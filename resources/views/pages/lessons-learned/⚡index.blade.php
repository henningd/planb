<?php

use App\Enums\LessonLearnedActionItemStatus;
use App\Models\LessonLearned;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Lessons Learned')] class extends Component {
    public string $filter = 'all';

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    #[Computed]
    public function lessons()
    {
        $query = LessonLearned::with(['incidentReport', 'scenarioRun', 'author', 'actionItems'])
            ->orderByDesc('created_at');

        if ($this->filter === 'open') {
            $query->whereNull('finalized_at');
        } elseif ($this->filter === 'finalized') {
            $query->whereNotNull('finalized_at');
        }

        return $query->get();
    }

    public function setFilter(string $value): void
    {
        $this->filter = in_array($value, ['all', 'open', 'finalized'], true) ? $value : 'all';
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Lessons Learned') }}</flux:heading>
            <flux:subheading>
                {{ __('Strukturierte Auswertung nach Übungen und Vorfällen — Ursache, was lief gut, was lief schlecht, plus konkrete Maßnahmen.') }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('lessons-learned.create')" wire:navigate :disabled="! $this->hasCompany">
            {{ __('Neue Auswertung') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="mb-4 flex items-center gap-2">
        <flux:button size="sm" :variant="$filter === 'all' ? 'primary' : 'filled'" wire:click="setFilter('all')">
            {{ __('Alle') }}
        </flux:button>
        <flux:button size="sm" :variant="$filter === 'open' ? 'primary' : 'filled'" wire:click="setFilter('open')">
            {{ __('Offen') }}
        </flux:button>
        <flux:button size="sm" :variant="$filter === 'finalized' ? 'primary' : 'filled'" wire:click="setFilter('finalized')">
            {{ __('Finalisiert') }}
        </flux:button>
    </div>

    <div class="space-y-3">
        @forelse ($this->lessons as $lesson)
            @php
                $openActions = $lesson->actionItems
                    ->whereIn('status', [LessonLearnedActionItemStatus::Open, LessonLearnedActionItemStatus::InProgress])
                    ->count();
                $totalActions = $lesson->actionItems->count();
                $overdue = $lesson->actionItems->filter(fn ($a) => $a->isOverdue())->count();
                $subjectLabel = $lesson->incidentReport?->title ?? $lesson->scenarioRun?->title;
                $subjectKind = $lesson->incidentReport_id !== null ? __('Vorfall') : ($lesson->scenario_run_id !== null ? __('Übung/Lage') : __('Frei'));
            @endphp
            <a href="{{ route('lessons-learned.show', $lesson) }}" wire:navigate
               class="block rounded-xl border border-zinc-200 bg-white p-5 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge color="zinc" size="sm">{{ $subjectKind }}</flux:badge>
                            @if ($lesson->finalized_at)
                                <flux:badge color="emerald" size="sm">{{ __('Finalisiert') }}</flux:badge>
                            @endif
                            @if ($overdue > 0)
                                <flux:badge color="rose" size="sm">{{ $overdue }} {{ __('überfällig') }}</flux:badge>
                            @endif
                            <span class="font-medium">{{ $lesson->title }}</span>
                        </div>
                        @if ($subjectLabel)
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Bezug:') }} {{ $subjectLabel }}
                            </flux:text>
                        @endif
                        <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Erstellt:') }} {{ $lesson->created_at->format('d.m.Y') }}
                            @if ($lesson->author)
                                · {{ $lesson->author->name }}
                            @endif
                        </flux:text>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">
                            {{ $totalActions - $openActions }} / {{ $totalActions }}
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Maßnahmen erledigt') }}</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Auswertung erfasst. Nach jeder Übung und jedem Vorfall lohnt sich eine kurze Lessons-Learned-Notiz.') }}
                </flux:text>
            </div>
        @endforelse
    </div>
</section>
