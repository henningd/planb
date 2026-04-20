<?php

use App\Enums\RaciRole;
use App\Models\Employee;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
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

    public ?string $editingTaskId = null;

    public string $editTitle = '';

    public ?string $editDescription = '';

    public ?string $editDueDate = null;

    /** @var array<int, array{employee_id: string, raci_role: string}> */
    public array $editAssignees = [];

    /** @var array<int, array{provider_id: string, raci_role: string}> */
    public array $editProviders = [];

    public function mount(System $system): void
    {
        abort_unless(Auth::user()->currentCompany(), 403);

        $this->system = $system->load([
            'priority',
            'serviceProviders',
            'employees',
            'dependencies',
            'dependents',
        ]);
    }

    /**
     * Open tasks first (by due date, nulls last), then completed.
     *
     * @return Collection<int, SystemTask>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return SystemTask::with(['assignees', 'providerAssignees'])
            ->where('system_id', $this->system->id)
            ->get()
            ->sort(function (SystemTask $a, SystemTask $b) {
                if ($a->isDone() !== $b->isDone()) {
                    return $a->isDone() ? 1 : -1;
                }

                $aDue = $a->due_date?->timestamp ?? PHP_INT_MAX;
                $bDue = $b->due_date?->timestamp ?? PHP_INT_MAX;

                if ($aDue !== $bDue) {
                    return $aDue <=> $bDue;
                }

                return $a->created_at <=> $b->created_at;
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
        ]);

        $task = SystemTask::create([
            'system_id' => $this->system->id,
            'title' => $validated['newTaskTitle'],
            'description' => $validated['newTaskDescription'] ?: null,
            'due_date' => $validated['newTaskDueDate'] ?: null,
        ]);

        $this->syncAssignees($task, $validated['newTaskAssignees'] ?? []);
        $this->syncProviderAssignees($task, $validated['newTaskProviders'] ?? []);

        $this->reset(['newTaskTitle', 'newTaskDescription', 'newTaskDueDate', 'newTaskAssignees', 'newTaskProviders']);
        unset($this->tasks);

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

    public function deleteTask(string $id): void
    {
        SystemTask::where('system_id', $this->system->id)->findOrFail($id)->delete();

        unset($this->tasks);

        Flux::toast(variant: 'success', text: __('Aufgabe gelöscht.'));
    }

    public function openEditTask(string $id): void
    {
        $task = SystemTask::with(['assignees', 'providerAssignees'])
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

        Flux::modal('task-edit')->close();
        $this->reset(['editingTaskId', 'editTitle', 'editDescription', 'editDueDate', 'editAssignees', 'editProviders']);
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

        $task->assignees()->sync($sync);
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

        $task->providerAssignees()->sync($sync);
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
            </div>

            <div x-show="tab === 'employees'" x-cloak>
                @if ($system->employees->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Verantwortlichen zugewiesen.') }}</flux:text>
                @else
                    <ol class="space-y-2">
                        @foreach ($system->employees as $index => $e)
                            <li class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                                        <span class="font-medium">{{ $e->fullName() }}</span>
                                        @if ($e->position)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $e->position }}</span>
                                        @endif
                                        @if ($e->department)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $e->department }}</span>
                                        @endif
                                    </div>
                                    @if ($e->mobile_phone || $e->work_phone || $e->email)
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($e->mobile_phone)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.device-phone-mobile class="h-3 w-3" />
                                                    {{ $e->mobile_phone }}
                                                </span>
                                            @endif
                                            @if ($e->work_phone)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.phone class="h-3 w-3" />
                                                    {{ $e->work_phone }}
                                                </span>
                                            @endif
                                            @if ($e->email)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.envelope class="h-3 w-3" />
                                                    {{ $e->email }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($e->pivot->note)
                                        <div class="mt-1 text-xs italic text-zinc-600 dark:text-zinc-400">
                                            {{ $e->pivot->note }}
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div x-show="tab === 'providers'" x-cloak>
                @if ($system->serviceProviders->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Keine Dienstleister zugeordnet.') }}</flux:text>
                @else
                    <ol class="space-y-2">
                        @foreach ($system->serviceProviders as $index => $p)
                            <li class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                                        <span class="font-medium">{{ $p->name }}</span>
                                        @if ($p->contact_name)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $p->contact_name }}</span>
                                        @endif
                                    </div>
                                    @if ($p->hotline || $p->email)
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($p->hotline)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.phone class="h-3 w-3" />
                                                    {{ $p->hotline }}
                                                </span>
                                            @endif
                                            @if ($p->email)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.envelope class="h-3 w-3" />
                                                    {{ $p->email }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($p->pivot->note)
                                        <div class="mt-1 text-xs italic text-zinc-600 dark:text-zinc-400">
                                            {{ $p->pivot->note }}
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div x-show="tab === 'dependencies'" x-cloak class="space-y-5">
                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Braucht:') }}</flux:heading>
                    @if ($system->dependencies->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Dieses System hat keine Abhängigkeiten.') }}</flux:text>
                    @else
                        <ol class="space-y-2">
                            @foreach ($system->dependencies as $index => $dep)
                                <li class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                                            <span class="font-medium">{{ $dep->name }}</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">· {{ $dep->category->label() }}</span>
                                        </div>
                                        @if ($dep->pivot->note)
                                            <div class="mt-1 text-xs italic text-zinc-600 dark:text-zinc-400">
                                                {{ $dep->pivot->note }}
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Blockiert:') }}</flux:heading>
                    @if ($system->dependents->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kein anderes System hängt davon ab.') }}</flux:text>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($system->dependents as $dep)
                                <flux:badge color="violet">{{ $dep->name }}</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Aufgaben-Bereich unter der Systemkarte --}}
    <div class="mb-6 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-4 border-b border-zinc-100 p-6 dark:border-zinc-800">
            <div>
                <flux:heading size="lg">{{ __('Aufgaben') }}</flux:heading>
                <flux:subheading>
                    {{ __('Wartungs-, Vorbereitungs- und Nachbesserungs-Aufgaben zu diesem System. Personen werden nach RACI zugeordnet.') }}
                </flux:subheading>
            </div>
            @php($openCount = $this->tasks->whereNull('completed_at')->count())
            @if ($openCount > 0)
                <flux:badge color="teal">{{ $openCount }} {{ __('offen') }}</flux:badge>
            @endif
        </div>

        <div class="space-y-6 p-6">
            <form wire:submit="addTask" class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/50">
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

                <div class="flex justify-end border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:button type="submit" variant="primary" icon="plus">
                        {{ __('Aufgabe anlegen') }}
                    </flux:button>
                </div>
            </form>

            @if ($this->tasks->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('Noch keine Aufgaben erfasst.') }}
                </div>
            @else
                <ul class="space-y-2">
                    @foreach ($this->tasks as $task)
                        <li wire:key="task-{{ $task->id }}"
                            class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 {{ $task->isDone() ? 'bg-zinc-50 dark:bg-zinc-950/50' : '' }}">
                            <button type="button"
                                wire:click.prevent="toggleTask('{{ $task->id }}')"
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 {{ $task->isDone() ? 'border-teal-600 bg-teal-600 text-white' : 'border-zinc-300 dark:border-zinc-600' }}"
                                :title="'{{ $task->isDone() ? __('Wiederöffnen') : __('Abhaken') }}'"
                            >
                                @if ($task->isDone())
                                    <flux:icon.check class="h-3.5 w-3.5" />
                                @endif
                            </button>

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
                                @if ($task->assignees->isNotEmpty() || $task->providerAssignees->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                        @foreach ($task->assignees as $person)
                                            @php($raci = \App\Enums\RaciRole::tryFrom($person->pivot->raci_role))
                                            <flux:badge
                                                :color="$raci?->badgeColor() ?? 'zinc'"
                                                size="sm"
                                                :title="$raci?->label()"
                                                icon="user"
                                            >
                                                <span class="font-bold">{{ $person->pivot->raci_role }}</span>
                                                <span class="ml-1">{{ $person->fullName() }}</span>
                                            </flux:badge>
                                        @endforeach
                                        @foreach ($task->providerAssignees as $prov)
                                            @php($raci = \App\Enums\RaciRole::tryFrom($prov->pivot->raci_role))
                                            <flux:badge
                                                :color="$raci?->badgeColor() ?? 'zinc'"
                                                size="sm"
                                                :title="$raci?->label()"
                                                icon="wrench-screwdriver"
                                            >
                                                <span class="font-bold">{{ $prov->pivot->raci_role }}</span>
                                                <span class="ml-1">{{ $prov->name }}</span>
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
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

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
