<?php

use App\Enums\LessonLearnedActionItemStatus;
use App\Models\Employee;
use App\Models\LessonLearned;
use App\Models\LessonLearnedActionItem;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Lessons-Learned-Detail')] class extends Component {
    public LessonLearned $lesson;

    public string $editing_field = '';

    public string $field_value = '';

    public string $new_action_description = '';

    public ?string $new_action_responsible = null;

    public ?string $new_action_due_date = null;

    public function mount(LessonLearned $lesson): void
    {
        abort_if($lesson->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->lesson = $lesson->load(['incidentReport', 'scenarioRun', 'handbookVersion', 'author', 'actionItems.responsibleEmployee']);
    }

    #[Computed]
    public function employees()
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    public function startEdit(string $field): void
    {
        if (! in_array($field, ['title', 'root_cause', 'what_went_well', 'what_went_poorly'], true)) {
            return;
        }

        $this->editing_field = $field;
        $this->field_value = (string) ($this->lesson->{$field} ?? '');
        Flux::modal('lesson-edit')->show();
    }

    public function saveField(): void
    {
        $this->validate([
            'editing_field' => ['required', 'in:title,root_cause,what_went_well,what_went_poorly'],
            'field_value' => $this->editing_field === 'title'
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:5000'],
        ]);

        $this->lesson->forceFill([
            $this->editing_field => $this->editing_field === 'title'
                ? $this->field_value
                : ($this->field_value ?: null),
        ])->save();

        $this->lesson->refresh();
        Flux::modal('lesson-edit')->close();
    }

    public function toggleFinalized(): void
    {
        $this->lesson->finalized_at = $this->lesson->finalized_at ? null : now();
        $this->lesson->save();
        $this->lesson->refresh();
    }

    public function openAddAction(): void
    {
        $this->reset(['new_action_description', 'new_action_responsible', 'new_action_due_date']);
        Flux::modal('action-add')->show();
    }

    public function addAction(): void
    {
        $validated = $this->validate([
            'new_action_description' => ['required', 'string', 'max:500'],
            'new_action_responsible' => ['nullable', 'uuid', 'exists:employees,id'],
            'new_action_due_date' => ['nullable', 'date'],
        ]);

        $this->lesson->actionItems()->create([
            'description' => $validated['new_action_description'],
            'responsible_employee_id' => $validated['new_action_responsible'],
            'due_date' => $validated['new_action_due_date'],
            'status' => LessonLearnedActionItemStatus::Open,
        ]);

        $this->lesson->load('actionItems.responsibleEmployee');
        Flux::modal('action-add')->close();
    }

    public function cycleStatus(string $actionItemId): void
    {
        $action = LessonLearnedActionItem::whereKey($actionItemId)
            ->whereHas('lessonLearned', fn ($q) => $q->where('id', $this->lesson->id))
            ->first();

        if (! $action) {
            return;
        }

        $next = match ($action->status) {
            LessonLearnedActionItemStatus::Open => LessonLearnedActionItemStatus::InProgress,
            LessonLearnedActionItemStatus::InProgress => LessonLearnedActionItemStatus::Done,
            LessonLearnedActionItemStatus::Done => LessonLearnedActionItemStatus::Cancelled,
            LessonLearnedActionItemStatus::Cancelled => LessonLearnedActionItemStatus::Open,
        };

        $action->forceFill([
            'status' => $next,
            'completed_at' => $next === LessonLearnedActionItemStatus::Done ? now() : null,
        ])->save();

        $this->lesson->load('actionItems.responsibleEmployee');
    }

    public function deleteAction(string $actionItemId): void
    {
        LessonLearnedActionItem::whereKey($actionItemId)
            ->whereHas('lessonLearned', fn ($q) => $q->where('id', $this->lesson->id))
            ->delete();

        $this->lesson->load('actionItems.responsibleEmployee');
    }
}; ?>

<section class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('lessons-learned.index')" wire:navigate>
                {{ __('Zurück') }}
            </flux:button>
            <div class="mt-2 flex items-center gap-2">
                <flux:heading size="xl">{{ $lesson->title }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon="pencil" wire:click="startEdit('title')" />
            </div>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Erstellt:') }} {{ $lesson->created_at->format('d.m.Y H:i') }}
                @if ($lesson->author) · {{ $lesson->author->name }} @endif
                @if ($lesson->incidentReport)
                    · <a class="underline" href="{{ route('incidents.show', $lesson->incidentReport) }}" wire:navigate>{{ __('Vorfall:') }} {{ $lesson->incidentReport->title }}</a>
                @endif
                @if ($lesson->scenarioRun)
                    · <a class="underline" href="{{ route('scenario-runs.show', $lesson->scenarioRun) }}" wire:navigate>{{ __('Übung:') }} {{ $lesson->scenarioRun->title }}</a>
                @endif
                @if ($lesson->handbookVersion)
                    · <a class="underline" href="{{ route('handbook-versions.index') }}" wire:navigate>{{ __('Handbuch-Version:') }} {{ $lesson->handbookVersion->version }}</a>
                @endif
            </flux:text>
        </div>
        <flux:button
            size="sm"
            :variant="$lesson->finalized_at ? 'filled' : 'primary'"
            wire:click="toggleFinalized"
        >
            {{ $lesson->finalized_at ? __('Finalisierung aufheben') : __('Finalisieren') }}
        </flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ([
            'root_cause' => __('Ursache (Root Cause)'),
            'what_went_well' => __('Was lief gut?'),
            'what_went_poorly' => __('Was lief nicht gut?'),
        ] as $field => $heading)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2 flex items-center justify-between">
                    <flux:heading size="md">{{ $heading }}</flux:heading>
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="startEdit('{{ $field }}')" />
                </div>
                <flux:text class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">
                    {{ $lesson->{$field} ?: __('— noch nichts erfasst —') }}
                </flux:text>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between gap-4">
            <flux:heading size="md">{{ __('Maßnahmen (Action Items)') }}</flux:heading>
            <flux:button size="sm" variant="primary" icon="plus" wire:click="openAddAction">
                {{ __('Maßnahme') }}
            </flux:button>
        </div>

        @forelse ($lesson->actionItems as $action)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 py-3 first:border-t-0 dark:border-zinc-800">
                <div class="min-w-0 flex-1">
                    <div class="font-medium">{{ $action->description }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        @if ($action->responsibleEmployee)
                            <span>{{ $action->responsibleEmployee->first_name }} {{ $action->responsibleEmployee->last_name }}</span>
                        @endif
                        @if ($action->due_date)
                            <span>{{ __('fällig:') }} {{ $action->due_date->format('d.m.Y') }}</span>
                        @endif
                        @if ($action->isOverdue())
                            <flux:badge color="rose" size="sm">{{ __('überfällig') }}</flux:badge>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="cycleStatus('{{ $action->id }}')"
                        class="cursor-pointer"
                        title="{{ __('Status wechseln') }}"
                    >
                        <flux:badge :color="$action->status->color()">{{ $action->status->label() }}</flux:badge>
                    </button>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        wire:click="deleteAction('{{ $action->id }}')"
                        wire:confirm="{{ __('Maßnahme wirklich löschen?') }}"
                    />
                </div>
            </div>
        @empty
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Noch keine Maßnahmen erfasst.') }}
            </flux:text>
        @endforelse
    </div>

    <flux:modal name="lesson-edit" class="max-w-xl">
        <form wire:submit="saveField" class="space-y-5">
            <flux:heading size="lg">{{ __('Bearbeiten') }}</flux:heading>
            @if ($editing_field === 'title')
                <flux:input wire:model="field_value" :label="__('Titel')" required />
            @else
                <flux:textarea wire:model="field_value" :label="__('Text')" rows="6" />
            @endif
            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="action-add" class="max-w-xl">
        <form wire:submit="addAction" class="space-y-5">
            <flux:heading size="lg">{{ __('Maßnahme hinzufügen') }}</flux:heading>

            <flux:input wire:model="new_action_description" :label="__('Beschreibung')" required />

            <flux:select wire:model="new_action_responsible" :label="__('Verantwortlich (Mitarbeiter)')">
                <flux:select.option value="">{{ __('— optional —') }}</flux:select.option>
                @foreach ($this->employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">
                        {{ $employee->first_name }} {{ $employee->last_name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="new_action_due_date" :label="__('Fälligkeit')" type="date" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Hinzufügen') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
