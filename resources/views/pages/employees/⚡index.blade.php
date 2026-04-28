<?php

use App\Enums\CrisisRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Support\Employees\EmployeeExporter;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mitarbeiter')] class extends Component {
    public ?string $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $position = '';

    public ?string $department_id = null;

    public string $work_phone = '';

    public string $mobile_phone = '';

    public string $private_phone = '';

    public string $email = '';

    public ?string $location_id = null;

    public string $emergency_contact = '';

    /** @var array<int, string> */
    public array $manager_ids = [];

    public bool $is_key_personnel = false;

    public string $crisis_role = '';

    public bool $is_crisis_deputy = false;

    public string $notes = '';

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
                    'has_crisis_role' => $e->crisis_role !== null,
                    'crisis_role' => $e->crisis_role?->label() ?? '',
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

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->with(['managers', 'reports', 'location', 'department'])
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

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Department>
     */
    #[Computed]
    public function departmentOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::query()->orderBy('sort')->orderBy('name')->get();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    #[Computed]
    public function managerOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::query()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Location>
     */
    #[Computed]
    public function locationOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Location::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('employee-form')->show();
    }

    public function openEdit(string $id): void
    {
        $e = Employee::findOrFail($id);

        $this->editingId = $e->id;
        $this->first_name = $e->first_name;
        $this->last_name = $e->last_name;
        $this->position = (string) $e->position;
        $this->department_id = $e->department_id;
        $this->work_phone = (string) $e->work_phone;
        $this->mobile_phone = (string) $e->mobile_phone;
        $this->private_phone = (string) $e->private_phone;
        $this->email = (string) $e->email;
        $this->location_id = $e->location_id;
        $this->emergency_contact = (string) $e->emergency_contact;
        $this->manager_ids = $e->managers->pluck('id')->all();
        $this->is_key_personnel = (bool) $e->is_key_personnel;
        $this->crisis_role = $e->crisis_role?->value ?? '';
        $this->is_crisis_deputy = (bool) $e->is_crisis_deputy;
        $this->notes = (string) $e->notes;

        Flux::modal('employee-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'work_phone' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:50'],
            'private_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'emergency_contact' => ['nullable', 'string', 'max:1000'],
            'manager_ids' => ['array'],
            'manager_ids.*' => ['uuid', 'exists:employees,id'],
            'is_key_personnel' => ['boolean'],
            'crisis_role' => ['nullable', 'string', Rule::in(collect(CrisisRole::cases())->pluck('value'))],
            'is_crisis_deputy' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($validated['crisis_role'])) {
            $conflict = Employee::query()
                ->where('crisis_role', $validated['crisis_role'])
                ->where('is_crisis_deputy', $validated['is_crisis_deputy'] ?? false)
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->exists();

            if ($conflict) {
                $this->addError('crisis_role', __('Diese Krisenrolle ist bereits vergeben. Lösen Sie zuerst die andere Zuordnung oder markieren Sie diese Person als Vertretung.'));

                return;
            }
        } else {
            $validated['crisis_role'] = null;
            $validated['is_crisis_deputy'] = false;
        }

        $managerIds = collect($validated['manager_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->filter(fn ($id) => $id !== $this->editingId) // niemand ist sein eigener Vorgesetzter
            ->unique()
            ->values()
            ->all();
        unset($validated['manager_ids']);

        if ($this->editingId) {
            $employee = Employee::findOrFail($this->editingId);
            $employee->update($validated);
        } else {
            $employee = Employee::create($validated);
        }
        $employee->managers()->sync($managerIds);

        Flux::modal('employee-form')->close();
        $this->resetForm();
        unset($this->employees, $this->departments, $this->departmentOptions, $this->managerOptions, $this->locationOptions);

        Flux::toast(variant: 'success', text: __('Mitarbeiter gespeichert.'));
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
            unset($this->employees, $this->departments, $this->departmentOptions, $this->managerOptions, $this->locationOptions);
            Flux::modal('employee-delete')->close();
            Flux::toast(variant: 'success', text: __('Mitarbeiter gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'first_name', 'last_name', 'position', 'department_id',
            'work_phone', 'mobile_phone', 'private_phone', 'email', 'location_id',
            'emergency_contact', 'manager_ids', 'is_key_personnel',
            'crisis_role', 'is_crisis_deputy', 'notes',
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function crisisRoleOptions(): array
    {
        return CrisisRole::options();
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
            <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
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
        @endphp

        {{-- Wechsel zwischen Liste und Hierarchie ausschließlich Client-seitig per Alpine —
             kein Livewire-Roundtrip. Die Hierarchie-Daten werden bei jedem Pageload mit
             ausgeliefert; Cytoscape wird erst beim ersten Wechsel auf „Hierarchie" instanziiert. --}}
        <div
            x-data="{
                viewMode: @js($viewMode),
                cy: null,
                hSearch: '',
                hDepartment: '',
                hSelected: null,
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
                onShowHierarchy() {
                    if (!this.cy) {
                        this.ensureCytoscape();
                    } else {
                        this.$nextTick(() => { this.cy.resize(); this.cy.fit(); });
                    }
                },
                applyHierarchyFilter() {
                    if (!this.cy) return;
                    this.cy.applyFilter({ search: this.hSearch, department: this.hDepartment });
                },
                fit() { this.cy && this.cy.fit(); },
                zoomIn() { this.cy && this.cy.zoomBy(1.5); },
                zoomOut() { this.cy && this.cy.zoomBy(1 / 1.5); },
                resetZoom() { this.cy && this.cy.resetZoom(); },
            }"
            x-init="$watch('viewMode', (val) => { if (val === 'hierarchy') onShowHierarchy(); }); if (viewMode === 'hierarchy') onShowHierarchy();"
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
                </div>

                <div x-show="viewMode === 'list'" x-cloak class="flex flex-wrap items-center gap-3">
                    <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="{{ __('Suchen: Name, Rolle, E-Mail …') }}" class="max-w-sm" />
                    @if ($this->departments)
                        <flux:select wire:model.live="filterDepartment" placeholder="{{ __('Alle Abteilungen') }}" class="max-w-xs">
                            <flux:select.option value="">{{ __('Alle Abteilungen') }}</flux:select.option>
                            @foreach ($this->departments as $dept)
                                <flux:select.option value="{{ $dept }}">{{ $dept }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
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
                                @if (! empty($graph['departments']))
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
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400" x-text="hSelected.department || '{{ __('Keine Abteilung') }}'"></div>
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
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->employees as $employee)
                <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <flux:avatar :name="$employee->fullName()" size="sm" class="mt-0.5 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <flux:heading size="base">{{ $employee->nameLastFirst() }}</flux:heading>
                                @if ($employee->position)
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $employee->position }}</flux:text>
                                @endif
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    @if ($employee->is_key_personnel)
                                        <flux:badge color="amber" size="sm">{{ __('Schlüsselmitarbeiter') }}</flux:badge>
                                    @endif
                                    @if ($employee->crisis_role)
                                        <flux:badge color="red" size="sm">
                                            {{ $employee->crisis_role->label() }}@if ($employee->is_crisis_deputy) ({{ __('Vertretung') }})@endif
                                        </flux:badge>
                                    @endif
                                    @if ($employee->department)
                                        <flux:badge color="zinc" size="sm">{{ $employee->department->name }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <flux:dropdown align="end">
                            <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="openEdit('{{ $employee->id }}')">
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
                        <div class="mt-4 space-y-2 text-sm">
                            @if ($employee->mobile_phone)
                                <div class="flex items-center gap-2">
                                    <flux:icon.device-phone-mobile class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="tel:{{ $employee->mobile_phone }}" class="hover:underline">{{ $employee->mobile_phone }}</a>
                                </div>
                            @endif
                            @if ($employee->work_phone)
                                <div class="flex items-center gap-2">
                                    <flux:icon.phone class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="tel:{{ $employee->work_phone }}" class="hover:underline">{{ $employee->work_phone }}</a>
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
                            <div class="flex items-start gap-2 text-sm">
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
                            <div class="flex items-start gap-2 text-sm">
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

    <flux:modal name="employee-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Mitarbeiter bearbeiten') : __('Neuen Mitarbeiter anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Alle Felder außer Vor- und Nachnamen sind optional. Private Nummer und Notfallkontakt sind für echte Ernstfälle Gold wert, wenn E-Mail und Arbeitstelefon nicht funktionieren.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('Vorname')" required />
                <flux:input wire:model="last_name" :label="__('Nachname')" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="position" :label="__('Position')" placeholder="z. B. Vertriebsleitung" />
                <flux:select wire:model="department_id" :label="__('Abteilung')" :placeholder="__('Keine Abteilung')">
                    <flux:select.option value="">{{ __('— Keine Abteilung —') }}</flux:select.option>
                    @foreach ($this->departmentOptions as $dept)
                        <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($this->departmentOptions->isEmpty())
                    <flux:text class="-mt-2 text-xs text-zinc-500">
                        {{ __('Noch keine Abteilung angelegt — pflegen Sie diese unter „Abteilungen" in der Sidebar.') }}
                    </flux:text>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="email" :label="__('E-Mail')" type="email" />
                <flux:select wire:model="location_id" :label="__('Standort')">
                    <flux:select.option value="">{{ __('— kein Standort —') }}</flux:select.option>
                    @foreach ($this->locationOptions as $loc)
                        <flux:select.option value="{{ $loc->id }}">{{ $loc->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="work_phone" :label="__('Tel. (Büro)')" />
                <flux:input wire:model="mobile_phone" :label="__('Mobil (dienstlich)')" />
                <flux:input wire:model="private_phone" :label="__('Privat')" />
            </div>

            <flux:textarea
                wire:model="emergency_contact"
                :label="__('Notfallkontakt')"
                rows="2"
                placeholder="z. B. Angehöriger: Max Mustermann (Ehemann), 0171 …"
            />

            <flux:field>
                <flux:label>{{ __('Vorgesetzt von') }}</flux:label>
                <flux:description>
                    {{ __('Mehrere Vorgesetzte möglich (z. B. fachlich + disziplinarisch). Wenn niemand ausgewählt: keine Vorgesetzten.') }}
                </flux:description>
                @if ($this->managerOptions->isEmpty())
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Es sind noch keine anderen Mitarbeiter erfasst.') }}
                    </flux:text>
                @else
                    {{-- Plain-HTML-Checkboxen statt <flux:checkbox> für die Array-Bindung:
                         Flux' Web-Component <ui-checkbox> hatte bei wire:model="manager_ids"
                         über mehrere Checkboxen falsche Checked-States gesetzt.

                         wire:key ist hier zwingend: managerOptions filtert den aktuell
                         bearbeiteten Mitarbeiter raus, also verschieben sich die
                         Listenpositionen beim Wechsel zwischen Mitarbeitern. Ohne
                         wire:key recycelt morphdom die <input>-Elemente positionsbasiert
                         und alte checked-States kleben am neuen Kandidaten. --}}
                    <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($this->managerOptions as $candidate)
                            <label
                                wire:key="manager-option-{{ $candidate->id }}"
                                class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                            >
                                <input
                                    type="checkbox"
                                    wire:model="manager_ids"
                                    value="{{ $candidate->id }}"
                                    class="size-4 shrink-0 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700"
                                />
                                <span class="text-zinc-700 dark:text-zinc-200">
                                    {{ $candidate->nameLastFirst() }}@if ($candidate->position) <span class="text-zinc-500 dark:text-zinc-400">· {{ $candidate->position }}</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </flux:field>

            <flux:switch wire:model="is_key_personnel" :label="__('Schlüsselmitarbeiter – besonders wichtig für den Betrieb')" />

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

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
