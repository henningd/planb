<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\System;
use App\Support\Employees\EmployeeExporter;
use App\Support\PhoneFormat;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mitarbeiter')] class extends Component {
    public ?string $deletingId = null;

    public string $search = '';

    public string $filterDepartment = '';

    public string $viewMode = 'list';

    /**
     * Daten-Struktur für den Cytoscape-Hierarchie-Graph (Vorgesetzten-DAG).
     *
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>, departments: list<string>}
     */
    #[Computed]
    public function hierarchyGraph(): array
    {
        $employees = Employee::query()
            ->with(['managers:id', 'department:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $nodes = $employees->map(function (Employee $e) {
            $line2 = trim((string) ($e->position ?? ''));
            $label = $e->fullName().($line2 !== '' ? "\n{$line2}" : '');

            return [
                'data' => [
                    'id' => $e->id,
                    'label' => $label,
                    'department' => (string) ($e->department?->name ?? ''),
                    'is_key_personnel' => (bool) $e->is_key_personnel,
                    'has_crisis_role' => $e->crisisRole() !== null,
                    'crisis_role' => $e->crisisRole()?->label() ?? '',
                ],
            ];
        })->all();

        $edges = [];
        foreach ($employees as $employee) {
            foreach ($employee->managers as $manager) {
                $edges[] = [
                    'data' => [
                        'id' => "edge-{$manager->id}-{$employee->id}",
                        'source' => $manager->id,
                        'target' => $employee->id,
                    ],
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'departments' => $this->departments,
        ];
    }

    /**
     * Bipartiter Graph Mitarbeiter ↔ Rollen.
     *
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    #[Computed]
    public function rolesGraph(): array
    {
        $employees = Employee::query()->orderBy('last_name')->orderBy('first_name')->get();
        $roles = Role::query()
            ->with(['employees' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name')])
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        $nodes = [];
        foreach ($employees as $e) {
            $line2 = trim((string) ($e->position ?? ''));
            $label = $e->fullName().($line2 !== '' ? "\n{$line2}" : '');
            $nodes[] = [
                'data' => [
                    'id' => 'emp-'.$e->id,
                    'kind' => 'employee',
                    'label' => $label,
                    'is_key_personnel' => (bool) $e->is_key_personnel,
                    'has_crisis_role' => $e->crisisRole() !== null,
                    'crisis_role' => $e->crisisRole()?->label() ?? '',
                ],
            ];
        }

        foreach ($roles as $role) {
            $nodes[] = [
                'data' => [
                    'id' => 'role-'.$role->id,
                    'kind' => 'role',
                    'label' => $role->name,
                    'is_system' => $role->isSystem(),
                ],
            ];
        }

        $edges = [];
        foreach ($roles as $role) {
            foreach ($role->employees as $employee) {
                $edges[] = [
                    'data' => [
                        'id' => 'edge-emp-'.$employee->id.'-role-'.$role->id,
                        'source' => 'emp-'.$employee->id,
                        'target' => 'role-'.$role->id,
                        'is_deputy' => (bool) ($employee->pivot->is_deputy ?? false),
                    ],
                ];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Tripartiter Graph Mitarbeiter → Rolle → System plus direkte
     * Mitarbeiter → System-Zuordnungen.
     *
     * Rollen-Knoten erscheinen nur, wenn die Rolle mindestens einen
     * Mitarbeiter und mindestens ein System verbindet (Brücken-Funktion);
     * Rollen ohne diese Brücke bleiben hier unsichtbar — sie sind im
     * dedizierten Rollen-Tab abgebildet.
     *
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    #[Computed]
    public function systemsGraph(): array
    {
        $employees = Employee::query()->orderBy('last_name')->orderBy('first_name')->get();
        $systems = System::query()
            ->with(['employees' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name')])
            ->orderBy('name')
            ->get();
        $roles = Role::query()
            ->with([
                'employees' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
                'systems' => fn ($q) => $q->orderBy('systems.name'),
            ])
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        $nodes = [];

        foreach ($employees as $e) {
            $line2 = trim((string) ($e->position ?? ''));
            $label = $e->fullName().($line2 !== '' ? "\n{$line2}" : '');
            $nodes[] = [
                'data' => [
                    'id' => 'emp-'.$e->id,
                    'kind' => 'employee',
                    'label' => $label,
                    'is_key_personnel' => (bool) $e->is_key_personnel,
                    'has_crisis_role' => $e->crisisRole() !== null,
                    'crisis_role' => $e->crisisRole()?->label() ?? '',
                ],
            ];
        }

        // Rollen erscheinen nur, wenn sie tatsächlich Mitarbeiter UND
        // Systeme verbinden — sonst bringen sie auf dieser Sicht nichts.
        $bridgingRoles = $roles->filter(
            fn ($role) => $role->employees->isNotEmpty() && $role->systems->isNotEmpty()
        );
        foreach ($bridgingRoles as $role) {
            $nodes[] = [
                'data' => [
                    'id' => 'role-'.$role->id,
                    'kind' => 'role',
                    'label' => $role->name,
                    'is_system' => $role->isSystem(),
                ],
            ];
        }

        foreach ($systems as $system) {
            $nodes[] = [
                'data' => [
                    'id' => 'sys-'.$system->id,
                    'kind' => 'system',
                    'label' => $system->name,
                ],
            ];
        }

        $edges = [];

        // 1. Direkte Zuordnungen Mitarbeiter → System (RACI-Pivot).
        foreach ($systems as $system) {
            foreach ($system->employees as $employee) {
                $edges[] = [
                    'data' => [
                        'id' => 'edge-direct-emp-'.$employee->id.'-sys-'.$system->id,
                        'source' => 'emp-'.$employee->id,
                        'target' => 'sys-'.$system->id,
                        'kind' => 'direct',
                        'raci_role' => $employee->pivot->raci_role ?? null,
                        'is_deputy' => (bool) ($employee->pivot->is_deputy ?? false),
                    ],
                ];
            }
        }

        // 2a. Mitarbeiter → Rolle (für Rollen, die als Brücke zu einem
        //     System dienen).
        // 2b. Rolle → System (für dieselben Rollen).
        foreach ($bridgingRoles as $role) {
            foreach ($role->employees as $employee) {
                $edges[] = [
                    'data' => [
                        'id' => 'edge-emp-'.$employee->id.'-role-'.$role->id,
                        'source' => 'emp-'.$employee->id,
                        'target' => 'role-'.$role->id,
                        'kind' => 'emp-role',
                        'is_deputy' => (bool) ($employee->pivot->is_deputy ?? false),
                    ],
                ];
            }
            foreach ($role->systems as $system) {
                $edges[] = [
                    'data' => [
                        'id' => 'edge-role-'.$role->id.'-sys-'.$system->id,
                        'source' => 'role-'.$role->id,
                        'target' => 'sys-'.$system->id,
                        'kind' => 'role-sys',
                        'raci_role' => $system->pivot->raci_role ?? null,
                    ],
                ];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->with(['managers', 'reports', 'location', 'department', 'roles', 'systems'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('position', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->filterDepartment !== '', fn ($q) => $q->whereHas('department', fn ($q2) => $q2->where('name', $this->filterDepartment)))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Department-Namen der aktuellen Firma (alphabetisch). Wird für den
     * Filter und für das Hierarchie-Drop-down verwendet.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function departments(): array
    {
        return Department::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('employee-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Employee::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->employees, $this->departments, $this->rolesGraph, $this->systemsGraph, $this->hierarchyGraph);
            Flux::modal('employee-delete')->close();
            Flux::toast(variant: 'success', text: __('Mitarbeiter gelöscht.'));
        }
    }

    public function exportJson(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $company = Auth::user()?->currentCompany();
        abort_unless($company !== null, 404);

        $payload = EmployeeExporter::export($company);
        $filename = EmployeeExporter::filename($company);

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Mitarbeiter') }}</flux:heading>
            <flux:subheading>
                {{ __('Die gesamte Belegschaft mit Kontaktdaten – wichtig für Benachrichtigungsketten und Krisenkommunikation.') }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button
                variant="ghost"
                icon="arrow-down-tray"
                wire:click="exportJson"
                :disabled="! $this->hasCompany"
                title="{{ __('Alle Mitarbeiter mit Stammdaten, Vorgesetzten, Rollen und System-Zuweisungen als JSON') }}"
            >
                {{ __('JSON-Export') }}
            </flux:button>
            <flux:button
                variant="primary"
                icon="plus"
                :href="$this->hasCompany ? route('employees.create') : null"
                wire:navigate
                :disabled="! $this->hasCompany"
            >
                {{ __('Neuer Mitarbeiter') }}
            </flux:button>
        </div>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        @php
            $graph = $this->hierarchyGraph;
            $hasNodes = count($graph['nodes']) > 0;
            $hasEdges = count($graph['edges']) > 0;
            $graphTabsEnabled = config('features.employee_graph_tabs');
            $rolesGraph = $graphTabsEnabled ? $this->rolesGraph : ['nodes' => [], 'edges' => []];
            $systemsGraph = $graphTabsEnabled ? $this->systemsGraph : ['nodes' => [], 'edges' => []];
            $hasRolesEdges = count($rolesGraph['edges']) > 0;
            $hasSystemsEdges = count($systemsGraph['edges']) > 0;
        @endphp

        {{-- Wechsel zwischen Liste und Hierarchie ausschließlich Client-seitig per Alpine —
             kein Livewire-Roundtrip. Die Hierarchie-Daten werden bei jedem Pageload mit
             ausgeliefert; Cytoscape wird erst beim ersten Wechsel auf „Hierarchie" instanziiert. --}}
        <div
            x-data="{
                viewMode: @js($viewMode),
                cy: null,
                cyRoles: null,
                cySystems: null,
                hSearch: '',
                hDepartment: '',
                hSelected: null,
                rSearch: '',
                rSelected: null,
                sSearch: '',
                sSelected: null,
                ensureCytoscape() {
                    if (this.cy) return;
                    const start = () => {
                        if (!window.PlanB || !window.PlanB.initEmployeeHierarchy) {
                            requestAnimationFrame(start);
                            return;
                        }
                        this.cy = window.PlanB.initEmployeeHierarchy({
                            containerId: 'employee-hierarchy-canvas',
                            nodes: @js($graph['nodes']),
                            edges: @js($graph['edges']),
                            onSelect: (data) => { this.hSelected = data; },
                        });
                    };
                    this.$nextTick(start);
                },
                ensureBipartite(which) {
                    const target = which === 'roles' ? 'cyRoles' : 'cySystems';
                    if (this[target]) return;
                    const start = () => {
                        if (!window.PlanB || !window.PlanB.initEmployeeBipartite) {
                            requestAnimationFrame(start);
                            return;
                        }
                        this[target] = window.PlanB.initEmployeeBipartite({
                            containerId: which === 'roles' ? 'employee-roles-canvas' : 'employee-systems-canvas',
                            nodes: which === 'roles' ? @js($rolesGraph['nodes']) : @js($systemsGraph['nodes']),
                            edges: which === 'roles' ? @js($rolesGraph['edges']) : @js($systemsGraph['edges']),
                            onSelect: (data) => {
                                if (which === 'roles') { this.rSelected = data; }
                                else { this.sSelected = data; }
                            },
                        });
                    };
                    this.$nextTick(start);
                },
                onShowHierarchy() {
                    if (!this.cy) { this.ensureCytoscape(); }
                    else { this.$nextTick(() => { this.cy.resize(); this.cy.fit(); }); }
                },
                onShowRoles() {
                    if (!this.cyRoles) { this.ensureBipartite('roles'); }
                    else { this.$nextTick(() => { this.cyRoles.cy.resize(); this.cyRoles.fit(); }); }
                },
                onShowSystems() {
                    if (!this.cySystems) { this.ensureBipartite('systems'); }
                    else { this.$nextTick(() => { this.cySystems.cy.resize(); this.cySystems.fit(); }); }
                },
                applyHierarchyFilter() {
                    if (!this.cy) return;
                    this.cy.applyFilter({ search: this.hSearch, department: this.hDepartment });
                },
                applyRolesFilter() { this.cyRoles && this.cyRoles.applyFilter({ search: this.rSearch }); },
                applySystemsFilter() { this.cySystems && this.cySystems.applyFilter({ search: this.sSearch }); },
                activeView() {
                    if (this.viewMode === 'hierarchy') return this.cy;
                    if (this.viewMode === 'roles') return this.cyRoles;
                    if (this.viewMode === 'systems') return this.cySystems;
                    return null;
                },
                fit() { const v = this.activeView(); v && v.fit(); },
                zoomIn() { const v = this.activeView(); v && v.zoomBy(1.5); },
                zoomOut() { const v = this.activeView(); v && v.zoomBy(1 / 1.5); },
                resetZoom() { const v = this.activeView(); v && v.resetZoom(); },
            }"
            x-init="
                $watch('viewMode', (val) => {
                    if (val === 'hierarchy') onShowHierarchy();
                    if (val === 'roles') onShowRoles();
                    if (val === 'systems') onShowSystems();
                });
                if (viewMode === 'hierarchy') onShowHierarchy();
                if (viewMode === 'roles') onShowRoles();
                if (viewMode === 'systems') onShowSystems();
            "
        >
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800" role="tablist" aria-label="{{ __('Ansicht') }}">
                    <button
                        type="button"
                        @click="viewMode = 'list'"
                        role="tab"
                        :aria-selected="viewMode === 'list' ? 'true' : 'false'"
                        class="inline-flex items-center gap-1 rounded-md px-3 py-1 text-xs font-medium transition"
                        :class="viewMode === 'list' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                    >
                        <flux:icon name="list-bullet" class="size-4" />
                        {{ __('Liste') }}
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'hierarchy'"
                        role="tab"
                        :aria-selected="viewMode === 'hierarchy' ? 'true' : 'false'"
                        class="inline-flex items-center gap-1 rounded-md px-3 py-1 text-xs font-medium transition"
                        :class="viewMode === 'hierarchy' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                    >
                        <flux:icon name="share" class="size-4" />
                        {{ __('Hierarchie') }}
                    </button>
                    @if ($graphTabsEnabled)
                        <button
                            type="button"
                            @click="viewMode = 'roles'"
                            role="tab"
                            :aria-selected="viewMode === 'roles' ? 'true' : 'false'"
                            class="inline-flex items-center gap-1 rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="viewMode === 'roles' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                        >
                            <flux:icon name="user-group" class="size-4" />
                            {{ __('Rollen') }}
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'systems'"
                            role="tab"
                            :aria-selected="viewMode === 'systems' ? 'true' : 'false'"
                            class="inline-flex items-center gap-1 rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="viewMode === 'systems' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                        >
                            <flux:icon name="server-stack" class="size-4" />
                            {{ __('Systeme') }}
                        </button>
                    @endif
                </div>

                <flux:input x-show="viewMode === 'list'" x-cloak wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="{{ __('Suchen: Name, Rolle, E-Mail …') }}" class="max-w-sm" />
                @if (config('features.departments') && $this->departments)
                    <flux:select x-show="viewMode === 'list'" x-cloak wire:model.live="filterDepartment" placeholder="{{ __('Alle Abteilungen') }}" class="max-w-xs">
                        <flux:select.option value="">{{ __('Alle Abteilungen') }}</flux:select.option>
                        @foreach ($this->departments as $dept)
                            <flux:select.option value="{{ $dept }}">{{ $dept }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div
                    wire:loading.delay.shortest
                    wire:target="search,filterDepartment"
                    x-show="viewMode === 'list'"
                    x-cloak
                    class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400"
                    role="status"
                    aria-live="polite"
                >
                    <flux:icon.loading variant="mini" />
                    <span>{{ __('Filtere …') }}</span>
                </div>
            </div>

            <div x-show="viewMode === 'hierarchy'" x-cloak>
                @if (! $hasNodes)
                    <div class="rounded-xl border border-zinc-200 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Mitarbeiter angelegt.') }}
                        </flux:text>
                    </div>
                @else
                    <div
                        wire:ignore
                        class="grid gap-4 lg:grid-cols-[1fr_20rem]"
                    >
                        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 p-3 dark:border-zinc-800">
                                <flux:input
                                    x-model.debounce.250ms="hSearch"
                                    @input="applyHierarchyFilter()"
                                    size="sm"
                                    icon="magnifying-glass"
                                    placeholder="{{ __('Suchen…') }}"
                                    class="w-44"
                                />
                                @if (config('features.departments') && ! empty($graph['departments']))
                                    <select
                                        x-model="hDepartment"
                                        @change="applyHierarchyFilter()"
                                        class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        <option value="">{{ __('Alle Abteilungen') }}</option>
                                        @foreach ($graph['departments'] as $dept)
                                            <option value="{{ $dept }}">{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                <div class="ml-auto flex items-center gap-0.5 rounded-lg bg-zinc-100 p-0.5 dark:bg-zinc-800">
                                    <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomOut()" title="{{ __('Verkleinern') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M5 9.75A.75.75 0 0 1 5.75 9h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 5 9.75Z"/></svg>
                                    </button>
                                    <button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="resetZoom()" title="{{ __('Zoom zurücksetzen') }}">1:1</button>
                                    <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomIn()" title="{{ __('Vergrößern') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 5.75a.75.75 0 0 0-1.5 0V9h-3.5a.75.75 0 0 0 0 1.5h3.5v3.25a.75.75 0 0 0 1.5 0V10.5h3.5a.75.75 0 0 0 0-1.5h-3.5V5.75Z"/></svg>
                                    </button>
                                </div>
                                <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" @click="fit()">{{ __('Einpassen') }}</flux:button>
                            </div>
                            <div id="employee-hierarchy-canvas" class="h-[640px] w-full"></div>

                            @if (! $hasEdges)
                                <div class="border-t border-zinc-100 px-4 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                                    {{ __('Noch keine Vorgesetzten-Beziehungen erfasst — bearbeiten Sie einzelne Mitarbeiter und setzen Sie das Feld „Vorgesetzte", damit hier ein Org-Chart entsteht.') }}
                                </div>
                            @endif
                        </div>

                        <aside class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <flux:heading size="sm">{{ __('Legende') }}</flux:heading>
                            <ul class="mt-3 space-y-2 text-xs text-zinc-600 dark:text-zinc-300">
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#fee2e2;border-color:#dc2626"></span>{{ __('Mit Krisenrolle') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#fef3c7;border-color:#d97706"></span>{{ __('Schlüsselperson') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#eef2ff;border-color:#6366f1"></span>{{ __('Standard-Mitarbeiter') }}</li>
                                <li class="flex items-center gap-2"><svg viewBox="0 0 24 12" class="h-3 w-6"><path d="M2 6h17" stroke="#0ea5e9" stroke-width="2" fill="none"/><path d="M22 6l-4-3v6z" fill="#0ea5e9"/></svg>{{ __('Beim Hover: Vorgesetzten-Pfad') }}</li>
                                <li class="flex items-center gap-2"><svg viewBox="0 0 24 12" class="h-3 w-6"><path d="M2 6h17" stroke="#f59e0b" stroke-width="2" fill="none"/><path d="M22 6l-4-3v6z" fill="#f59e0b"/></svg>{{ __('Beim Hover: Unterstellte') }}</li>
                            </ul>

                            <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                                <flux:heading size="sm">{{ __('Auswahl') }}</flux:heading>
                                <template x-if="hSelected">
                                    <div class="mt-2 space-y-1 text-sm">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="hSelected.label.replace(/\n/g, ' — ')"></div>
                                        @if (config('features.departments'))
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400" x-text="hSelected.department || '{{ __('Keine Abteilung') }}'"></div>
                                        @endif
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400" x-show="hSelected.has_crisis_role" x-text="'{{ __('Krisenrolle:') }} ' + hSelected.crisis_role"></div>
                                    </div>
                                </template>
                                <template x-if="!hSelected">
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Klicken Sie einen Knoten an, um Details zu sehen.') }}</div>
                                </template>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>

            @if ($graphTabsEnabled)
            <div x-show="viewMode === 'roles'" x-cloak>
                @if ($this->employees->isEmpty())
                    <div class="rounded-xl border border-zinc-200 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Mitarbeiter angelegt.') }}</flux:text>
                    </div>
                @else
                    <div wire:ignore class="grid gap-4 lg:grid-cols-[1fr_20rem]">
                        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 p-3 dark:border-zinc-800">
                                <flux:input
                                    x-model.debounce.250ms="rSearch"
                                    @input="applyRolesFilter()"
                                    size="sm"
                                    icon="magnifying-glass"
                                    placeholder="{{ __('Suchen: Mitarbeiter oder Rolle …') }}"
                                    class="w-60"
                                />
                                <div class="ml-auto flex items-center gap-0.5 rounded-lg bg-zinc-100 p-0.5 dark:bg-zinc-800">
                                    <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomOut()" title="{{ __('Verkleinern') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M5 9.75A.75.75 0 0 1 5.75 9h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 5 9.75Z"/></svg>
                                    </button>
                                    <button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="resetZoom()" title="{{ __('Zoom zurücksetzen') }}">1:1</button>
                                    <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomIn()" title="{{ __('Vergrößern') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 5.75a.75.75 0 0 0-1.5 0V9h-3.5a.75.75 0 0 0 0 1.5h3.5v3.25a.75.75 0 0 0 1.5 0V10.5h3.5a.75.75 0 0 0 0-1.5h-3.5V5.75Z"/></svg>
                                    </button>
                                </div>
                                <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" @click="fit()">{{ __('Einpassen') }}</flux:button>
                            </div>
                            <div id="employee-roles-canvas" class="h-[640px] w-full"></div>
                            @if (! $hasRolesEdges)
                                <div class="border-t border-zinc-100 px-4 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                                    {{ __('Noch keine Rollen-Zuordnungen erfasst — pflegen Sie diese unter „Rollen" oder direkt am Mitarbeiter.') }}
                                </div>
                            @endif
                        </div>

                        <aside class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <flux:heading size="sm">{{ __('Legende') }}</flux:heading>
                            <ul class="mt-3 space-y-2 text-xs text-zinc-600 dark:text-zinc-300">
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#fee2e2;border-color:#dc2626"></span>{{ __('Mitarbeiter mit Krisenrolle') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#fef3c7;border-color:#d97706"></span>{{ __('Schlüsselperson') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#eef2ff;border-color:#6366f1"></span>{{ __('Standard-Mitarbeiter') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#dbeafe;border-color:#1d4ed8"></span>{{ __('System-Rolle (Pflicht)') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#ecfeff;border-color:#0891b2"></span>{{ __('Eigene Rolle') }}</li>
                                <li class="flex items-center gap-2"><svg viewBox="0 0 24 12" class="h-3 w-6"><path d="M2 6h17" stroke="#a855f7" stroke-width="2" stroke-dasharray="3 2" fill="none"/><path d="M22 6l-4-3v6z" fill="#a855f7"/></svg>{{ __('Stellvertretung') }}</li>
                            </ul>

                            <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                                <flux:heading size="sm">{{ __('Auswahl') }}</flux:heading>
                                <template x-if="rSelected">
                                    <div class="mt-2 space-y-1 text-sm">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="rSelected.label.replace(/\n/g, ' — ')"></div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400" x-show="rSelected.kind === 'employee' && rSelected.has_crisis_role" x-text="'{{ __('Krisenrolle:') }} ' + rSelected.crisis_role"></div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400" x-show="rSelected.kind === 'role' && rSelected.is_system">{{ __('Pflichtrolle (System)') }}</div>
                                    </div>
                                </template>
                                <template x-if="!rSelected">
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Klicken Sie einen Knoten an, um Details zu sehen.') }}</div>
                                </template>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>

            <div x-show="viewMode === 'systems'" x-cloak>
                @if ($this->employees->isEmpty())
                    <div class="rounded-xl border border-zinc-200 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Mitarbeiter angelegt.') }}</flux:text>
                    </div>
                @else
                    <div wire:ignore class="grid gap-4 lg:grid-cols-[1fr_20rem]">
                        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 p-3 dark:border-zinc-800">
                                <flux:input
                                    x-model.debounce.250ms="sSearch"
                                    @input="applySystemsFilter()"
                                    size="sm"
                                    icon="magnifying-glass"
                                    placeholder="{{ __('Suchen: Mitarbeiter oder System …') }}"
                                    class="w-60"
                                />
                                <div class="ml-auto flex items-center gap-0.5 rounded-lg bg-zinc-100 p-0.5 dark:bg-zinc-800">
                                    <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomOut()" title="{{ __('Verkleinern') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M5 9.75A.75.75 0 0 1 5.75 9h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 5 9.75Z"/></svg>
                                    </button>
                                    <button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="resetZoom()" title="{{ __('Zoom zurücksetzen') }}">1:1</button>
                                    <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomIn()" title="{{ __('Vergrößern') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 5.75a.75.75 0 0 0-1.5 0V9h-3.5a.75.75 0 0 0 0 1.5h3.5v3.25a.75.75 0 0 0 1.5 0V10.5h3.5a.75.75 0 0 0 0-1.5h-3.5V5.75Z"/></svg>
                                    </button>
                                </div>
                                <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" @click="fit()">{{ __('Einpassen') }}</flux:button>
                            </div>
                            <div id="employee-systems-canvas" class="h-[640px] w-full"></div>
                            @if (! $hasSystemsEdges)
                                <div class="border-t border-zinc-100 px-4 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                                    {{ __('Noch keine System-Zuordnungen erfasst — pflegen Sie diese unter „Systeme" am jeweiligen System.') }}
                                </div>
                            @endif
                        </div>

                        <aside class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <flux:heading size="sm">{{ __('Legende') }}</flux:heading>
                            <ul class="mt-3 space-y-2 text-xs text-zinc-600 dark:text-zinc-300">
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#fee2e2;border-color:#dc2626"></span>{{ __('Mitarbeiter mit Krisenrolle') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#fef3c7;border-color:#d97706"></span>{{ __('Schlüsselperson') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#eef2ff;border-color:#6366f1"></span>{{ __('Standard-Mitarbeiter') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#ecfeff;border-color:#0891b2"></span>{{ __('Rolle (verknüpft Mitarbeiter mit Systemen)') }}</li>
                                <li class="flex items-center gap-2"><span class="inline-block h-3 w-5 rounded border-2" style="background:#f0fdf4;border-color:#16a34a"></span>{{ __('System') }}</li>
                                <li class="flex items-center gap-2"><svg viewBox="0 0 24 12" class="h-3 w-6"><path d="M2 6h17" stroke="#9ca3af" stroke-width="2" fill="none"/><path d="M22 6l-4-3v6z" fill="#9ca3af"/></svg>{{ __('Mitarbeiter → System direkt (RACI)') }}</li>
                                <li class="flex items-center gap-2"><svg viewBox="0 0 24 12" class="h-3 w-6"><path d="M2 6h17" stroke="#0d9488" stroke-width="2" stroke-dasharray="2 2" fill="none"/><path d="M22 6l-4-3v6z" fill="#0d9488"/></svg>{{ __('Mitarbeiter → Rolle → System') }}</li>
                                <li class="flex items-center gap-2"><svg viewBox="0 0 24 12" class="h-3 w-6"><path d="M2 6h17" stroke="#a855f7" stroke-width="2" stroke-dasharray="3 2" fill="none"/><path d="M22 6l-4-3v6z" fill="#a855f7"/></svg>{{ __('Stellvertretung') }}</li>
                            </ul>

                            <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                                <flux:heading size="sm">{{ __('Auswahl') }}</flux:heading>
                                <template x-if="sSelected">
                                    <div class="mt-2 space-y-1 text-sm">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="sSelected.label.replace(/\n/g, ' — ')"></div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400" x-show="sSelected.kind === 'employee' && sSelected.has_crisis_role" x-text="'{{ __('Krisenrolle:') }} ' + sSelected.crisis_role"></div>
                                    </div>
                                </template>
                                <template x-if="!sSelected">
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Klicken Sie einen Knoten an, um Details zu sehen.') }}</div>
                                </template>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>
            @endif

            <div x-show="viewMode === 'list'" x-cloak>
                @if ($this->employees->isEmpty())
                    <div class="rounded-xl border border-zinc-200 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                            @if ($this->search !== '' || $this->filterDepartment !== '')
                                {{ __('Keine Mitarbeiter gefunden, die zu den Filtern passen.') }}
                            @else
                                {{ __('Noch keine Mitarbeiter angelegt.') }}
                            @endif
                        </flux:text>
                    </div>
                @else
                    <div
                        wire:loading.delay.shortest.class="opacity-50"
                        wire:target="search,filterDepartment"
                        class="grid gap-4 transition-opacity sm:grid-cols-2 xl:grid-cols-3"
                    >
                        @foreach ($this->employees as $employee)
                <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <flux:avatar :name="$employee->fullName()" size="sm" class="mt-0.5 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <flux:heading size="base">
                                    <a href="{{ route('employees.show', $employee) }}" wire:navigate class="hover:underline">
                                        {{ $employee->nameLastFirst() }}
                                    </a>
                                </flux:heading>
                                @if ($employee->position)
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $employee->position }}</flux:text>
                                @endif
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    @if ($employee->is_key_personnel)
                                        <flux:badge color="amber" size="sm">{{ __('Schlüsselmitarbeiter') }}</flux:badge>
                                    @endif
                                    @if (config('features.departments') && $employee->department)
                                        <flux:badge color="zinc" size="sm">{{ $employee->department->name }}</flux:badge>
                                    @endif
                                    @foreach ($employee->systems->sortBy('name') as $system)
                                        <flux:badge color="zinc" size="sm" icon="server-stack">{{ $system->name }}</flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <flux:dropdown align="end">
                            <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item icon="eye" :href="route('employees.show', $employee)" wire:navigate>
                                    {{ __('Details') }}
                                </flux:menu.item>
                                <flux:menu.item icon="pencil" :href="route('employees.edit', $employee)" wire:navigate>
                                    {{ __('Bearbeiten') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $employee->id }}')">
                                    {{ __('Löschen') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    @if ($employee->mobile_phone || $employee->work_phone || $employee->email || $employee->location_id)
                        <div class="mt-4 space-y-2 pl-11 text-sm">
                            @if ($employee->mobile_phone)
                                <div class="flex items-center gap-2">
                                    <flux:icon.device-phone-mobile class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="tel:{{ PhoneFormat::tel($employee->mobile_phone) }}" class="hover:underline">{{ PhoneFormat::display($employee->mobile_phone) }}</a>
                                </div>
                            @endif
                            @if ($employee->work_phone)
                                <div class="flex items-center gap-2">
                                    <flux:icon.phone class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="tel:{{ PhoneFormat::tel($employee->work_phone) }}" class="hover:underline">{{ PhoneFormat::display($employee->work_phone) }}</a>
                                </div>
                            @endif
                            @if ($employee->email)
                                <div class="flex items-center gap-2">
                                    <flux:icon.envelope class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="mailto:{{ $employee->email }}" class="truncate hover:underline">{{ $employee->email }}</a>
                                </div>
                            @endif
                            @if ($employee->location)
                                <div class="flex items-center gap-2">
                                    <flux:icon.map-pin class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <span>{{ $employee->location->name }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($employee->managers->isNotEmpty())
                        <div class="mt-3 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <div class="flex items-start gap-2 pl-11 text-sm">
                                <flux:icon name="user-circle" class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Unterstellt') }}</div>
                                    <div class="mt-0.5 text-zinc-700 dark:text-zinc-200">
                                        {{ $employee->managers->map(fn ($m) => $m->nameLastFirst())->implode(' · ') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($employee->reports->isNotEmpty())
                        <div class="mt-2 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                            <div class="flex items-start gap-2 pl-11 text-sm">
                                <flux:icon name="users" class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ trans_choice('{1} Vorgesetzte:r|[2,*] Vorgesetzte:r (:count)', $employee->reports->count(), ['count' => $employee->reports->count()]) }}</div>
                                    <div class="mt-0.5 text-zinc-700 dark:text-zinc-200">
                                        {{ $employee->reports->map(fn ($r) => $r->nameLastFirst())->implode(' · ') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <flux:modal name="employee-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Mitarbeiter löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
