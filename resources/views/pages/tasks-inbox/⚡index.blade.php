<?php

use App\Enums\RaciRole;
use App\Models\Employee;
use App\Models\System;
use App\Models\SystemTask;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Aufgaben-Inbox')] class extends Component {
    public string $statusFilter = 'open';

    public string $systemFilter = '';

    public string $assigneeFilter = 'all';

    public string $search = '';

    public function currentEmployeeId(): ?string
    {
        $user = Auth::user();
        if ($user === null) {
            return null;
        }

        $employee = Employee::where('email', $user->email)->first();

        return $employee?->id;
    }

    /**
     * @return Collection<int, SystemTask>
     */
    #[Computed]
    public function tasks(): Collection
    {
        $query = SystemTask::with(['system', 'assignees'])
            ->when($this->systemFilter !== '', fn ($q) => $q->where('system_id', $this->systemFilter))
            ->when($this->search !== '', function ($q) {
                $term = '%'.strtolower($this->search).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(title) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$term]);
                });
            });

        if ($this->statusFilter === 'open') {
            $query->whereNull('completed_at');
        } elseif ($this->statusFilter === 'overdue') {
            $query->whereNull('completed_at')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString());
        } elseif ($this->statusFilter === 'done') {
            $query->whereNotNull('completed_at');
        }

        if ($this->assigneeFilter === 'mine') {
            $employeeId = $this->currentEmployeeId();
            if ($employeeId === null) {
                return new Collection;
            }
            $query->whereHas('assignees', fn ($q) => $q->where('employees.id', $employeeId));
        } elseif ($this->assigneeFilter === 'others') {
            $employeeId = $this->currentEmployeeId();
            if ($employeeId !== null) {
                $query->whereDoesntHave('assignees', fn ($q) => $q->where('employees.id', $employeeId));
            }
        }

        $tasks = $query->get();

        $today = now()->startOfDay();

        return $tasks->sort(function (SystemTask $a, SystemTask $b) use ($today) {
            $bucket = function (SystemTask $t) use ($today): int {
                if ($t->isDone()) {
                    return 2;
                }
                if ($t->due_date !== null && $t->due_date->lt($today)) {
                    return 0;
                }

                return 1;
            };

            $ba = $bucket($a);
            $bb = $bucket($b);
            if ($ba !== $bb) {
                return $ba <=> $bb;
            }

            if ($ba === 0) {
                return $a->due_date <=> $b->due_date;
            }

            if ($ba === 1) {
                $aDue = $a->due_date;
                $bDue = $b->due_date;
                if ($aDue === null && $bDue === null) {
                    return 0;
                }
                if ($aDue === null) {
                    return 1;
                }
                if ($bDue === null) {
                    return -1;
                }

                return $aDue <=> $bDue;
            }

            return ($b->completed_at ?? now()) <=> ($a->completed_at ?? now());
        })->values();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function systemOptions(): array
    {
        return System::orderBy('name')->pluck('name', 'id')->all();
    }

    #[Computed]
    public function openCount(): int
    {
        return SystemTask::whereNull('completed_at')->count();
    }

    #[Computed]
    public function overdueCount(): int
    {
        return SystemTask::whereNull('completed_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
    }

    #[Computed]
    public function doneTodayCount(): int
    {
        return SystemTask::whereNotNull('completed_at')
            ->whereDate('completed_at', now()->toDateString())
            ->count();
    }

    public function toggleTask(string $id): void
    {
        $task = SystemTask::findOrFail($id);

        if ($task->isDone()) {
            $task->update(['completed_at' => null]);
            Flux::toast(variant: 'success', text: __('Aufgabe wieder geöffnet.'));
        } else {
            $task->update(['completed_at' => now()]);
            Flux::toast(variant: 'success', text: __('Aufgabe als erledigt markiert.'));
        }

        unset($this->tasks, $this->openCount, $this->overdueCount, $this->doneTodayCount);
    }

    public function resetFilters(): void
    {
        $this->statusFilter = 'open';
        $this->systemFilter = '';
        $this->assigneeFilter = 'all';
        $this->search = '';
    }

    public function hasActiveFilters(): bool
    {
        return $this->statusFilter !== 'open'
            || $this->systemFilter !== ''
            || $this->assigneeFilter !== 'all'
            || $this->search !== '';
    }

    public function dueLabel(SystemTask $task): array
    {
        if ($task->due_date === null) {
            return ['text' => __('Ohne Fälligkeit'), 'color' => 'zinc'];
        }

        $today = now()->startOfDay();
        $endOfWeek = now()->endOfWeek();

        if ($task->isOverdue()) {
            return ['text' => __('Überfällig: :date', ['date' => $task->due_date->format('d.m.Y')]), 'color' => 'red'];
        }
        if ($task->due_date->isSameDay($today)) {
            return ['text' => __('Heute'), 'color' => 'amber'];
        }
        if ($task->due_date->lte($endOfWeek)) {
            return ['text' => __('Diese Woche: :date', ['date' => $task->due_date->format('d.m.')]), 'color' => 'sky'];
        }

        return ['text' => $task->due_date->format('d.m.Y'), 'color' => 'zinc'];
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Aufgaben-Inbox') }}</flux:heading>
        <flux:subheading>{{ __('Zentrale Sicht aller System-Aufgaben.') }}</flux:subheading>
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center gap-2 text-sm">
            <flux:badge color="sky" size="sm">{{ $this->openCount }} {{ __('offen') }}</flux:badge>
            <flux:badge color="red" size="sm">{{ $this->overdueCount }} {{ __('überfällig') }}</flux:badge>
            <flux:badge color="emerald" size="sm">{{ $this->doneTodayCount }} {{ __('heute erledigt') }}</flux:badge>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:select wire:model.live="statusFilter" :label="__('Status')" class="min-w-40">
            <flux:select.option value="all">{{ __('Alle') }}</flux:select.option>
            <flux:select.option value="open">{{ __('Offen') }}</flux:select.option>
            <flux:select.option value="overdue">{{ __('Überfällig') }}</flux:select.option>
            <flux:select.option value="done">{{ __('Erledigt') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="systemFilter" :label="__('System')" class="min-w-48">
            <flux:select.option value="">{{ __('Alle Systeme') }}</flux:select.option>
            @foreach ($this->systemOptions as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="assigneeFilter" :label="__('Verantwortlich')" class="min-w-48">
            <flux:select.option value="all">{{ __('Alle') }}</flux:select.option>
            <flux:select.option value="mine">{{ __('Mir zugewiesen') }}</flux:select.option>
            <flux:select.option value="others">{{ __('Andere') }}</flux:select.option>
        </flux:select>

        <flux:input wire:model.live.debounce.300ms="search" :label="__('Suche')" type="search" :placeholder="__('Titel oder Beschreibung…')" class="min-w-56" />

        @if ($this->hasActiveFilters())
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters" type="button">
                {{ __('Filter zurücksetzen') }}
            </flux:button>
        @endif
    </div>

    @if ($assigneeFilter === 'mine' && $this->currentEmployeeId() === null)
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Ihr Account ist nicht mit einem Mitarbeiter verknüpft (gleiche E-Mail wie der Account erforderlich).') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->tasks as $task)
            @php
                $due = $this->dueLabel($task);
                $responsibleAssignees = $task->assignees->filter(fn ($e) => $e->pivot->raci_role === RaciRole::Responsible->value);
            @endphp
            <div @class([
                'flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 sm:flex-row sm:items-start sm:justify-between dark:border-zinc-700 dark:bg-zinc-900',
                'opacity-70' => $task->isDone(),
            ])>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="base" :class="$task->isDone() ? 'line-through' : ''">{{ $task->title }}</flux:heading>
                        <flux:badge color="zinc" size="sm">{{ $task->system?->name }}</flux:badge>
                        <flux:badge color="{{ $due['color'] }}" size="sm">{{ $due['text'] }}</flux:badge>
                        @if ($task->isDone())
                            <flux:badge color="emerald" size="sm">{{ __('Erledigt') }}</flux:badge>
                        @endif
                    </div>
                    @if ($task->description)
                        <flux:text class="mt-1 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $task->description }}</flux:text>
                    @endif
                    @if ($responsibleAssignees->isNotEmpty())
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">R:</span>
                            @foreach ($responsibleAssignees as $emp)
                                <flux:badge color="amber" size="sm" inset="top bottom">{{ $emp->fullName() }}</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="shrink-0">
                    @if ($task->isDone())
                        <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="toggleTask('{{ $task->id }}')" type="button">
                            {{ __('Wieder öffnen') }}
                        </flux:button>
                    @else
                        <flux:button size="sm" variant="primary" icon="check" wire:click="toggleTask('{{ $task->id }}')" type="button">
                            {{ __('Erledigt') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Keine Aufgaben gefunden.') }}
                </flux:text>
                @if ($this->hasActiveFilters())
                    <div class="mt-3">
                        <flux:button size="sm" variant="filled" icon="x-mark" wire:click="resetFilters" type="button">
                            {{ __('Filter zurücksetzen') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</section>
