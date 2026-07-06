<?php

use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use App\Support\Reports\DrillReport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Übungsbericht')] class extends Component {
    public ScenarioRun $run;

    public function mount(ScenarioRun $run): void
    {
        abort_if($run->company_id !== Auth::user()->currentCompany()?->id, 403);

        // Berichte gibt es nur für abgeschlossene Übungs-Läufe — laufende
        // Übungen und Ernstfälle gehören zu „Protokolle & Übungen".
        abort_unless($run->isDrill() && ! $run->isActive(), 404);

        $this->run = $run;
    }

    #[Computed]
    public function report(): DrillReport
    {
        return DrillReport::for($this->run);
    }
}; ?>

<section class="w-full">
    @php($report = $this->report)

    <div class="mb-2">
        <flux:link :href="route('drill-reports.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Übungsberichte') }}
        </flux:link>
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:badge color="indigo">{{ __('Übung') }}</flux:badge>
                <flux:badge :color="$report->wasAborted() ? 'amber' : 'emerald'">
                    {{ __($report->outcomeLabel()) }}
                </flux:badge>
                @if ($report->wasEscalated())
                    <flux:badge color="rose">{{ __('Eskaliert') }}</flux:badge>
                @endif
            </div>
            <flux:heading size="xl" class="mt-2">
                {{ $run->scenario?->name ?? $run->title }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $run->title }} ·
                {{ __('Gestartet') }}: {{ $run->started_at?->format('d.m.Y H:i') ?? '–' }}
                @if ($run->startedBy) · {{ $run->startedBy->name }} @endif
                · {{ $report->wasAborted() ? __('Abgebrochen') : __('Beendet') }}:
                {{ $report->endedAt()?->format('d.m.Y H:i') ?? '–' }}
            </flux:text>
        </div>
        <flux:button :href="route('drill-reports.pdf', $run)" target="_blank" variant="primary" icon="arrow-down-tray">
            {{ __('PDF-Bericht') }}
        </flux:button>
    </div>

    {{-- Kennzahlen --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Dauer') }}</div>
            <div class="mt-1 text-lg font-semibold">{{ DrillReport::formatDuration($report->durationSeconds()) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Erste Quittierung') }}</div>
            <div class="mt-1 text-lg font-semibold">{{ DrillReport::formatDuration($report->secondsToFirstAcknowledgement()) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Übernahme nach') }}</div>
            <div class="mt-1 text-lg font-semibold">{{ DrillReport::formatDuration($report->secondsToTakeover()) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Schritte erledigt') }}</div>
            <div class="mt-1 text-lg font-semibold">
                {{ $report->stepsDone() }} / {{ $report->stepsTotal() }}
                @if ($report->stepsOpen() > 0)
                    <span class="text-sm font-normal text-amber-600 dark:text-amber-400">({{ $report->stepsOpen() }} {{ __('offen') }})</span>
                @endif
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Eskalation') }}</div>
            <div class="mt-1 text-lg font-semibold">
                @if ($report->wasEscalated())
                    <span class="text-rose-600 dark:text-rose-400">{{ __('Ja') }}</span>
                    <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ $run->escalated_at?->format('d.m.Y H:i') }}</span>
                @else
                    {{ __('Nein') }}
                @endif
            </div>
        </div>
    </div>

    {{-- Hinweis-Box mit Lücken --}}
    @if (count($report->gaps()) > 0)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950">
            <flux:heading size="sm" class="text-amber-900 dark:text-amber-100">
                {{ __('Festgestellte Lücken') }}
            </flux:heading>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-200">
                @foreach ($report->gaps() as $gap)
                    <li>{{ __($gap) }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
            {{ __('Keine Lücken festgestellt — alle Schritte erledigt, Alarm quittiert und Verantwortung übernommen.') }}
        </div>
    @endif

    {{-- Schritt-Tabelle --}}
    <div class="mb-6">
        <flux:heading size="lg" class="mb-3">{{ __('Schritte') }}</flux:heading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">{{ __('Schritt') }}</th>
                        <th class="px-4 py-3">{{ __('Zuständig') }}</th>
                        <th class="px-4 py-3">{{ __('Erledigt von') }}</th>
                        <th class="px-4 py-3">{{ __('Erledigt am') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($run->steps as $step)
                        <tr wire:key="step-{{ $step->id }}">
                            <td class="px-4 py-3 text-zinc-500">{{ $step->sort }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $step->title }}</span>
                                @if ($step->note)
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Notiz') }}: {{ $step->note }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $step->responsible ?: '–' }}</td>
                            <td class="px-4 py-3">
                                @if ($step->checked_at)
                                    {{ $step->checkedBy?->name ?? '–' }}
                                @else
                                    <flux:badge color="amber" size="sm">{{ __('Offen') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $step->checked_at?->format('d.m.Y H:i') ?? '–' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('Keine Schritte in diesem Durchlauf.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quittierungs-Tabelle --}}
    <div class="mb-6">
        <flux:heading size="lg" class="mb-3">{{ __('Alarm-Quittierungen') }}</flux:heading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">{{ __('Person') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Zeitpunkt') }}</th>
                        <th class="px-4 py-3">{{ __('Nach Alarmstart') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($run->acknowledgements as $ack)
                        <tr wire:key="ack-{{ $ack->id }}">
                            <td class="px-4 py-3">{{ $ack->user?->name ?? '–' }}</td>
                            <td class="px-4 py-3">
                                @if ($ack->status === ScenarioRunAcknowledgement::STATUS_TAKING_OVER)
                                    <flux:badge color="emerald" size="sm">{{ __('Übernommen') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Gesehen') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $ack->acknowledged_at?->format('d.m.Y H:i:s') ?? '–' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($ack->acknowledged_at && $run->started_at)
                                    {{ DrillReport::formatDuration(max(0, (int) $run->started_at->diffInSeconds($ack->acknowledged_at))) }}
                                @else
                                    –
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('Keine Quittierungen — der Alarm wurde von niemandem bestätigt.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Beteiligte --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="sm">{{ __('Beteiligte') }} ({{ $report->participantCount() }})</flux:heading>
        <div class="mt-2 flex flex-wrap gap-2">
            @forelse ($report->participantNames() as $name)
                <flux:badge color="zinc" size="sm">{{ $name }}</flux:badge>
            @empty
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">–</flux:text>
            @endforelse
        </div>
    </div>

    <div class="text-sm text-zinc-500 dark:text-zinc-400">
        <flux:link :href="route('scenario-runs.show', $run)" wire:navigate>
            {{ __('Zum vollständigen Protokoll dieses Durchlaufs') }} →
        </flux:link>
    </div>
</section>
