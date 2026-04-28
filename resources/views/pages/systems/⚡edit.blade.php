<?php

use App\Enums\SystemCategory;
use App\Enums\SystemType;
use App\Models\Employee;
use App\Models\EmergencyLevel;
use App\Models\Role;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemPriority;
use App\Models\SystemTask;
use App\Support\AssignmentSync;
use App\Support\Duration;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('System bearbeiten')] class extends Component {
    public ?System $system = null;

    public string $name = '';

    public string $description = '';

    public string $fallback_process = '';

    public string $runbook_reference = '';

    public ?string $emergency_level_id = null;

    public string $category = '';

    public string $system_type = '';

    public ?string $system_priority_id = null;

    public ?int $rto_minutes = null;

    public ?int $rpo_minutes = null;

    public ?int $downtime_cost_per_hour = null;

    public string $monitoring_keys_text = '';

    /** @var array<int, array{provider_id: string, ownership_kind: string, is_deputy: bool, note: string}> */
    public array $providerAssignments = [];

    public string $providerSearch = '';

    /** @var array<int, array{employee_id: string, ownership_kind: string, is_deputy: bool, note: string}> */
    public array $responsibles = [];

    public string $employeeSearch = '';

    /** @var array<int, array{role_id: string, ownership_kind: string, is_deputy: bool, note: string}> */
    public array $roleAssignments = [];

    public string $roleSearch = '';

    /** @var array<int, array{dependency_id: string, note: string}> */
    public array $dependencyAssignments = [];

    public string $dependencySearch = '';

    public function mount(?System $system = null): void
    {
        abort_unless(Auth::user()->currentCompany(), 403);

        if ($system && $system->exists) {
            $system->load(['serviceProviders', 'employees', 'roles', 'dependencies']);

            $this->system = $system;
            $this->name = $system->name;
            $this->description = (string) $system->description;
            $this->fallback_process = (string) $system->fallback_process;
            $this->runbook_reference = (string) $system->runbook_reference;
            $this->emergency_level_id = $system->emergency_level_id;
            $this->category = $system->category->value;
            $this->system_type = $system->system_type?->value ?? '';
            $this->system_priority_id = $system->system_priority_id;
            $this->rto_minutes = $system->rto_minutes;
            $this->rpo_minutes = $system->rpo_minutes;
            $this->downtime_cost_per_hour = $system->downtime_cost_per_hour;
            $this->monitoring_keys_text = is_array($system->monitoring_keys)
                ? implode("\n", $system->monitoring_keys)
                : '';
            $this->providerAssignments = $system->serviceProviders
                ->map(fn (ServiceProvider $p) => [
                    'provider_id' => $p->id,
                    'ownership_kind' => (string) ($p->pivot->ownership_kind ?? ''),
                    'is_deputy' => (bool) ($p->pivot->is_deputy ?? false),
                    'note' => (string) ($p->pivot->note ?? ''),
                ])
                ->values()
                ->all();
            $this->responsibles = $system->employees
                ->map(fn (Employee $e) => [
                    'employee_id' => $e->id,
                    'ownership_kind' => (string) ($e->pivot->ownership_kind ?? ''),
                    'is_deputy' => (bool) ($e->pivot->is_deputy ?? false),
                    'note' => (string) ($e->pivot->note ?? ''),
                ])
                ->values()
                ->all();
            $this->dependencyAssignments = $system->dependencies
                ->map(fn (System $s) => [
                    'dependency_id' => $s->id,
                    'note' => (string) ($s->pivot->note ?? ''),
                ])
                ->values()
                ->all();
            $this->roleAssignments = $system->roles
                ->map(fn (Role $r) => [
                    'role_id' => $r->id,
                    'ownership_kind' => (string) ($r->pivot->ownership_kind ?? ''),
                    'is_deputy' => (bool) ($r->pivot->is_deputy ?? false),
                    'note' => (string) ($r->pivot->note ?? ''),
                ])
                ->values()
                ->all();

            return;
        }

        $requested = request()->string('category')->toString();
        $this->category = in_array($requested, array_column(SystemCategory::cases(), 'value'), true)
            ? $requested
            : SystemCategory::Basisbetrieb->value;
    }

    /**
     * @return Collection<int, SystemPriority>
     */
    #[Computed]
    public function priorities(): Collection
    {
        return SystemPriority::orderBy('sort')->get();
    }

    /**
     * Read-only listing of tasks for this system. Management
     * (anlegen/bearbeiten/löschen) erfolgt auf der Detail-Seite.
     *
     * @return Collection<int, SystemTask>
     */
    #[Computed]
    public function tasks(): Collection
    {
        if (! $this->system) {
            return new Collection;
        }

        return SystemTask::with(['assignees', 'providerAssignees'])
            ->where('system_id', $this->system->id)
            ->orderByRaw('completed_at IS NULL DESC')
            ->orderBy('sort')
            ->get();
    }

    /**
     * @return Collection<int, EmergencyLevel>
     */
    #[Computed]
    public function emergencyLevels(): Collection
    {
        return EmergencyLevel::orderBy('sort')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, ServiceProvider>
     */
    #[Computed]
    public function providers(): Collection
    {
        return ServiceProvider::orderBy('name')->get();
    }

    /**
     * @return array<string, ServiceProvider>
     */
    #[Computed]
    public function providersById(): array
    {
        return $this->providers->keyBy('id')->all();
    }

    /**
     * @return Collection<int, ServiceProvider>
     */
    #[Computed]
    public function availableProviders(): Collection
    {
        $taken = collect($this->providerAssignments)->pluck('provider_id')->all();
        $needle = mb_strtolower(trim($this->providerSearch));

        return $this->providers
            ->reject(fn (ServiceProvider $p) => in_array($p->id, $taken, true))
            ->filter(function (ServiceProvider $p) use ($needle) {
                if ($needle === '') {
                    return true;
                }

                $hay = mb_strtolower($p->name.' '.$p->hotline.' '.$p->contact_name);

                return str_contains($hay, $needle);
            })
            ->values();
    }

    public function addProviderById(string $id): void
    {
        $already = collect($this->providerAssignments)->pluck('provider_id')->contains($id);
        if ($already) {
            return;
        }

        if (! array_key_exists($id, $this->providersById)) {
            return;
        }

        $this->providerAssignments[] = [
            'provider_id' => $id,
            'ownership_kind' => '',
            'is_deputy' => false,
            'note' => '',
        ];

        $this->providerSearch = '';
    }

    public function removeProvider(int $index): void
    {
        if (! isset($this->providerAssignments[$index])) {
            return;
        }

        unset($this->providerAssignments[$index]);
        $this->providerAssignments = array_values($this->providerAssignments);
    }

    public function moveProviderUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->providerAssignments[$index], $this->providerAssignments[$index - 1])) {
            return;
        }

        [$this->providerAssignments[$index - 1], $this->providerAssignments[$index]] = [
            $this->providerAssignments[$index],
            $this->providerAssignments[$index - 1],
        ];
    }

    public function moveProviderDown(int $index): void
    {
        if (! isset($this->providerAssignments[$index], $this->providerAssignments[$index + 1])) {
            return;
        }

        [$this->providerAssignments[$index], $this->providerAssignments[$index + 1]] = [
            $this->providerAssignments[$index + 1],
            $this->providerAssignments[$index],
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * @return array<string, Employee>
     */
    #[Computed]
    public function employeesById(): array
    {
        return $this->employees->keyBy('id')->all();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function availableEmployees(): Collection
    {
        $taken = collect($this->responsibles)->pluck('employee_id')->all();
        $needle = mb_strtolower(trim($this->employeeSearch));

        return $this->employees
            ->reject(fn (Employee $e) => in_array($e->id, $taken, true))
            ->filter(function (Employee $e) use ($needle) {
                if ($needle === '') {
                    return true;
                }

                $emailLocal = $e->email ? mb_strstr($e->email, '@', true) : '';
                $hay = mb_strtolower($e->first_name.' '.$e->last_name.' '.$e->position.' '.($e->department?->name ?? '').' '.$emailLocal);

                return str_contains($hay, $needle);
            })
            ->values();
    }

    public function addResponsibleById(string $id): void
    {
        $already = collect($this->responsibles)->pluck('employee_id')->contains($id);
        if ($already) {
            return;
        }

        if (! array_key_exists($id, $this->employeesById)) {
            return;
        }

        $this->responsibles[] = [
            'employee_id' => $id,
            'ownership_kind' => '',
            'is_deputy' => false,
            'note' => '',
        ];

        $this->employeeSearch = '';
    }

    public function removeResponsible(int $index): void
    {
        if (! isset($this->responsibles[$index])) {
            return;
        }

        unset($this->responsibles[$index]);
        $this->responsibles = array_values($this->responsibles);
    }

    public function moveResponsibleUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->responsibles[$index], $this->responsibles[$index - 1])) {
            return;
        }

        [$this->responsibles[$index - 1], $this->responsibles[$index]] = [
            $this->responsibles[$index],
            $this->responsibles[$index - 1],
        ];
    }

    public function moveResponsibleDown(int $index): void
    {
        if (! isset($this->responsibles[$index], $this->responsibles[$index + 1])) {
            return;
        }

        [$this->responsibles[$index], $this->responsibles[$index + 1]] = [
            $this->responsibles[$index + 1],
            $this->responsibles[$index],
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::with('employees')->orderBy('sort')->orderBy('name')->get();
    }

    /**
     * @return array<string, Role>
     */
    #[Computed]
    public function rolesById(): array
    {
        return $this->roles->keyBy('id')->all();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function availableRoles(): Collection
    {
        $taken = collect($this->roleAssignments)->pluck('role_id')->all();
        $needle = mb_strtolower(trim($this->roleSearch));

        return $this->roles
            ->reject(fn (Role $r) => in_array($r->id, $taken, true))
            ->filter(function (Role $r) use ($needle) {
                if ($needle === '') {
                    return true;
                }

                $hay = mb_strtolower($r->name.' '.$r->description);

                return str_contains($hay, $needle);
            })
            ->values();
    }

    public function addRoleById(string $id): void
    {
        if (collect($this->roleAssignments)->pluck('role_id')->contains($id)) {
            return;
        }
        if (! array_key_exists($id, $this->rolesById)) {
            return;
        }

        $this->roleAssignments[] = [
            'role_id' => $id,
            'ownership_kind' => '',
            'is_deputy' => false,
            'note' => '',
        ];
        $this->roleSearch = '';
    }

    public function removeRole(int $index): void
    {
        if (! isset($this->roleAssignments[$index])) {
            return;
        }
        unset($this->roleAssignments[$index]);
        $this->roleAssignments = array_values($this->roleAssignments);
    }

    public function moveRoleUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->roleAssignments[$index], $this->roleAssignments[$index - 1])) {
            return;
        }

        [$this->roleAssignments[$index - 1], $this->roleAssignments[$index]] = [
            $this->roleAssignments[$index],
            $this->roleAssignments[$index - 1],
        ];
    }

    public function moveRoleDown(int $index): void
    {
        if (! isset($this->roleAssignments[$index], $this->roleAssignments[$index + 1])) {
            return;
        }

        [$this->roleAssignments[$index], $this->roleAssignments[$index + 1]] = [
            $this->roleAssignments[$index + 1],
            $this->roleAssignments[$index],
        ];
    }

    /**
     * @return Collection<int, System>
     */
    #[Computed]
    public function dependencyCandidates(): Collection
    {
        $all = System::orderBy('name')->get();

        if ($this->system === null) {
            return $all;
        }

        $forbidden = $this->descendantIds($this->system->id);
        $forbidden[$this->system->id] = true;

        return $all->reject(fn (System $s) => isset($forbidden[$s->id]))->values();
    }

    /**
     * @return array<string, System>
     */
    #[Computed]
    public function dependencyCandidatesById(): array
    {
        return $this->dependencyCandidates->keyBy('id')->all();
    }

    /**
     * @return Collection<int, System>
     */
    #[Computed]
    public function availableDependencies(): Collection
    {
        $taken = collect($this->dependencyAssignments)->pluck('dependency_id')->all();
        $needle = mb_strtolower(trim($this->dependencySearch));

        return $this->dependencyCandidates
            ->reject(fn (System $s) => in_array($s->id, $taken, true))
            ->filter(function (System $s) use ($needle) {
                if ($needle === '') {
                    return true;
                }

                $hay = mb_strtolower($s->name.' '.$s->category->label().' '.$s->description);

                return str_contains($hay, $needle);
            })
            ->values();
    }

    public function addDependencyById(string $id): void
    {
        $already = collect($this->dependencyAssignments)->pluck('dependency_id')->contains($id);
        if ($already) {
            return;
        }

        if (! array_key_exists($id, $this->dependencyCandidatesById)) {
            return;
        }

        $this->dependencyAssignments[] = [
            'dependency_id' => $id,
            'note' => '',
        ];

        $this->dependencySearch = '';
    }

    public function removeDependency(int $index): void
    {
        if (! isset($this->dependencyAssignments[$index])) {
            return;
        }

        unset($this->dependencyAssignments[$index]);
        $this->dependencyAssignments = array_values($this->dependencyAssignments);
    }

    public function moveDependencyUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->dependencyAssignments[$index], $this->dependencyAssignments[$index - 1])) {
            return;
        }

        [$this->dependencyAssignments[$index - 1], $this->dependencyAssignments[$index]] = [
            $this->dependencyAssignments[$index],
            $this->dependencyAssignments[$index - 1],
        ];
    }

    public function moveDependencyDown(int $index): void
    {
        if (! isset($this->dependencyAssignments[$index], $this->dependencyAssignments[$index + 1])) {
            return;
        }

        [$this->dependencyAssignments[$index], $this->dependencyAssignments[$index + 1]] = [
            $this->dependencyAssignments[$index + 1],
            $this->dependencyAssignments[$index],
        ];
    }

    /**
     * @return array<string, true>
     */
    protected function descendantIds(string $systemId): array
    {
        $dependentsByParent = [];
        foreach (
            DB::table('system_dependencies')
                ->select('system_id', 'depends_on_system_id')
                ->get() as $row
        ) {
            $dependentsByParent[$row->depends_on_system_id][] = $row->system_id;
        }

        $visited = [];
        $stack = [$systemId];
        while ($stack) {
            $cur = array_pop($stack);
            foreach ($dependentsByParent[$cur] ?? [] as $child) {
                if (isset($visited[$child])) {
                    continue;
                }
                $visited[$child] = true;
                $stack[] = $child;
            }
        }

        return $visited;
    }

    public function save()
    {
        $validDurations = array_keys(Duration::OPTIONS);
        $ownershipValues = implode(',', array_column(\App\Enums\SystemOwnership::cases(), 'value'));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'fallback_process' => ['nullable', 'string', 'max:2000'],
            'runbook_reference' => ['nullable', 'string', 'max:255'],
            'emergency_level_id' => ['nullable', 'uuid', 'exists:emergency_levels,id'],
            'category' => ['required', 'in:'.collect(SystemCategory::cases())->pluck('value')->implode(',')],
            'system_type' => ['nullable', 'in:'.collect(SystemType::cases())->pluck('value')->implode(',')],
            'system_priority_id' => ['nullable', 'uuid', 'exists:system_priorities,id'],
            'rto_minutes' => ['nullable', 'integer', 'in:'.implode(',', $validDurations)],
            'rpo_minutes' => ['nullable', 'integer', 'in:'.implode(',', $validDurations)],
            'downtime_cost_per_hour' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'monitoring_keys_text' => ['nullable', 'string', 'max:2000'],
            'providerAssignments' => ['array'],
            'providerAssignments.*.provider_id' => ['required', 'uuid', 'exists:service_providers,id'],
            'providerAssignments.*.ownership_kind' => ['nullable', 'in:'.$ownershipValues],
            'providerAssignments.*.is_deputy' => ['nullable', 'boolean'],
            'providerAssignments.*.note' => ['nullable', 'string', 'max:500'],
            'responsibles' => ['array'],
            'responsibles.*.employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'responsibles.*.ownership_kind' => ['nullable', 'in:'.$ownershipValues],
            'responsibles.*.is_deputy' => ['nullable', 'boolean'],
            'responsibles.*.note' => ['nullable', 'string', 'max:500'],
            'dependencyAssignments' => ['array'],
            'dependencyAssignments.*.dependency_id' => ['required', 'uuid', 'exists:systems,id'],
            'dependencyAssignments.*.note' => ['nullable', 'string', 'max:500'],
            'roleAssignments' => ['array'],
            'roleAssignments.*.role_id' => ['required', 'uuid', 'exists:roles,id'],
            'roleAssignments.*.ownership_kind' => ['nullable', 'in:'.$ownershipValues],
            'roleAssignments.*.is_deputy' => ['nullable', 'boolean'],
            'roleAssignments.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $providerAssignments = $validated['providerAssignments'] ?? [];
        $responsibles = $validated['responsibles'] ?? [];
        $dependencyAssignments = $validated['dependencyAssignments'] ?? [];
        $roleAssignments = $validated['roleAssignments'] ?? [];

        $monitoringKeys = collect(preg_split('/[\r\n,]+/', (string) ($validated['monitoring_keys_text'] ?? '')))
            ->map(fn ($k) => trim((string) $k))
            ->filter()
            ->values()
            ->all();
        $validated['monitoring_keys'] = $monitoringKeys === [] ? null : $monitoringKeys;

        $validated['system_type'] = ($validated['system_type'] ?? '') !== '' ? $validated['system_type'] : null;

        unset($validated['providerAssignments'], $validated['responsibles'], $validated['dependencyAssignments'], $validated['roleAssignments'], $validated['monitoring_keys_text']);

        if ($this->system !== null) {
            $forbidden = $this->descendantIds($this->system->id);
            $forbidden[$this->system->id] = true;

            $dependencyAssignments = array_values(array_filter(
                $dependencyAssignments,
                fn (array $row) => ! isset($forbidden[$row['dependency_id']]),
            ));
        }

        $system = $this->system
            ? tap($this->system)->update($validated)
            : System::create($validated);

        $employeeSync = [];
        foreach (array_values($responsibles) as $index => $row) {
            $employeeSync[$row['employee_id']] = [
                'sort' => $index,
                'ownership_kind' => ($row['ownership_kind'] ?? '') !== '' ? $row['ownership_kind'] : null,
                'is_deputy' => (bool) ($row['is_deputy'] ?? false),
                'note' => ($row['note'] ?? '') !== '' ? $row['note'] : null,
            ];
        }

        $providerSync = [];
        foreach (array_values($providerAssignments) as $index => $row) {
            $providerSync[$row['provider_id']] = [
                'sort' => $index,
                'ownership_kind' => ($row['ownership_kind'] ?? '') !== '' ? $row['ownership_kind'] : null,
                'is_deputy' => (bool) ($row['is_deputy'] ?? false),
                'note' => ($row['note'] ?? '') !== '' ? $row['note'] : null,
            ];
        }

        $dependencySync = [];
        foreach (array_values($dependencyAssignments) as $index => $row) {
            $dependencySync[$row['dependency_id']] = [
                'sort' => $index,
                'note' => ($row['note'] ?? '') !== '' ? $row['note'] : null,
            ];
        }

        $roleSync = [];
        foreach (array_values($roleAssignments) as $index => $row) {
            $roleSync[$row['role_id']] = [
                'sort' => $index,
                'ownership_kind' => ($row['ownership_kind'] ?? '') !== '' ? $row['ownership_kind'] : null,
                'is_deputy' => (bool) ($row['is_deputy'] ?? false),
                'note' => ($row['note'] ?? '') !== '' ? $row['note'] : null,
            ];
        }

        AssignmentSync::sync($system, $system->serviceProviders(), $providerSync);
        AssignmentSync::sync($system, $system->employees(), $employeeSync);
        AssignmentSync::sync($system, $system->roles(), $roleSync);
        $system->dependencies()->sync($dependencySync);

        Flux::toast(variant: 'success', text: __('System gespeichert.'));

        return redirect()->route('systems.index');
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('systems.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Systeme') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="save" class="space-y-5 p-6">
            <div>
                <flux:heading size="xl">
                    {{ $system ? __('System bearbeiten') : __('Neues System anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Was ist das System, wofür wird es gebraucht und wie wichtig ist es?') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Warenwirtschaft, Telefonanlage" />

            <flux:textarea
                wire:model="description"
                :label="__('Beschreibung')"
                rows="3"
                placeholder="Wofür wird dieses System genutzt?"
            />

            <flux:textarea
                wire:model="fallback_process"
                :label="__('Notbetrieb / Ersatzprozess')"
                rows="3"
                placeholder="Wie läuft der Betrieb weiter, wenn dieses System ausfällt?"
            />

            <flux:input
                wire:model="runbook_reference"
                :label="__('Runbook-Verweis')"
                type="text"
                placeholder="z. B. Wiki-Link, Dateipfad oder Dokumenttitel"
            />

            <flux:field>
                <flux:label>{{ __('Wiederanlauf-Stufe') }}</flux:label>
                <flux:description>
                    {{ __('Ab welcher Notfall-Stufe muss dieses System wieder verfügbar sein? Karte anklicken zum Auswählen.') }}
                </flux:description>

                @if ($this->emergencyLevels->isEmpty())
                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        {{ __('Noch keine Notfall-Stufen definiert.') }}
                        <flux:link :href="route('emergency-levels.index')" wire:navigate>{{ __('Jetzt anlegen') }}</flux:link>
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($this->emergencyLevels as $level)
                            @php($selected = $emergency_level_id === $level->id)
                            <label
                                wire:key="level-{{ $level->id }}"
                                class="group relative flex cursor-pointer flex-col gap-2 rounded-lg border p-4 transition
                                    {{ $selected
                                        ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-500 dark:border-teal-500 dark:bg-teal-950/40'
                                        : 'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500' }}"
                            >
                                <input
                                    type="radio"
                                    wire:model.live="emergency_level_id"
                                    value="{{ $level->id }}"
                                    class="sr-only"
                                />
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold
                                            {{ $selected
                                                ? 'border-teal-600 bg-teal-600 text-white'
                                                : 'border-zinc-300 text-zinc-500 dark:border-zinc-600 dark:text-zinc-400' }}">
                                            {{ $level->sort }}
                                        </span>
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $level->name }}</span>
                                    </div>
                                    @if ($selected)
                                        <flux:icon.check-circle class="h-5 w-5 text-teal-600 dark:text-teal-400" />
                                    @endif
                                </div>
                                @if ($level->description)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $level->description }}</p>
                                @endif
                                @if ($level->reaction)
                                    <div class="mt-1 rounded-md bg-zinc-50 px-2.5 py-1.5 text-xs text-zinc-600 dark:bg-zinc-950/50 dark:text-zinc-400">
                                        <span class="font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('Reaktion') }}:</span>
                                        {{ $level->reaction }}
                                    </div>
                                @endif
                            </label>
                        @endforeach

                        @php($none = $emergency_level_id === null || $emergency_level_id === '')
                        <label
                            class="group relative flex cursor-pointer flex-col gap-2 rounded-lg border border-dashed p-4 transition sm:col-span-2
                                {{ $none
                                    ? 'border-zinc-500 bg-zinc-50 ring-2 ring-zinc-400 dark:border-zinc-400 dark:bg-zinc-950/40'
                                    : 'border-zinc-300 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500' }}"
                        >
                            <input
                                type="radio"
                                wire:model.live="emergency_level_id"
                                value=""
                                class="sr-only"
                            />
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Keine Zuordnung') }}
                                </span>
                                @if ($none)
                                    <flux:icon.check-circle class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                                @endif
                            </div>
                        </label>
                    </div>
                @endif
            </flux:field>

            <flux:select wire:model="category" :label="__('Kategorie')" required>
                @foreach (\App\Enums\SystemCategory::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="system_type" :label="__('Systemtyp')">
                <flux:select.option value="">{{ __('Nicht definiert') }}</flux:select.option>
                @foreach (\App\Enums\SystemType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:field>
                <flux:label>{{ __('Ausfallkosten pro Stunde') }}</flux:label>
                <flux:description>
                    {{ __('Geschätzter Umsatz- oder Produktivitätsverlust, wenn dieses System eine Stunde lang ausfällt. In Euro, nur ganze Zahlen.') }}
                </flux:description>
                <flux:input wire:model="downtime_cost_per_hour" type="number" min="0" step="1" placeholder="z. B. 250" />
            </flux:field>

            @if (config('features.monitoring_api'))
                <flux:field>
                    <flux:label>{{ __('Monitoring-Hostnamen / Labels') }}</flux:label>
                    <flux:description>
                        {{ __('Eine Bezeichnung pro Zeile (oder kommasepariert). Wenn ein Zabbix-/Prometheus-Alarm einen dieser Begriffe in Host oder Subject trägt, wird er automatisch diesem System zugeordnet. Beispiel: srv-prod-01, warenwirtschaft.local') }}
                    </flux:description>
                    <flux:textarea wire:model="monitoring_keys_text" rows="3" placeholder="srv-prod-01&#10;warenwirtschaft.local" />
                </flux:field>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Max. Ausfallzeit') }}</flux:label>
                    <flux:description>
                        {{ __('Wie lange darf das System maximal ausfallen, bevor der Betrieb ernsthaft leidet?') }}
                        <span class="text-zinc-400">· {{ __('Fachbegriff: RTO') }}</span>
                    </flux:description>
                    <flux:select wire:model="rto_minutes">
                        <flux:select.option value="">{{ __('Nicht definiert') }}</flux:select.option>
                        @foreach (\App\Support\Duration::options() as $opt)
                            <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Max. Datenverlust') }}</flux:label>
                    <flux:description>
                        {{ __('Wieviel Datenverlust ist im Notfall verkraftbar?') }}
                        <span class="text-zinc-400">· {{ __('Fachbegriff: RPO') }}</span>
                    </flux:description>
                    <flux:select wire:model="rpo_minutes">
                        <flux:select.option value="">{{ __('Nicht definiert') }}</flux:select.option>
                        @foreach (\App\Support\Duration::options() as $opt)
                            <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            @if ($this->employees->isNotEmpty() || $this->providers->isNotEmpty() || $this->roles->isNotEmpty() || $this->dependencyCandidates->isNotEmpty())
                @php($defaultTab = $this->employees->isNotEmpty() ? 'employees' : ($this->roles->isNotEmpty() ? 'roles' : ($this->providers->isNotEmpty() ? 'providers' : 'dependencies')))
                <div x-data="{ tab: '{{ $defaultTab }}' }" class="space-y-3">
                    <div role="tablist" class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
                        @if ($this->employees->isNotEmpty())
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="tab === 'employees'"
                                @click="tab = 'employees'"
                                :class="tab === 'employees'
                                    ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                                    : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                            >
                                <flux:icon.user class="h-4 w-4" />
                                {{ __('Verantwortliche Mitarbeiter') }}
                                @if (count($responsibles) > 0)
                                    <flux:badge color="teal" size="sm">{{ count($responsibles) }}</flux:badge>
                                @endif
                            </button>
                        @endif
                        @if ($this->providers->isNotEmpty())
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="tab === 'providers'"
                                @click="tab = 'providers'"
                                :class="tab === 'providers'
                                    ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                                    : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                            >
                                <flux:icon.wrench-screwdriver class="h-4 w-4" />
                                {{ __('Dienstleister') }}
                                @if (count($providerAssignments) > 0)
                                    <flux:badge color="teal" size="sm">{{ count($providerAssignments) }}</flux:badge>
                                @endif
                            </button>
                        @endif
                        @if ($this->roles->isNotEmpty())
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="tab === 'roles'"
                                @click="tab = 'roles'"
                                :class="tab === 'roles'
                                    ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                                    : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                            >
                                <flux:icon.identification class="h-4 w-4" />
                                {{ __('Rollen') }}
                                @if (count($roleAssignments) > 0)
                                    <flux:badge color="teal" size="sm">{{ count($roleAssignments) }}</flux:badge>
                                @endif
                            </button>
                        @endif
                        @if ($this->dependencyCandidates->isNotEmpty())
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="tab === 'dependencies'"
                                @click="tab = 'dependencies'"
                                :class="tab === 'dependencies'
                                    ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                                    : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                            >
                                <flux:icon.link class="h-4 w-4" />
                                {{ __('Abhängigkeiten') }}
                                @if (count($dependencyAssignments) > 0)
                                    <flux:badge color="teal" size="sm">{{ count($dependencyAssignments) }}</flux:badge>
                                @endif
                            </button>
                        @endif
                    </div>

                    @if ($this->employees->isNotEmpty())
                        <div x-show="tab === 'employees'" x-cloak class="space-y-4">
                            <flux:description>
                                {{ __('Zuständigkeiten auf System-Ebene (Ownership). Rufreihenfolge: zuerst Position 1, dann 2 etc. Für konkrete Aufgaben wird RACI separat in der Aufgaben-Sektion vergeben.') }}
                            </flux:description>

                            @php($remaining = $this->employees->count() - count($responsibles))

                            @if ($remaining > 0)
                                <div
                                    x-data="{ open: false }"
                                    @focusin="open = true"
                                    @click.outside="open = false"
                                    class="relative"
                                >
                                    <flux:field>
                                        <flux:label>{{ __('Mitarbeiter hinzufügen') }}</flux:label>
                                        <flux:input
                                            type="search"
                                            wire:model.live.debounce.150ms="employeeSearch"
                                            autocomplete="off"
                                            icon="magnifying-glass"
                                            :placeholder="__('Name, Position oder Abteilung tippen …')"
                                        />
                                    </flux:field>

                                    <div
                                        x-show="open"
                                        x-cloak
                                        class="absolute left-0 right-0 z-20 mt-1 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        @forelse ($this->availableEmployees as $employee)
                                            <button
                                                type="button"
                                                wire:click="addResponsibleById('{{ $employee->id }}')"
                                                class="flex w-full items-center gap-3 border-b border-zinc-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800"
                                            >
                                                <flux:icon.user class="h-4 w-4 text-zinc-400" />
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate font-medium">{{ $employee->fullName() }}</div>
                                                    @if ($employee->position || $employee->department?->name)
                                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ $employee->position }}@if ($employee->position && $employee->department?->name) · @endif{{ $employee->department?->name }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <flux:icon.plus class="h-4 w-4 text-zinc-400" />
                                            </button>
                                        @empty
                                            <div class="px-3 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ __('Keine passenden Mitarbeiter.') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                            @php($employeeLookup = $this->employees->mapWithKeys(fn ($e) => [
                                $e->id => [
                                    'name' => $e->fullName(),
                                    'position' => (string) $e->position,
                                    'mobile' => (string) $e->mobile_phone,
                                ],
                            ])->all())

                            <div
                                wire:key="responsibles-list"
                                x-data="{
                                    rows: $wire.entangle('responsibles'),
                                    directory: @js((object) $employeeLookup),
                                    moveUp(i) {
                                        if (i <= 0) return;
                                        const copy = [...this.rows];
                                        [copy[i - 1], copy[i]] = [copy[i], copy[i - 1]];
                                        this.rows = copy;
                                    },
                                    moveDown(i) {
                                        if (i >= this.rows.length - 1) return;
                                        const copy = [...this.rows];
                                        [copy[i], copy[i + 1]] = [copy[i + 1], copy[i]];
                                        this.rows = copy;
                                    },
                                    remove(i) {
                                        this.rows = this.rows.filter((_, idx) => idx !== i);
                                    },
                                    meta(id) {
                                        return this.directory[id] || { name: 'Unbekannter Mitarbeiter', position: '', mobile: '' };
                                    },
                                }"
                            >
                                <template x-if="rows.length === 0">
                                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        {{ __('Noch keine Verantwortlichen zugewiesen.') }}
                                    </div>
                                </template>

                                <ol x-show="rows.length > 0" class="space-y-2">
                                    <template x-for="(row, index) in rows" :key="row.employee_id">
                                        <li class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                            <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100"
                                                  x-text="index + 1"></span>

                                            <div class="min-w-0 flex-1 space-y-1.5">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="font-medium" x-text="meta(row.employee_id).name"></span>
                                                    <span x-show="meta(row.employee_id).position" class="text-xs text-zinc-500 dark:text-zinc-400" x-text="'· ' + meta(row.employee_id).position"></span>
                                                    <span x-show="meta(row.employee_id).mobile" class="text-xs text-zinc-500 dark:text-zinc-400" x-text="'· ' + meta(row.employee_id).mobile"></span>
                                                </div>
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                    <select
                                                        x-model="row.ownership_kind"
                                                        class="w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 sm:w-64 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                                    >
                                                        <option value="">{{ __('Zuständigkeit wählen …') }}</option>
                                                        @foreach (\App\Enums\SystemOwnership::ordered() as $o)
                                                            <option value="{{ $o->value }}">{{ $o->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300">
                                                        <input type="checkbox" x-model="row.is_deputy" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                                                        {{ __('Vertretung') }}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        x-model="row.note"
                                                        @keydown.enter.prevent
                                                        placeholder="{{ __('Notiz (optional)') }}"
                                                        class="block w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-sm shadow-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                                    />
                                                </div>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-1">
                                                <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-up class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="moveDown(index)" :disabled="index === rows.length - 1"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-down class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="remove(index)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                                    <flux:icon.x-mark class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </div>
                    @endif

                    @if ($this->providers->isNotEmpty())
                        <div x-show="tab === 'providers'" x-cloak class="space-y-4">
                            <flux:description>
                                {{ __('Zuständigkeiten auf System-Ebene und Eskalationskette: zuerst Dienstleister 1, dann 2 etc. Für konkrete Aufgaben wird RACI separat in der Aufgaben-Sektion vergeben.') }}
                            </flux:description>

                            @php($providersRemaining = $this->providers->count() - count($providerAssignments))

                            @if ($providersRemaining > 0)
                                <div
                                    x-data="{ open: false }"
                                    @focusin="open = true"
                                    @click.outside="open = false"
                                    class="relative"
                                >
                                    <flux:field>
                                        <flux:label>{{ __('Dienstleister hinzufügen') }}</flux:label>
                                        <flux:input
                                            type="search"
                                            wire:model.live.debounce.150ms="providerSearch"
                                            autocomplete="off"
                                            icon="magnifying-glass"
                                            :placeholder="__('Name, Hotline oder Ansprechpartner tippen …')"
                                        />
                                    </flux:field>

                                    <div
                                        x-show="open"
                                        x-cloak
                                        class="absolute left-0 right-0 z-20 mt-1 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        @forelse ($this->availableProviders as $provider)
                                            <button
                                                type="button"
                                                wire:click="addProviderById('{{ $provider->id }}')"
                                                class="flex w-full items-center gap-3 border-b border-zinc-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800"
                                            >
                                                <flux:icon.wrench-screwdriver class="h-4 w-4 text-zinc-400" />
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate font-medium">{{ $provider->name }}</div>
                                                    @if ($provider->hotline || $provider->contact_name)
                                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ $provider->hotline }}@if ($provider->hotline && $provider->contact_name) · @endif{{ $provider->contact_name }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <flux:icon.plus class="h-4 w-4 text-zinc-400" />
                                            </button>
                                        @empty
                                            <div class="px-3 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ __('Keine passenden Dienstleister.') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                            @php($providerLookup = $this->providers->mapWithKeys(fn ($p) => [
                                $p->id => [
                                    'name' => $p->name,
                                    'hotline' => (string) $p->hotline,
                                    'contact' => (string) $p->contact_name,
                                ],
                            ])->all())

                            <div
                                wire:key="provider-list"
                                x-data="{
                                    rows: $wire.entangle('providerAssignments'),
                                    directory: @js((object) $providerLookup),
                                    moveUp(i) {
                                        if (i <= 0) return;
                                        const copy = [...this.rows];
                                        [copy[i - 1], copy[i]] = [copy[i], copy[i - 1]];
                                        this.rows = copy;
                                    },
                                    moveDown(i) {
                                        if (i >= this.rows.length - 1) return;
                                        const copy = [...this.rows];
                                        [copy[i], copy[i + 1]] = [copy[i + 1], copy[i]];
                                        this.rows = copy;
                                    },
                                    remove(i) {
                                        this.rows = this.rows.filter((_, idx) => idx !== i);
                                    },
                                    meta(id) {
                                        return this.directory[id] || { name: 'Unbekannter Dienstleister', hotline: '', contact: '' };
                                    },
                                }"
                            >
                                <template x-if="rows.length === 0">
                                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        {{ __('Noch keine Dienstleister zugewiesen.') }}
                                    </div>
                                </template>

                                <ol x-show="rows.length > 0" class="space-y-2">
                                    <template x-for="(row, index) in rows" :key="row.provider_id">
                                        <li class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                            <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100"
                                                  x-text="index + 1"></span>

                                            <div class="min-w-0 flex-1 space-y-1.5">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="font-medium" x-text="meta(row.provider_id).name"></span>
                                                    <span x-show="meta(row.provider_id).hotline" class="text-xs text-zinc-500 dark:text-zinc-400" x-text="'· ' + meta(row.provider_id).hotline"></span>
                                                    <span x-show="meta(row.provider_id).contact" class="text-xs text-zinc-500 dark:text-zinc-400" x-text="'· ' + meta(row.provider_id).contact"></span>
                                                </div>
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                    <select
                                                        x-model="row.ownership_kind"
                                                        class="w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 sm:w-64 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                                    >
                                                        <option value="">{{ __('Zuständigkeit wählen …') }}</option>
                                                        @foreach (\App\Enums\SystemOwnership::ordered() as $o)
                                                            <option value="{{ $o->value }}">{{ $o->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300">
                                                        <input type="checkbox" x-model="row.is_deputy" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                                                        {{ __('Vertretung') }}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        x-model="row.note"
                                                        @keydown.enter.prevent
                                                        placeholder="{{ __('Notiz (optional, z. B. „SLA nur Mo–Fr 8–17")') }}"
                                                        class="block w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-sm shadow-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                                    />
                                                </div>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-1">
                                                <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-up class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="moveDown(index)" :disabled="index === rows.length - 1"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-down class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="remove(index)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                                    <flux:icon.x-mark class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </div>
                    @endif

                    @if ($this->dependencyCandidates->isNotEmpty())
                        <div x-show="tab === 'dependencies'" x-cloak class="space-y-4">
                            <flux:description>
                                {{ __('Welche anderen Systeme müssen bereits laufen, damit dieses hier funktioniert? Reihenfolge beeinflusst die Darstellung; die Startreihenfolge im Ernstfall wird aus dem Abhängigkeitsgraph berechnet.') }}
                            </flux:description>

                            @php($depsRemaining = $this->dependencyCandidates->count() - count($dependencyAssignments))

                            @if ($depsRemaining > 0)
                                <div
                                    x-data="{ open: false }"
                                    @focusin="open = true"
                                    @click.outside="open = false"
                                    class="relative"
                                >
                                    <flux:field>
                                        <flux:label>{{ __('Abhängigkeit hinzufügen') }}</flux:label>
                                        <flux:input
                                            type="search"
                                            wire:model.live.debounce.150ms="dependencySearch"
                                            autocomplete="off"
                                            icon="magnifying-glass"
                                            :placeholder="__('Systemname, Kategorie oder Beschreibung tippen …')"
                                        />
                                    </flux:field>

                                    <div
                                        x-show="open"
                                        x-cloak
                                        class="absolute left-0 right-0 z-20 mt-1 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        @forelse ($this->availableDependencies as $candidate)
                                            <button
                                                type="button"
                                                wire:click="addDependencyById('{{ $candidate->id }}')"
                                                class="flex w-full items-center gap-3 border-b border-zinc-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800"
                                            >
                                                <flux:icon.server-stack class="h-4 w-4 text-zinc-400" />
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate font-medium">{{ $candidate->name }}</div>
                                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ $candidate->category->label() }}
                                                    </div>
                                                </div>
                                                <flux:icon.plus class="h-4 w-4 text-zinc-400" />
                                            </button>
                                        @empty
                                            <div class="px-3 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ __('Keine passenden Systeme.') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                            @php($dependencyLookup = $this->dependencyCandidates->mapWithKeys(fn ($s) => [
                                $s->id => [
                                    'name' => $s->name,
                                    'category' => $s->category->label(),
                                ],
                            ])->all())

                            <div
                                wire:key="dependency-list"
                                x-data="{
                                    rows: $wire.entangle('dependencyAssignments'),
                                    directory: @js((object) $dependencyLookup),
                                    moveUp(i) {
                                        if (i <= 0) return;
                                        const copy = [...this.rows];
                                        [copy[i - 1], copy[i]] = [copy[i], copy[i - 1]];
                                        this.rows = copy;
                                    },
                                    moveDown(i) {
                                        if (i >= this.rows.length - 1) return;
                                        const copy = [...this.rows];
                                        [copy[i], copy[i + 1]] = [copy[i + 1], copy[i]];
                                        this.rows = copy;
                                    },
                                    remove(i) {
                                        this.rows = this.rows.filter((_, idx) => idx !== i);
                                    },
                                    meta(id) {
                                        return this.directory[id] || { name: 'Unbekanntes System', category: '' };
                                    },
                                }"
                            >
                                <template x-if="rows.length === 0">
                                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        {{ __('Keine Abhängigkeiten definiert.') }}
                                    </div>
                                </template>

                                <ol x-show="rows.length > 0" class="space-y-2">
                                    <template x-for="(row, index) in rows" :key="row.dependency_id">
                                        <li class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                            <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100"
                                                  x-text="index + 1"></span>

                                            <div class="min-w-0 flex-1 space-y-1.5">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="font-medium" x-text="meta(row.dependency_id).name"></span>
                                                    <span x-show="meta(row.dependency_id).category" class="text-xs text-zinc-500 dark:text-zinc-400" x-text="'· ' + meta(row.dependency_id).category"></span>
                                                </div>
                                                <input
                                                    type="text"
                                                    x-model="row.note"
                                                    @keydown.enter.prevent
                                                    placeholder="{{ __('Notiz (optional, z. B. „Nur Schreibzugriff nötig")') }}"
                                                    class="block w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-sm shadow-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                                />
                                            </div>

                                            <div class="flex shrink-0 items-center gap-1">
                                                <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-up class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="moveDown(index)" :disabled="index === rows.length - 1"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-down class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="remove(index)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                                    <flux:icon.x-mark class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </div>
                    @endif

                    @if ($this->roles->isNotEmpty())
                        <div x-show="tab === 'roles'" x-cloak class="space-y-4">
                            <flux:description>
                                {{ __('Zuweisung über Rollen statt einzelner Personen. Mitglieder der Rolle werden automatisch unter dem Eintrag aufgelistet — Änderungen an der Rollenmitgliedschaft wirken sich direkt aus.') }}
                            </flux:description>

                            @php($rolesRemaining = $this->roles->count() - count($roleAssignments))

                            @if ($rolesRemaining > 0)
                                <div
                                    x-data="{ open: false }"
                                    @focusin="open = true"
                                    @click.outside="open = false"
                                    class="relative"
                                >
                                    <flux:field>
                                        <flux:label>{{ __('Rolle hinzufügen') }}</flux:label>
                                        <flux:input
                                            type="search"
                                            wire:model.live.debounce.150ms="roleSearch"
                                            autocomplete="off"
                                            icon="magnifying-glass"
                                            :placeholder="__('Rollenname tippen …')"
                                        />
                                    </flux:field>

                                    <div
                                        x-show="open"
                                        x-cloak
                                        class="absolute left-0 right-0 z-20 mt-1 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        @forelse ($this->availableRoles as $role)
                                            <button
                                                type="button"
                                                wire:click="addRoleById('{{ $role->id }}')"
                                                class="flex w-full items-center gap-3 border-b border-zinc-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800"
                                            >
                                                <flux:icon.identification class="h-4 w-4 text-zinc-400" />
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate font-medium">{{ $role->name }}</div>
                                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ trans_choice(':count Mitarbeitender|:count Mitarbeitende', $role->employees->count(), ['count' => $role->employees->count()]) }}
                                                        @if ($role->employees->isNotEmpty())
                                                            · {{ $role->employees->take(3)->map(fn ($e) => $e->fullName())->join(', ') }}@if ($role->employees->count() > 3) · …@endif
                                                        @endif
                                                    </div>
                                                </div>
                                                <flux:icon.plus class="h-4 w-4 text-zinc-400" />
                                            </button>
                                        @empty
                                            <div class="px-3 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ __('Keine passenden Rollen.') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                            @php($roleLookup = $this->roles->mapWithKeys(fn ($r) => [
                                $r->id => [
                                    'name' => $r->name,
                                    'description' => (string) $r->description,
                                    'members' => $r->employees->map(fn ($e) => $e->fullName())->all(),
                                ],
                            ])->all())

                            <div
                                wire:key="roles-list"
                                x-data="{
                                    rows: $wire.entangle('roleAssignments'),
                                    directory: @js((object) $roleLookup),
                                    moveUp(i) {
                                        if (i <= 0) return;
                                        const copy = [...this.rows];
                                        [copy[i - 1], copy[i]] = [copy[i], copy[i - 1]];
                                        this.rows = copy;
                                    },
                                    moveDown(i) {
                                        if (i >= this.rows.length - 1) return;
                                        const copy = [...this.rows];
                                        [copy[i], copy[i + 1]] = [copy[i + 1], copy[i]];
                                        this.rows = copy;
                                    },
                                    remove(i) {
                                        this.rows = this.rows.filter((_, idx) => idx !== i);
                                    },
                                    meta(id) {
                                        return this.directory[id] || { name: 'Unbekannte Rolle', description: '', members: [] };
                                    },
                                }"
                            >
                                <template x-if="rows.length === 0">
                                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        {{ __('Noch keine Rollen zugewiesen.') }}
                                    </div>
                                </template>

                                <ol x-show="rows.length > 0" class="space-y-2">
                                    <template x-for="(row, index) in rows" :key="row.role_id">
                                        <li class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                            <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100"
                                                  x-text="index + 1"></span>

                                            <div class="min-w-0 flex-1 space-y-1.5">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <flux:icon.identification class="h-4 w-4 text-zinc-400" />
                                                    <span class="font-medium" x-text="meta(row.role_id).name"></span>
                                                    <span x-show="meta(row.role_id).description" class="text-xs text-zinc-500 dark:text-zinc-400" x-text="'· ' + meta(row.role_id).description"></span>
                                                </div>
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                    <select
                                                        x-model="row.ownership_kind"
                                                        class="w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 sm:w-64 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                                    >
                                                        <option value="">{{ __('Zuständigkeit wählen …') }}</option>
                                                        @foreach (\App\Enums\SystemOwnership::ordered() as $o)
                                                            <option value="{{ $o->value }}">{{ $o->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300">
                                                        <input type="checkbox" x-model="row.is_deputy" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                                                        {{ __('Vertretung') }}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        x-model="row.note"
                                                        @keydown.enter.prevent
                                                        placeholder="{{ __('Notiz (optional)') }}"
                                                        class="block w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-sm shadow-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                                    />
                                                </div>
                                                <template x-if="meta(row.role_id).members.length > 0">
                                                    <div class="flex flex-wrap gap-1.5 pt-1">
                                                        <template x-for="member in meta(row.role_id).members" :key="member">
                                                            <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                                <flux:icon.user class="h-3 w-3 text-zinc-400" />
                                                                <span x-text="member"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="meta(row.role_id).members.length === 0">
                                                    <div class="text-xs italic text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Rolle hat keine Mitglieder.') }}
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-1">
                                                <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-up class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="moveDown(index)" :disabled="index === rows.length - 1"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-40 dark:hover:bg-zinc-800">
                                                    <flux:icon.arrow-down class="h-4 w-4" />
                                                </button>
                                                <button type="button" @click="remove(index)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                                    <flux:icon.x-mark class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button variant="filled" :href="route('systems.index')" wire:navigate>
                    {{ __('Abbrechen') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $system ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </div>

    @if ($system)
        <div class="mt-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 p-6 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg">{{ __('Aufgaben') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Übersicht der Wartungs- und Vorbereitungs-Aufgaben zu diesem System. Anlegen, bearbeiten und löschen erfolgt in der Detail-Ansicht.') }}
                    </flux:subheading>
                </div>
                <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('systems.show', ['system' => $system->id])" wire:navigate>
                    {{ __('Aufgaben verwalten') }}
                </flux:button>
            </div>

            <div class="p-6">
                @if ($this->tasks->isEmpty())
                    <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        {{ __('Noch keine Aufgaben erfasst.') }}
                    </div>
                @else
                    <ul class="space-y-2">
                        @foreach ($this->tasks as $task)
                            @php($empGroups = $task->assignees->groupBy(fn ($e) => $e->pivot->raci_role ?? ''))
                            @php($provGroups = $task->providerAssignees->groupBy(fn ($p) => $p->pivot->raci_role ?? ''))
                            @php($accountableNames = $empGroups->get('A', collect())->map(fn ($e) => $e->fullName())->merge($provGroups->get('A', collect())->map(fn ($p) => $p->name)))
                            @php($responsibleNames = $empGroups->get('R', collect())->map(fn ($e) => $e->fullName())->merge($provGroups->get('R', collect())->map(fn ($p) => $p->name)))
                            <li wire:key="edit-task-{{ $task->id }}"
                                class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="mt-0.5 shrink-0">
                                    @if ($task->isDone())
                                        <flux:icon.check-circle class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                    @else
                                        <flux:icon.clock class="h-5 w-5 text-zinc-400" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                                        <span class="font-medium {{ $task->isDone() ? 'text-zinc-500 line-through dark:text-zinc-500' : '' }}">
                                            {{ $task->title }}
                                        </span>
                                        @if ($task->due_date)
                                            @if ($task->isOverdue())
                                                <flux:badge color="rose" size="sm" icon="clock">
                                                    {{ __('Überfällig') }}: {{ $task->due_date->format('d.m.Y') }}
                                                </flux:badge>
                                            @else
                                                <flux:badge :color="$task->isDone() ? 'zinc' : 'amber'" size="sm" icon="calendar-days">
                                                    {{ $task->due_date->format('d.m.Y') }}
                                                </flux:badge>
                                            @endif
                                        @endif
                                        @if ($task->isDone())
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __('Erledigt am') }} {{ $task->completed_at->format('d.m.Y') }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($task->description)
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400 {{ $task->isDone() ? 'line-through' : '' }}">
                                            {{ $task->description }}
                                        </div>
                                    @endif
                                    @if ($accountableNames->isNotEmpty() || $responsibleNames->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                            @if ($responsibleNames->isNotEmpty())
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="rounded bg-blue-100 px-1.5 py-0.5 font-bold text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">R</span>
                                                    {{ $responsibleNames->join(', ') }}
                                                </span>
                                            @endif
                                            @if ($accountableNames->isNotEmpty())
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 font-bold text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">A</span>
                                                    {{ $accountableNames->join(', ') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</section>
