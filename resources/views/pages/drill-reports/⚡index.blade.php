<?php

use App\Support\Reports\DrillReport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Übungsberichte')] class extends Component {
    /**
     * @return \Illuminate\Support\Collection<int, DrillReport>
     */
    #[Computed]
    public function reports()
    {
        return DrillReport::completedDrillsQuery()
            ->with(['scenario', 'startedBy', 'steps.checkedBy', 'acknowledgements.user'])
            ->orderByDesc('started_at')
            ->get()
            ->map(fn ($run) => new DrillReport($run));
    }

    #[Computed]
    public function memberCount(): int
    {
        return (int) Auth::user()?->currentTeam?->members()->count();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Übungsberichte') }}</flux:heading>
            <flux:subheading>
                {{ __('Auswertung aller abgeschlossenen Übungen — als Nachweis für Prüfer, Versicherer und das Management. Jeder Bericht lässt sich als PDF exportieren.') }}
            </flux:subheading>
        </div>
        <flux:button :href="route('scenario-runs.index')" wire:navigate variant="ghost" icon="clipboard-document-check">
            {{ __('Protokolle & Übungen') }}
        </flux:button>
    </div>

    @if ($this->reports->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Noch keine abgeschlossenen Übungen. Starten Sie ein Szenario im Übungsmodus — sobald es beendet ist, erscheint hier der Bericht.') }}
            </flux:text>
            <div class="mt-4">
                <flux:button :href="route('scenarios.index')" wire:navigate variant="primary" icon="bolt" size="sm">
                    {{ __('Szenario starten') }}
                </flux:button>
            </div>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">{{ __('Datum') }}</th>
                        <th class="px-4 py-3">{{ __('Szenario') }}</th>
                        <th class="px-4 py-3">{{ __('Dauer') }}</th>
                        <th class="px-4 py-3">{{ __('Beteiligte') }}</th>
                        <th class="px-4 py-3">{{ __('Quittierungs-Quote') }}</th>
                        <th class="px-4 py-3">{{ __('Ausgang') }}</th>
                        <th class="px-4 py-3"><span class="sr-only">{{ __('Aktionen') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->reports as $report)
                        @php($run = $report->run)
                        <tr wire:key="report-{{ $run->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $run->started_at?->format('d.m.Y H:i') ?? '–' }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:link :href="route('drill-reports.show', $run)" wire:navigate class="font-medium">
                                    {{ $run->scenario?->name ?? $run->title }}
                                </flux:link>
                                @if ($run->scenario && $run->title && $run->title !== $run->scenario->name)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $run->title }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ DrillReport::formatDuration($report->durationSeconds()) }}
                            </td>
                            <td class="px-4 py-3">{{ $report->participantCount() }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if (($rate = $report->acknowledgementRate($this->memberCount)) !== null)
                                    {{ $rate }} %
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        ({{ $report->acknowledgedUserCount() }}/{{ $this->memberCount }})
                                    </span>
                                @else
                                    –
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$report->wasAborted() ? 'amber' : 'emerald'" size="sm">
                                    {{ __($report->outcomeLabel()) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:button :href="route('drill-reports.show', $run)" wire:navigate size="xs" variant="ghost" icon="document-chart-bar">
                                    {{ __('Bericht') }}
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
