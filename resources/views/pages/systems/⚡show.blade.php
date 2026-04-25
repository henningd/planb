<?php

use App\Enums\RaciRole;
use App\Models\Employee;
use App\Models\Role;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Support\AssignmentHistory;
use App\Support\AssignmentSync;
use App\Support\Duration;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('System')] class extends Component {
    public System $system;

    public string $newTaskTitle = '';

    public string $newTaskDescription = '';

    public ?string $newTaskDueDate = null;

    /** @var array<int, array{employee_id: string, raci_role: string}> */
    public array $newTaskAssignees = [];

    /** @var array<int, array{provider_id: string, raci_role: string}> */
    public array $newTaskProviders = [];

    /** @var array<int, array{role_id: string, raci_role: string}> */
    public array $newTaskRoles = [];

    public ?string $editingTaskId = null;

    public string $editTitle = '';

    public ?string $editDescription = '';

    public ?string $editDueDate = null;

    /** @var array<int, array{employee_id: string, raci_role: string}> */
    public array $editAssignees = [];

    /** @var array<int, array{provider_id: string, raci_role: string}> */
    public array $editProviders = [];

    /** @var array<int, array{role_id: string, raci_role: string}> */
    public array $editRoles = [];

    /** Stichtag (YYYY-MM-DD) für die Historie-Ansicht. Leer = alle Stints anzeigen. */
    public string $historyDate = '';

    public function mount(System $system): void
    {
        abort_unless(Auth::user()->currentCompany(), 403);

        $this->system = $system->load([
            'priority',
            'emergencyLevel',
            'serviceProviders',
            'employees',
            'dependencies',
            'dependents',
        ]);
    }

    /**
     * Open tasks first (by user-defined sort), completed at the bottom.
     *
     * @return Collection<int, SystemTask>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return SystemTask::with(['assignees', 'providerAssignees', 'roleAssignees.employees'])
            ->where('system_id', $this->system->id)
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get()
            ->sort(function (SystemTask $a, SystemTask $b) {
                if ($a->isDone() !== $b->isDone()) {
                    return $a->isDone() ? 1 : -1;
                }

                return $a->sort <=> $b->sort;
            })
            ->values();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employeesForSelect(): Collection
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * @return Collection<int, ServiceProvider>
     */
    #[Computed]
    public function providersForSelect(): Collection
    {
        return ServiceProvider::orderBy('name')->get();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function rolesForSelect(): Collection
    {
        return Role::with('employees')->orderBy('sort')->orderBy('name')->get();
    }

    /**
     * Vereinheitlichte Zuordnungs-Historie über alle Pivots des Systems
     * inkl. Aufgaben. Wenn $historyDate gesetzt ist, gefiltert auf den
     * Stichtag (point-in-time).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function assignmentHistory(): \Illuminate\Support\Collection
    {
        $history = AssignmentHistory::forSystem($this->system);

        if ($this->historyDate === '') {
            return $history;
        }

        try {
            $moment = \Illuminate\Support\Carbon::parse($this->historyDate)->endOfDay();
        } catch (\Throwable) {
            return $history;
        }

        return AssignmentHistory::atMoment($history, $moment);
    }

    public function clearHistoryDate(): void
    {
        $this->historyDate = '';
    }

    public function roleMembersText(?string $roleId): string
    {
        if (! $roleId) {
            return '';
        }

        $role = $this->rolesForSelect->firstWhere('id', $roleId);
        if (! $role) {
            return '';
        }

        $names = $role->employees->map(fn ($m) => $m->fullName())->implode(', ');

        return $names !== '' ? $names : __('Diese Rolle hat noch keine Mitglieder.');
    }

    public function addNewTaskAssignee(): void
    {
        $this->newTaskAssignees[] = [
            'employee_id' => '',
            'raci_role' => RaciRole::Responsible->value,
        ];
    }

    public function removeNewTaskAssignee(int $index): void
    {
        if (isset($this->newTaskAssignees[$index])) {
            unset($this->newTaskAssignees[$index]);
            $this->newTaskAssignees = array_values($this->newTaskAssignees);
        }
    }

    public function addNewTaskProvider(): void
    {
        $this->newTaskProviders[] = [
            'provider_id' => '',
            'raci_role' => RaciRole::Responsible->value,
        ];
    }

    public function removeNewTaskProvider(int $index): void
    {
        if (isset($this->newTaskProviders[$index])) {
            unset($this->newTaskProviders[$index]);
            $this->newTaskProviders = array_values($this->newTaskProviders);
        }
    }

    public function addEditAssignee(): void
    {
        $this->editAssignees[] = [
            'employee_id' => '',
            'raci_role' => RaciRole::Responsible->value,
        ];
    }

    public function removeEditAssignee(int $index): void
    {
        if (isset($this->editAssignees[$index])) {
            unset($this->editAssignees[$index]);
            $this->editAssignees = array_values($this->editAssignees);
        }
    }

    public function addEditProvider(): void
    {
        $this->editProviders[] = [
            'provider_id' => '',
            'raci_role' => RaciRole::Responsible->value,
        ];
    }

    public function removeEditProvider(int $index): void
    {
        if (isset($this->editProviders[$index])) {
            unset($this->editProviders[$index]);
            $this->editProviders = array_values($this->editProviders);
        }
    }

    public function addNewTaskRole(): void
    {
        $this->newTaskRoles[] = [
            'role_id' => '',
            'raci_role' => RaciRole::Responsible->value,
        ];
    }

    public function removeNewTaskRole(int $index): void
    {
        if (isset($this->newTaskRoles[$index])) {
            unset($this->newTaskRoles[$index]);
            $this->newTaskRoles = array_values($this->newTaskRoles);
        }
    }

    public function addEditRole(): void
    {
        $this->editRoles[] = [
            'role_id' => '',
            'raci_role' => RaciRole::Responsible->value,
        ];
    }

    public function removeEditRole(int $index): void
    {
        if (isset($this->editRoles[$index])) {
            unset($this->editRoles[$index]);
            $this->editRoles = array_values($this->editRoles);
        }
    }

    public function addTask(): void
    {
        $raciValues = implode(',', array_column(RaciRole::cases(), 'value'));

        $validated = $this->validate([
            'newTaskTitle' => ['required', 'string', 'max:255'],
            'newTaskDescription' => ['nullable', 'string', 'max:2000'],
            'newTaskDueDate' => ['nullable', 'date'],
            'newTaskAssignees' => ['array'],
            'newTaskAssignees.*.employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'newTaskAssignees.*.raci_role' => ['required', 'in:'.$raciValues],
            'newTaskProviders' => ['array'],
            'newTaskProviders.*.provider_id' => ['required', 'uuid', 'exists:service_providers,id'],
            'newTaskProviders.*.raci_role' => ['required', 'in:'.$raciValues],
            'newTaskRoles' => ['array'],
            'newTaskRoles.*.role_id' => ['required', 'uuid', 'exists:roles,id'],
            'newTaskRoles.*.raci_role' => ['required', 'in:'.$raciValues],
        ]);

        $nextSort = (int) SystemTask::where('system_id', $this->system->id)->max('sort') + 1;

        $task = SystemTask::create([
            'system_id' => $this->system->id,
            'title' => $validated['newTaskTitle'],
            'description' => $validated['newTaskDescription'] ?: null,
            'due_date' => $validated['newTaskDueDate'] ?: null,
            'sort' => $nextSort,
        ]);

        $this->syncAssignees($task, $validated['newTaskAssignees'] ?? []);
        $this->syncProviderAssignees($task, $validated['newTaskProviders'] ?? []);
        $this->syncRoleAssignees($task, $validated['newTaskRoles'] ?? []);

        $this->reset(['newTaskTitle', 'newTaskDescription', 'newTaskDueDate', 'newTaskAssignees', 'newTaskProviders', 'newTaskRoles']);
        unset($this->tasks);

        $this->dispatch('task-saved');

        Flux::toast(variant: 'success', text: __('Aufgabe hinzugefügt.'));
    }

    public function toggleTask(string $id): void
    {
        $task = SystemTask::where('system_id', $this->system->id)->findOrFail($id);

        $task->update([
            'completed_at' => $task->completed_at ? null : now(),
        ]);

        unset($this->tasks);
    }

    public function moveTaskUp(string $id): void
    {
        $this->swapSort($id, -1);
    }

    public function moveTaskDown(string $id): void
    {
        $this->swapSort($id, +1);
    }

    protected function swapSort(string $id, int $direction): void
    {
        $task = SystemTask::where('system_id', $this->system->id)->findOrFail($id);

        if ($task->completed_at !== null) {
            return;
        }

        $neighbour = SystemTask::where('system_id', $this->system->id)
            ->whereNull('completed_at')
            ->where('id', '!=', $task->id)
            ->when($direction < 0, fn ($q) => $q->where('sort', '<', $task->sort)->orderByDesc('sort'))
            ->when($direction > 0, fn ($q) => $q->where('sort', '>', $task->sort)->orderBy('sort'))
            ->first();

        if (! $neighbour) {
            return;
        }

        [$task->sort, $neighbour->sort] = [$neighbour->sort, $task->sort];
        $task->save();
        $neighbour->save();

        unset($this->tasks);
    }

    public function deleteTask(string $id): void
    {
        SystemTask::where('system_id', $this->system->id)->findOrFail($id)->delete();

        unset($this->tasks);

        Flux::toast(variant: 'success', text: __('Aufgabe gelöscht.'));
    }

    public function openEditTask(string $id): void
    {
        $task = SystemTask::with(['assignees', 'providerAssignees', 'roleAssignees'])
            ->where('system_id', $this->system->id)
            ->findOrFail($id);

        $this->editingTaskId = $task->id;
        $this->editTitle = $task->title;
        $this->editDescription = (string) $task->description;
        $this->editDueDate = $task->due_date?->toDateString();
        $this->editAssignees = $task->assignees
            ->map(fn (Employee $e) => [
                'employee_id' => $e->id,
                'raci_role' => (string) $e->pivot->raci_role,
            ])
            ->values()
            ->all();
        $this->editProviders = $task->providerAssignees
            ->map(fn (ServiceProvider $p) => [
                'provider_id' => $p->id,
                'raci_role' => (string) $p->pivot->raci_role,
            ])
            ->values()
            ->all();
        $this->editRoles = $task->roleAssignees
            ->map(fn (Role $r) => [
                'role_id' => $r->id,
                'raci_role' => (string) $r->pivot->raci_role,
            ])
            ->values()
            ->all();

        Flux::modal('task-edit')->show();
    }

    public function saveEditTask(): void
    {
        $raciValues = implode(',', array_column(RaciRole::cases(), 'value'));

        $validated = $this->validate([
            'editTitle' => ['required', 'string', 'max:255'],
            'editDescription' => ['nullable', 'string', 'max:2000'],
            'editDueDate' => ['nullable', 'date'],
            'editAssignees' => ['array'],
            'editAssignees.*.employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'editAssignees.*.raci_role' => ['required', 'in:'.$raciValues],
            'editProviders' => ['array'],
            'editProviders.*.provider_id' => ['required', 'uuid', 'exists:service_providers,id'],
            'editProviders.*.raci_role' => ['required', 'in:'.$raciValues],
            'editRoles' => ['array'],
            'editRoles.*.role_id' => ['required', 'uuid', 'exists:roles,id'],
            'editRoles.*.raci_role' => ['required', 'in:'.$raciValues],
        ]);

        $task = SystemTask::where('system_id', $this->system->id)
            ->findOrFail($this->editingTaskId);

        $task->update([
            'title' => $validated['editTitle'],
            'description' => $validated['editDescription'] ?: null,
            'due_date' => $validated['editDueDate'] ?: null,
        ]);

        $this->syncAssignees($task, $validated['editAssignees'] ?? []);
        $this->syncProviderAssignees($task, $validated['editProviders'] ?? []);
        $this->syncRoleAssignees($task, $validated['editRoles'] ?? []);

        Flux::modal('task-edit')->close();
        $this->reset(['editingTaskId', 'editTitle', 'editDescription', 'editDueDate', 'editAssignees', 'editProviders', 'editRoles']);
        unset($this->tasks);

        Flux::toast(variant: 'success', text: __('Aufgabe gespeichert.'));
    }

    /**
     * @param  array<int, array{employee_id: string, raci_role: string}>  $rows
     */
    protected function syncAssignees(SystemTask $task, array $rows): void
    {
        $sync = [];
        foreach ($rows as $row) {
            if (empty($row['employee_id'])) {
                continue;
            }
            $sync[$row['employee_id']] = ['raci_role' => $row['raci_role']];
        }

        AssignmentSync::sync($task, $task->assignees(), $sync);
    }

    /**
     * @param  array<int, array{provider_id: string, raci_role: string}>  $rows
     */
    protected function syncProviderAssignees(SystemTask $task, array $rows): void
    {
        $sync = [];
        foreach ($rows as $row) {
            if (empty($row['provider_id'])) {
                continue;
            }
            $sync[$row['provider_id']] = ['raci_role' => $row['raci_role']];
        }

        AssignmentSync::sync($task, $task->providerAssignees(), $sync);
    }

    /**
     * @param  array<int, array{role_id: string, raci_role: string}>  $rows
     */
    protected function syncRoleAssignees(SystemTask $task, array $rows): void
    {
        $sync = [];
        foreach (array_values($rows) as $index => $row) {
            if (empty($row['role_id'])) {
                continue;
            }
            $sync[$row['role_id']] = [
                'raci_role' => $row['raci_role'],
                'sort' => $index,
            ];
        }

        AssignmentSync::sync($task, $task->roleAssignees(), $sync);
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('systems.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Systeme') }}
        </flux:link>
    </div>

    <div class="mb-6 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between gap-4 border-b border-zinc-100 p-6 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl">{{ $system->name }}</flux:heading>
                    @if ($system->priority)
                        <flux:badge
                            :color="match ($system->priority->sort) { 1 => 'rose', 2 => 'amber', default => 'zinc' }"
                        >
                            {{ $system->priority->name }}
                        </flux:badge>
                    @endif
                    <flux:badge color="zinc">{{ $system->category->label() }}</flux:badge>
                </div>
                @if ($system->description)
                    <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $system->description }}
                    </flux:text>
                @endif
                @if ($system->fallback_process)
                    <div class="mt-3">
                        <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Notbetrieb / Ersatzprozess') }}</flux:text>
                        <flux:text class="mt-1 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">
                            {{ $system->fallback_process }}
                        </flux:text>
                    </div>
                @endif
                @if ($system->runbook_reference)
                    <div class="mt-3 flex items-center gap-2">
                        <flux:icon.book-open class="h-4 w-4 text-zinc-400" />
                        <div>
                            <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Runbook-Verweis') }}</flux:text>
                            <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $system->runbook_reference }}
                            </flux:text>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex shrink-0 gap-2">
                <flux:button size="sm" variant="filled" icon="qr-code" :href="route('systems.sticker', ['system' => $system->id])" target="_blank">
                    {{ __('QR-Aushang') }}
                </flux:button>
                <flux:button size="sm" variant="primary" icon="pencil" :href="route('systems.edit', ['system' => $system->id])" wire:navigate>
                    {{ __('Bearbeiten') }}
                </flux:button>
            </div>
        </div>

        @if ($system->emergencyLevel)
            @php
                $level = $system->emergencyLevel;
            @endphp
            <div class="border-b border-zinc-100 p-6 dark:border-zinc-800">
                <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Wiederanlauf-Stufe') }}</flux:text>
                <div class="mt-2 rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/40">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-sky-600 bg-sky-600 text-sm font-semibold text-white">
                            {{ $level->sort }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <flux:heading size="base" class="text-sky-900 dark:text-sky-100">{{ $level->name }}</flux:heading>
                            @if ($level->description)
                                <flux:text class="mt-1 text-sm text-sky-900/80 dark:text-sky-100/80">
                                    {{ $level->description }}
                                </flux:text>
                            @endif
                            @if ($level->reaction)
                                <div class="mt-3 rounded-md bg-white/60 px-3 py-2 text-sm dark:bg-zinc-900/60">
                                    <span class="text-xs font-semibold uppercase text-sky-700 dark:text-sky-300">{{ __('Reaktion') }}:</span>
                                    <span class="ml-1 text-zinc-700 dark:text-zinc-200">{{ $level->reaction }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($system->rto_minutes || $system->rpo_minutes || $system->downtime_cost_per_hour)
            <div class="grid gap-4 border-b border-zinc-100 p-6 sm:grid-cols-3 dark:border-zinc-800">
                @if ($system->rto_minutes)
                    <div>
                        <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Max. Ausfall (RTO)') }}</flux:text>
                        <div class="mt-1 text-lg font-medium">{{ Duration::format($system->rto_minutes) }}</div>
                    </div>
                @endif
                @if ($system->rpo_minutes)
                    <div>
                        <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Max. Datenverlust (RPO)') }}</flux:text>
                        <div class="mt-1 text-lg font-medium">{{ Duration::format($system->rpo_minutes) }}</div>
                    </div>
                @endif
                @if ($system->downtime_cost_per_hour)
                    <div>
                        <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Ausfallkosten') }}</flux:text>
                        <div class="mt-1 text-lg font-medium">{{ number_format($system->downtime_cost_per_hour, 0, ',', '.') }} € / h</div>
                    </div>
                @endif
            </div>
        @endif

        <div x-data="{ tab: 'employees' }" class="p-6">
            <div role="tablist" class="mb-4 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
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
                    <flux:badge color="teal" size="sm">{{ $system->employees->count() }}</flux:badge>
                </button>
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
                    <flux:badge color="teal" size="sm">{{ $system->serviceProviders->count() }}</flux:badge>
                </button>
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
                    <flux:badge color="teal" size="sm">{{ $system->dependencies->count() + $system->dependents->count() }}</flux:badge>
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="tab === 'history'"
                    @click="tab = 'history'"
                    :class="tab === 'history'
                        ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                        : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                >
                    <flux:icon.clock class="h-4 w-4" />
                    {{ __('Historie') }}
                </button>
            </div>

            <div x-show="tab === 'employees'" x-cloak class="space-y-5">
                @if ($system->employees->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Verantwortlichen zugewiesen.') }}</flux:text>
                @else
                    @php
                        $raciGroupsEmployees = $system->employees->groupBy(fn ($e) => $e->pivot->raci_role ?? '');
                        $accountableCountEmp = ($raciGroupsEmployees->get('A') ?? collect())->count();
                        $responsibleCountEmp = ($raciGroupsEmployees->get('R') ?? collect())->count();
                    @endphp

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/50">
                        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                            <flux:heading size="sm">{{ __('Zuständigkeiten auf System-Ebene') }}</flux:heading>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Pflicht: genau 1 × A (Accountable), mind. 1 × R (Responsible).') }}
                            </flux:text>
                        </div>
                        <flux:text class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Grundsätzliche Ownership für dieses System. Konkrete Aufgaben haben ihr eigenes RACI weiter unten im Abschnitt „Aufgaben".') }}
                        </flux:text>

                        @if ($accountableCountEmp === 0 || $responsibleCountEmp === 0 || $accountableCountEmp > 1)
                            <div class="mb-3 flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm dark:border-rose-900 dark:bg-rose-950/40">
                                <flux:icon.exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
                                <div class="space-y-0.5 text-rose-800 dark:text-rose-200">
                                    @if ($accountableCountEmp === 0)
                                        <div>{{ __('Kein „A" (Accountable) zugewiesen – genau eine Person wird erwartet.') }}</div>
                                    @elseif ($accountableCountEmp > 1)
                                        <div>{{ __(':n Personen mit „A" (Accountable) – klassisch nur eine Person.', ['n' => $accountableCountEmp]) }}</div>
                                    @endif
                                    @if ($responsibleCountEmp === 0)
                                        <div>{{ __('Kein „R" (Responsible) zugewiesen – mindestens eine Person wird erwartet.') }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach (\App\Enums\RaciRole::cases() as $r)
                                @php
                                    $members = $raciGroupsEmployees->get($r->value) ?? collect();
                                @endphp
                                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="mb-2 flex items-center gap-2">
                                        <flux:badge :color="$r->badgeColor()" size="sm">
                                            <span class="font-bold">{{ $r->value }}</span>
                                            <span class="ml-1">{{ $r->label() }}</span>
                                        </flux:badge>
                                    </div>
                                    <flux:text class="mb-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $r->description() }}</flux:text>
                                    @if ($members->isEmpty())
                                        <div class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __('— nicht besetzt —') }}</div>
                                    @else
                                        <ul class="space-y-0.5 text-sm">
                                            @foreach ($members as $m)
                                                <li class="text-zinc-700 dark:text-zinc-200">{{ $m->fullName() }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($system->employees as $index => $e)
                            @php
                                $raci = \App\Enums\RaciRole::tryFrom((string) $e->pivot->raci_role);
                            @endphp
                            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:heading size="base">{{ $e->fullName() }}</flux:heading>
                                            @if ($raci)
                                                <flux:badge :color="$raci->badgeColor()" size="sm" :title="$raci->description()">
                                                    <span class="font-bold">{{ $raci->value }}</span>
                                                    <span class="ml-1">{{ $raci->label() }}</span>
                                                </flux:badge>
                                            @endif
                                        </div>
                                        @if ($e->position || $e->department)
                                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $e->position }}@if ($e->position && $e->department) · @endif{{ $e->department }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>

                                @if ($e->mobile_phone || $e->work_phone || $e->email)
                                    <div class="mt-4 space-y-2 text-sm">
                                        @if ($e->mobile_phone)
                                            <div class="flex items-start gap-2">
                                                <flux:icon.device-phone-mobile class="mt-0.5 h-4 w-4 text-zinc-400" />
                                                <div>
                                                    <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Mobil') }}</div>
                                                    <a href="tel:{{ $e->mobile_phone }}" class="font-medium hover:underline">{{ $e->mobile_phone }}</a>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($e->work_phone)
                                            <div class="flex items-start gap-2">
                                                <flux:icon.phone class="mt-0.5 h-4 w-4 text-zinc-400" />
                                                <div>
                                                    <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Festnetz') }}</div>
                                                    <a href="tel:{{ $e->work_phone }}" class="hover:underline">{{ $e->work_phone }}</a>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($e->email)
                                            <div class="flex items-start gap-2">
                                                <flux:icon.envelope class="mt-0.5 h-4 w-4 text-zinc-400" />
                                                <div>
                                                    <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('E-Mail') }}</div>
                                                    <a href="mailto:{{ $e->email }}" class="hover:underline">{{ $e->email }}</a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($e->pivot->note)
                                    <div class="mt-4 flex items-start gap-2 rounded-md border-l-2 border-amber-400 bg-amber-50 px-3 py-2 dark:border-amber-500 dark:bg-amber-950/30">
                                        <flux:icon.chat-bubble-left-ellipsis class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-400">{{ __('Notiz') }}</div>
                                            <div class="mt-0.5 text-sm text-zinc-700 dark:text-zinc-200">{{ $e->pivot->note }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div x-show="tab === 'providers'" x-cloak class="space-y-5">
                @if ($system->serviceProviders->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Keine Dienstleister zugeordnet.') }}</flux:text>
                @else
                    @php
                        $raciGroupsProv = $system->serviceProviders->groupBy(fn ($p) => $p->pivot->raci_role ?? '');
                    @endphp

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/50">
                        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                            <flux:heading size="sm">{{ __('Zuständigkeiten auf System-Ebene') }}</flux:heading>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Dienstleister ergänzen i. d. R. die R-/C-Rollen.') }}
                            </flux:text>
                        </div>
                        <flux:text class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Grundsätzliche Zuständigkeit für dieses System. Für konkrete Aufgaben siehe RACI-Matrix im Abschnitt „Aufgaben".') }}
                        </flux:text>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach (\App\Enums\RaciRole::cases() as $r)
                                @php
                                    $members = $raciGroupsProv->get($r->value) ?? collect();
                                @endphp
                                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="mb-2 flex items-center gap-2">
                                        <flux:badge :color="$r->badgeColor()" size="sm">
                                            <span class="font-bold">{{ $r->value }}</span>
                                            <span class="ml-1">{{ $r->label() }}</span>
                                        </flux:badge>
                                    </div>
                                    <flux:text class="mb-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $r->description() }}</flux:text>
                                    @if ($members->isEmpty())
                                        <div class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __('— nicht besetzt —') }}</div>
                                    @else
                                        <ul class="space-y-0.5 text-sm">
                                            @foreach ($members as $m)
                                                <li class="text-zinc-700 dark:text-zinc-200">{{ $m->name }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($system->serviceProviders as $index => $p)
                            @php
                                $raci = \App\Enums\RaciRole::tryFrom((string) $p->pivot->raci_role);
                            @endphp
                            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:heading size="base">{{ $p->name }}</flux:heading>
                                            @if ($raci)
                                                <flux:badge :color="$raci->badgeColor()" size="sm" :title="$raci->description()">
                                                    <span class="font-bold">{{ $raci->value }}</span>
                                                    <span class="ml-1">{{ $raci->label() }}</span>
                                                </flux:badge>
                                            @endif
                                        </div>
                                        @if ($p->contact_name)
                                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $p->contact_name }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2 text-sm">
                                    @if ($p->hotline)
                                        <div class="flex items-start gap-2">
                                            <flux:icon.phone class="mt-0.5 h-4 w-4 text-zinc-400" />
                                            <div>
                                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Hotline') }}</div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-medium">{{ $p->hotline }}</span>
                                                    @if ($p->sla)<flux:badge color="zinc" size="sm">{{ $p->sla }}</flux:badge>@endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($p->email)
                                        <div class="flex items-start gap-2">
                                            <flux:icon.envelope class="mt-0.5 h-4 w-4 text-zinc-400" />
                                            <div>
                                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Notfall-E-Mail') }}</div>
                                                <span>{{ $p->email }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($p->contract_number)
                                        <div class="flex items-start gap-2">
                                            <flux:icon.document-text class="mt-0.5 h-4 w-4 text-zinc-400" />
                                            <div>
                                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Vertragsnummer') }}</div>
                                                <span class="text-zinc-600 dark:text-zinc-300">{{ $p->contract_number }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if ($p->pivot->note)
                                    <div class="mt-4 flex items-start gap-2 rounded-md border-l-2 border-amber-400 bg-amber-50 px-3 py-2 dark:border-amber-500 dark:bg-amber-950/30">
                                        <flux:icon.chat-bubble-left-ellipsis class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-400">{{ __('Notiz') }}</div>
                                            <div class="mt-0.5 text-sm text-zinc-700 dark:text-zinc-200">{{ $p->pivot->note }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div x-show="tab === 'dependencies'" x-cloak class="space-y-6">
                <div>
                    <flux:heading size="sm" class="mb-3">{{ __('Braucht:') }}</flux:heading>
                    @if ($system->dependencies->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Dieses System hat keine Abhängigkeiten.') }}</flux:text>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($system->dependencies as $index => $dep)
                                <a href="{{ route('systems.show', ['system' => $dep->id]) }}" wire:navigate
                                   class="group rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                            {{ $index + 1 }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <flux:heading size="base" class="group-hover:underline">{{ $dep->name }}</flux:heading>
                                                <flux:badge color="zinc" size="sm">{{ $dep->category->label() }}</flux:badge>
                                            </div>
                                            @if ($dep->description)
                                                <flux:text class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ $dep->description }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($dep->pivot->note)
                                        <div class="mt-4 flex items-start gap-2 rounded-md border-l-2 border-amber-400 bg-amber-50 px-3 py-2 dark:border-amber-500 dark:bg-amber-950/30">
                                            <flux:icon.chat-bubble-left-ellipsis class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-400">{{ __('Notiz') }}</div>
                                                <div class="mt-0.5 text-sm text-zinc-700 dark:text-zinc-200">{{ $dep->pivot->note }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <flux:heading size="sm" class="mb-3">{{ __('Blockiert:') }}</flux:heading>
                    @if ($system->dependents->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kein anderes System hängt davon ab.') }}</flux:text>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($system->dependents as $dep)
                                <a href="{{ route('systems.show', ['system' => $dep->id]) }}" wire:navigate
                                   class="group rounded-xl border border-violet-200 bg-violet-50/50 p-5 transition hover:border-violet-300 hover:shadow-sm dark:border-violet-900 dark:bg-violet-950/20 dark:hover:border-violet-700">
                                    <div class="flex items-start gap-3">
                                        <flux:icon.arrow-long-left class="mt-1 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <flux:heading size="base" class="group-hover:underline">{{ $dep->name }}</flux:heading>
                                                <flux:badge color="violet" size="sm">{{ $dep->category->label() }}</flux:badge>
                                            </div>
                                            @if ($dep->description)
                                                <flux:text class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ $dep->description }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div x-show="tab === 'history'" x-cloak class="space-y-5">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/50">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[220px]">
                            <flux:heading size="sm">{{ __('Zuordnungs-Historie') }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Revisionssichere Übersicht aller Mitarbeiter-, Rollen- und Dienstleister-Zuordnungen — auf System-Ebene und auf Aufgaben-Ebene. Wähle einen Stichtag, um die Verantwortlichen zu einem konkreten Zeitpunkt zu sehen.') }}
                            </flux:text>
                        </div>
                        <flux:field>
                            <flux:label>{{ __('Stichtag') }}</flux:label>
                            <flux:input type="date" wire:model.live="historyDate" />
                        </flux:field>
                        @if ($historyDate !== '')
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearHistoryDate">
                                {{ __('Filter zurücksetzen') }}
                            </flux:button>
                        @endif
                    </div>
                </div>

                @php
                    $rows = $this->assignmentHistory;
                @endphp

                @if ($rows->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $historyDate !== ''
                            ? __('Am gewählten Stichtag waren keine Zuordnungen aktiv.')
                            : __('Für dieses System sind noch keine Zuordnungen erfasst.') }}
                    </flux:text>
                @else
                    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        @foreach ($rows as $row)
                            @php
                                $raci = $row['raci_role'] ? \App\Enums\RaciRole::tryFrom((string) $row['raci_role']) : null;
                                $isActive = $row['removed_at'] === null;
                                $kindIcon = match ($row['kind']) {
                                    'role' => 'users',
                                    'provider' => 'wrench-screwdriver',
                                    default => 'user',
                                };
                            @endphp
                            <div class="border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                                <div class="flex flex-wrap items-start gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                        <flux:icon :name="$kindIcon" class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:badge color="zinc" size="sm">{{ $row['kind_label'] }}</flux:badge>
                                            <span class="font-medium">{{ $row['target_label'] }}</span>
                                            @if ($raci)
                                                <flux:badge :color="$raci->badgeColor()" size="sm" :title="$raci->description()">
                                                    <span class="font-bold">{{ $raci->value }}</span>
                                                    <span class="ml-1">{{ $raci->label() }}</span>
                                                </flux:badge>
                                            @endif
                                            @if ($isActive)
                                                <flux:badge color="emerald" size="sm">{{ __('aktiv') }}</flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm">{{ __('beendet') }}</flux:badge>
                                            @endif
                                        </div>
                                        <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($row['scope'] === 'system')
                                                {{ __('System-Ebene') }}
                                            @else
                                                {{ __('Aufgabe') }}: <span class="text-zinc-700 dark:text-zinc-200">{{ $row['scope_label'] }}</span>
                                            @endif
                                        </flux:text>
                                        <div class="mt-2 grid gap-2 text-xs sm:grid-cols-2">
                                            <div>
                                                <span class="text-zinc-500 dark:text-zinc-400">{{ __('Zugewiesen') }}:</span>
                                                <span class="ml-1 text-zinc-700 dark:text-zinc-200">
                                                    {{ $row['assigned_at']?->format('d.m.Y H:i') ?? '—' }}
                                                </span>
                                                @if ($row['assigned_by'])
                                                    <span class="text-zinc-500 dark:text-zinc-400">· {{ $row['assigned_by'] }}</span>
                                                @endif
                                            </div>
                                            <div>
                                                @if ($row['removed_at'])
                                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Entzogen') }}:</span>
                                                    <span class="ml-1 text-zinc-700 dark:text-zinc-200">{{ $row['removed_at']->format('d.m.Y H:i') }}</span>
                                                    @if ($row['removed_by'])
                                                        <span class="text-zinc-500 dark:text-zinc-400">· {{ $row['removed_by'] }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-zinc-400 italic dark:text-zinc-500">{{ __('aktuell zugewiesen') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($row['note'])
                                            <div class="mt-2 flex items-start gap-2 rounded-md border-l-2 border-amber-400 bg-amber-50 px-3 py-1.5 text-sm dark:border-amber-500 dark:bg-amber-950/30">
                                                <flux:icon.chat-bubble-left-ellipsis class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                                                <span class="text-zinc-700 dark:text-zinc-200">{{ $row['note'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Aufgaben-Bereich unter der Systemkarte --}}
    <div class="mb-6 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" x-data="{ showTaskForm: false }" @task-saved.window="showTaskForm = false">
        <div class="flex items-center justify-between gap-4 border-b border-zinc-100 p-6 dark:border-zinc-800">
            <div>
                <flux:heading size="lg">{{ __('Aufgaben') }}</flux:heading>
                <flux:subheading>
                    {{ __('Wartungs-, Vorbereitungs- und Nachbesserungs-Aufgaben zu diesem System. Pro Aufgabe wird RACI separat zugeordnet — unabhängig von den Zuständigkeiten auf System-Ebene.') }}
                </flux:subheading>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <flux:button type="button" variant="primary" icon="plus" x-show="!showTaskForm" @click="showTaskForm = true">
                    {{ __('Aufgabe erfassen') }}
                </flux:button>
            </div>
        </div>

        <div class="space-y-6 p-6">
            <form
                wire:submit="addTask"
                x-show="showTaskForm"
                x-cloak
                class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/50"
            >
                <flux:heading size="sm">{{ __('Neue Aufgabe') }}</flux:heading>

                <flux:input
                    wire:model="newTaskTitle"
                    :label="__('Überschrift')"
                    placeholder="z. B. Backup monatlich prüfen"
                    required
                />

                <flux:textarea
                    wire:model="newTaskDescription"
                    :label="__('Beschreibung')"
                    rows="3"
                    placeholder="Details, Schritte, Kontext"
                />

                <flux:field>
                    <flux:label>{{ __('Fällig') }}</flux:label>
                    <flux:input type="date" wire:model="newTaskDueDate" class="max-w-xs" />
                </flux:field>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <flux:label>{{ __('Personen (RACI)') }}</flux:label>
                        <flux:button type="button" size="sm" variant="filled" icon="plus" wire:click.prevent="addNewTaskAssignee">
                            {{ __('Person hinzufügen') }}
                        </flux:button>
                    </div>

                    @if (empty($newTaskAssignees))
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Personen zugeordnet.') }}
                        </flux:text>
                    @else
                        <div class="space-y-2">
                            @foreach ($newTaskAssignees as $i => $row)
                                <div wire:key="new-assignee-{{ $i }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                    <flux:select wire:model="newTaskAssignees.{{ $i }}.employee_id" class="flex-1" required>
                                        <flux:select.option value="">{{ __('Mitarbeiter wählen') }}</flux:select.option>
                                        @foreach ($this->employeesForSelect as $e)
                                            <flux:select.option value="{{ $e->id }}">
                                                {{ $e->fullName() }}@if ($e->position) · {{ $e->position }}@endif
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:select wire:model="newTaskAssignees.{{ $i }}.raci_role" class="sm:w-64">
                                        @foreach (\App\Enums\RaciRole::cases() as $r)
                                            <flux:select.option value="{{ $r->value }}">{{ $r->value }} – {{ $r->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark"
                                        wire:click.prevent="removeNewTaskAssignee({{ $i }})" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <flux:label>{{ __('Dienstleister (RACI)') }}</flux:label>
                        <flux:button type="button" size="sm" variant="filled" icon="plus" wire:click.prevent="addNewTaskProvider">
                            {{ __('Dienstleister hinzufügen') }}
                        </flux:button>
                    </div>

                    @if (empty($newTaskProviders))
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Dienstleister zugeordnet.') }}
                        </flux:text>
                    @else
                        <div class="space-y-2">
                            @foreach ($newTaskProviders as $i => $row)
                                <div wire:key="new-provider-{{ $i }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                    <flux:select wire:model="newTaskProviders.{{ $i }}.provider_id" class="flex-1" required>
                                        <flux:select.option value="">{{ __('Dienstleister wählen') }}</flux:select.option>
                                        @foreach ($this->providersForSelect as $p)
                                            <flux:select.option value="{{ $p->id }}">
                                                {{ $p->name }}@if ($p->hotline) · {{ $p->hotline }}@endif
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:select wire:model="newTaskProviders.{{ $i }}.raci_role" class="sm:w-64">
                                        @foreach (\App\Enums\RaciRole::cases() as $r)
                                            <flux:select.option value="{{ $r->value }}">{{ $r->value }} – {{ $r->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark"
                                        wire:click.prevent="removeNewTaskProvider({{ $i }})" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <flux:label>{{ __('Rollen (RACI)') }}</flux:label>
                        <flux:button type="button" size="sm" variant="filled" icon="plus" wire:click.prevent="addNewTaskRole">
                            {{ __('Rolle hinzufügen') }}
                        </flux:button>
                    </div>
                    <flux:description>
                        {{ __('Mitglieder der gewählten Rolle werden automatisch als verantwortliche Personen angezeigt.') }}
                    </flux:description>

                    @if (empty($newTaskRoles))
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Rollen zugeordnet.') }}
                        </flux:text>
                    @else
                        <div class="space-y-2">
                            @foreach ($newTaskRoles as $i => $row)
                                <div wire:key="new-role-{{ $i }}" class="space-y-1.5 rounded-md border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                        <flux:select wire:model.live="newTaskRoles.{{ $i }}.role_id" class="flex-1" required>
                                            <flux:select.option value="">{{ __('Rolle wählen') }}</flux:select.option>
                                            @foreach ($this->rolesForSelect as $r)
                                                <flux:select.option value="{{ $r->id }}">{{ $r->name }} ({{ $r->employees->count() }})</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="newTaskRoles.{{ $i }}.raci_role" class="sm:w-64">
                                            @foreach (\App\Enums\RaciRole::cases() as $r)
                                                <flux:select.option value="{{ $r->value }}">{{ $r->value }} – {{ $r->label() }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click.prevent="removeNewTaskRole({{ $i }})" />
                                    </div>
                                    @if ($row['role_id'])
                                        <div class="px-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $this->roleMembersText($row['role_id']) }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:button type="button" variant="filled" @click="showTaskForm = false">
                        {{ __('Abbrechen') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('Aufgabe speichern') }}
                    </flux:button>
                </div>
            </form>

            @if ($this->tasks->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('Noch keine Aufgaben erfasst.') }}
                </div>
            @else
                @php
                    $openTasks = $this->tasks->whereNull('completed_at')->values();
                    $openCount = $openTasks->count();
                @endphp
                <ul class="space-y-2">
                    @foreach ($this->tasks as $task)
                        @php
                            $openIndex = $openTasks->search(fn ($t) => $t->id === $task->id);
                        @endphp
                        <li wire:key="task-{{ $task->id }}"
                            class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
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
                                @if ($task->assignees->isNotEmpty() || $task->providerAssignees->isNotEmpty() || $task->roleAssignees->isNotEmpty())
                                    @php($taskEmpGroups = $task->assignees->groupBy(fn ($e) => $e->pivot->raci_role ?? ''))
                                    @php($taskProvGroups = $task->providerAssignees->groupBy(fn ($p) => $p->pivot->raci_role ?? ''))
                                    @php($taskRoleGroups = $task->roleAssignees->groupBy(fn ($r) => $r->pivot->raci_role ?? ''))
                                    @php($roleMembersFor = fn (string $code) => ($taskRoleGroups->get($code) ?? collect())->flatMap(fn ($role) => $role->employees))
                                    @php($taskAccountableCount = ($taskEmpGroups->get('A') ?? collect())->count() + ($taskProvGroups->get('A') ?? collect())->count() + $roleMembersFor('A')->count())
                                    @php($taskResponsibleCount = ($taskEmpGroups->get('R') ?? collect())->count() + ($taskProvGroups->get('R') ?? collect())->count() + $roleMembersFor('R')->count())

                                    @if (! $task->isDone() && ($taskAccountableCount === 0 || $taskResponsibleCount === 0 || $taskAccountableCount > 1))
                                        <div class="mt-2 flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs dark:border-rose-900 dark:bg-rose-950/40">
                                            <flux:icon.exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
                                            <div class="space-y-0.5 text-rose-800 dark:text-rose-200">
                                                @if ($taskAccountableCount === 0)
                                                    <div>{{ __('Kein „A" (Accountable) zugewiesen – genau eine Person wird erwartet.') }}</div>
                                                @elseif ($taskAccountableCount > 1)
                                                    <div>{{ __(':n × „A" (Accountable) zugewiesen – klassisch nur eine Person.', ['n' => $taskAccountableCount]) }}</div>
                                                @endif
                                                @if ($taskResponsibleCount === 0)
                                                    <div>{{ __('Kein „R" (Responsible) zugewiesen – mindestens eine Person wird erwartet.') }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="grid gap-2 pt-2 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach (\App\Enums\RaciRole::cases() as $r)
                                            @php($emps = $taskEmpGroups->get($r->value) ?? collect())
                                            @php($provs = $taskProvGroups->get($r->value) ?? collect())
                                            @php($rolesAtR = $taskRoleGroups->get($r->value) ?? collect())
                                            @php($isEmpty = $emps->isEmpty() && $provs->isEmpty() && $rolesAtR->isEmpty())
                                            <div class="rounded-md border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                                                <div class="mb-1">
                                                    <flux:badge :color="$r->badgeColor()" size="sm" :title="$r->description()">
                                                        <span class="font-bold">{{ $r->value }}</span>
                                                        <span class="ml-1">{{ $r->label() }}</span>
                                                    </flux:badge>
                                                </div>
                                                <flux:text class="mb-1.5 text-[11px] leading-tight text-zinc-500 dark:text-zinc-400">
                                                    {{ $r->description() }}
                                                </flux:text>
                                                @if ($isEmpty)
                                                    <div class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __('— nicht besetzt —') }}</div>
                                                @else
                                                    <ul class="space-y-0.5 text-xs">
                                                        @foreach ($emps as $person)
                                                            <li class="flex items-center gap-1 text-zinc-700 dark:text-zinc-200">
                                                                <flux:icon.user class="h-3 w-3 shrink-0 text-zinc-400" />
                                                                <span class="truncate">{{ $person->fullName() }}</span>
                                                            </li>
                                                        @endforeach
                                                        @foreach ($provs as $prov)
                                                            <li class="flex items-center gap-1 text-zinc-700 dark:text-zinc-200">
                                                                <flux:icon.wrench-screwdriver class="h-3 w-3 shrink-0 text-zinc-400" />
                                                                <span class="truncate">{{ $prov->name }}</span>
                                                            </li>
                                                        @endforeach
                                                        @foreach ($rolesAtR as $roleAssignee)
                                                            <li class="text-zinc-700 dark:text-zinc-200">
                                                                <div class="font-medium">{{ $roleAssignee->name }}</div>
                                                                <div class="ml-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                                                                    {{ $this->roleMembersText($roleAssignee->id) }}
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                @unless ($task->isDone())
                                    <button type="button" wire:click.prevent="moveTaskUp('{{ $task->id }}')"
                                            @disabled($openIndex === false || $openIndex === 0)
                                            :title="{{ json_encode(__('Nach oben')) }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-30 disabled:hover:bg-transparent dark:hover:bg-zinc-800">
                                        <flux:icon.arrow-up class="h-4 w-4" />
                                    </button>
                                    <button type="button" wire:click.prevent="moveTaskDown('{{ $task->id }}')"
                                            @disabled($openIndex === false || $openIndex === $openCount - 1)
                                            :title="{{ json_encode(__('Nach unten')) }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 disabled:opacity-30 disabled:hover:bg-transparent dark:hover:bg-zinc-800">
                                        <flux:icon.arrow-down class="h-4 w-4" />
                                    </button>
                                @endunless
                                <button type="button" wire:click.prevent="openEditTask('{{ $task->id }}')"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    <flux:icon.pencil class="h-4 w-4" />
                                </button>
                                <button type="button" wire:click.prevent="deleteTask('{{ $task->id }}')"
                                        wire:confirm="{{ __('Aufgabe wirklich löschen?') }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-rose-100 hover:text-rose-700 dark:hover:bg-rose-900/30">
                                    <flux:icon.trash class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <flux:modal name="task-edit" class="max-w-xl">
        <form wire:submit="saveEditTask" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Aufgabe bearbeiten') }}</flux:heading>
            </div>

            <flux:input wire:model="editTitle" :label="__('Überschrift')" required />

            <flux:textarea wire:model="editDescription" :label="__('Beschreibung')" rows="3" />

            <flux:field>
                <flux:label>{{ __('Fällig') }}</flux:label>
                <flux:input type="date" wire:model="editDueDate" class="max-w-xs" />
            </flux:field>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <flux:label>{{ __('Personen (RACI)') }}</flux:label>
                    <flux:button type="button" size="sm" variant="filled" icon="plus" wire:click.prevent="addEditAssignee">
                        {{ __('Person hinzufügen') }}
                    </flux:button>
                </div>

                @if (empty($editAssignees))
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Noch keine Personen zugeordnet.') }}
                    </flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($editAssignees as $i => $row)
                            <div wire:key="edit-assignee-{{ $i }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                <flux:select wire:model="editAssignees.{{ $i }}.employee_id" class="flex-1" required>
                                    <flux:select.option value="">{{ __('Mitarbeiter wählen') }}</flux:select.option>
                                    @foreach ($this->employeesForSelect as $e)
                                        <flux:select.option value="{{ $e->id }}">
                                            {{ $e->fullName() }}@if ($e->position) · {{ $e->position }}@endif
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="editAssignees.{{ $i }}.raci_role" class="sm:w-64">
                                    @foreach (\App\Enums\RaciRole::cases() as $r)
                                        <flux:select.option value="{{ $r->value }}">{{ $r->value }} – {{ $r->label() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:button type="button" size="sm" variant="ghost" icon="x-mark"
                                    wire:click.prevent="removeEditAssignee({{ $i }})" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <flux:label>{{ __('Dienstleister (RACI)') }}</flux:label>
                    <flux:button type="button" size="sm" variant="filled" icon="plus" wire:click.prevent="addEditProvider">
                        {{ __('Dienstleister hinzufügen') }}
                    </flux:button>
                </div>

                @if (empty($editProviders))
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Noch keine Dienstleister zugeordnet.') }}
                    </flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($editProviders as $i => $row)
                            <div wire:key="edit-provider-{{ $i }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                <flux:select wire:model="editProviders.{{ $i }}.provider_id" class="flex-1" required>
                                    <flux:select.option value="">{{ __('Dienstleister wählen') }}</flux:select.option>
                                    @foreach ($this->providersForSelect as $p)
                                        <flux:select.option value="{{ $p->id }}">
                                            {{ $p->name }}@if ($p->hotline) · {{ $p->hotline }}@endif
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="editProviders.{{ $i }}.raci_role" class="sm:w-64">
                                    @foreach (\App\Enums\RaciRole::cases() as $r)
                                        <flux:select.option value="{{ $r->value }}">{{ $r->value }} – {{ $r->label() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:button type="button" size="sm" variant="ghost" icon="x-mark"
                                    wire:click.prevent="removeEditProvider({{ $i }})" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <flux:label>{{ __('Rollen (RACI)') }}</flux:label>
                    <flux:button type="button" size="sm" variant="filled" icon="plus" wire:click.prevent="addEditRole">
                        {{ __('Rolle hinzufügen') }}
                    </flux:button>
                </div>
                <flux:description>
                    {{ __('Mitglieder der gewählten Rolle werden automatisch als verantwortliche Personen angezeigt.') }}
                </flux:description>

                @if (empty($editRoles))
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Noch keine Rollen zugeordnet.') }}
                    </flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($editRoles as $i => $row)
                            <div wire:key="edit-role-{{ $i }}" class="space-y-1.5 rounded-md border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                    <flux:select wire:model.live="editRoles.{{ $i }}.role_id" class="flex-1" required>
                                        <flux:select.option value="">{{ __('Rolle wählen') }}</flux:select.option>
                                        @foreach ($this->rolesForSelect as $r)
                                            <flux:select.option value="{{ $r->id }}">{{ $r->name }} ({{ $r->employees->count() }})</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:select wire:model="editRoles.{{ $i }}.raci_role" class="sm:w-64">
                                        @foreach (\App\Enums\RaciRole::cases() as $r)
                                            <flux:select.option value="{{ $r->value }}">{{ $r->value }} – {{ $r->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click.prevent="removeEditRole({{ $i }})" />
                                </div>
                                @if ($row['role_id'])
                                    <div class="px-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $this->roleMembersText($row['role_id']) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
