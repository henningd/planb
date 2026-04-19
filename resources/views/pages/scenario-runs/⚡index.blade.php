<?php

use App\Models\ScenarioRun;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Protokolle & Übungen')] class extends Component {
    #[Computed]
    public function runs()
    {
        return ScenarioRun::with(['scenario', 'startedBy', 'steps'])
            ->orderByDesc('started_at')
            ->get();
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Protokolle & Übungen') }}</flux:heading>
            <flux:subheading>
                {{ __('Aktive Ernstfälle und abgeschlossene Übungen. Jeder Durchlauf wird mit Zeitstempel und Verantwortlichen dokumentiert.') }}
            </flux:subheading>
        </div>
        <flux:button :href="route('scenarios.index')" wire:navigate variant="primary" icon="plus">
            {{ __('Szenario starten') }}
        </flux:button>
    </div>

    <div class="space-y-3">
        @forelse ($this->runs as $run)
            @php
                $checked = $run->steps->whereNotNull('checked_at')->count();
                $total = $run->steps->count();
                $isActive = $run->isActive();
            @endphp
            <a href="{{ route('scenario-runs.show', $run) }}" wire:navigate
               class="block rounded-xl border border-zinc-200 bg-white p-5 hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <flux:badge :color="$run->mode->color()" size="sm">
                                {{ $run->mode->label() }}
                            </flux:badge>
                            @if ($isActive)
                                <flux:badge color="emerald" size="sm">{{ __('Aktiv') }}</flux:badge>
                            @elseif ($run->aborted_at)
                                <flux:badge color="zinc" size="sm">{{ __('Abgebrochen') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Abgeschlossen') }}</flux:badge>
                            @endif
                            <span class="font-medium">{{ $run->title }}</span>
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Gestartet') }}: {{ $run->started_at->format('d.m.Y H:i') }}
                            @if ($run->startedBy) · {{ $run->startedBy->name }} @endif
                            @if ($run->ended_at) · {{ __('Beendet') }}: {{ $run->ended_at->format('d.m.Y H:i') }} @endif
                        </flux:text>
                    </div>
                    <div class="text-right">
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $checked }} / {{ $total }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Schritte') }}</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Durchläufe protokolliert.') }}
                </flux:text>
            </div>
        @endforelse
    </div>
</section>
