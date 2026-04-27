<?php

use App\Enums\ComplianceCategory;
use App\Models\ComplianceScoreSnapshot;
use App\Models\Company;
use App\Support\Compliance\Evaluator;
use App\Support\Compliance\Report;
use App\Support\Compliance\Status;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Compliance')] class extends Component {
    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()?->currentCompany();
    }

    #[Computed]
    public function report(): ?Report
    {
        $company = $this->company;

        return $company ? Evaluator::for($company) : null;
    }

    /**
     * Snapshots der letzten 30 Tage für die aktuelle Company,
     * aufsteigend nach Datum.
     *
     * @return \Illuminate\Support\Collection<int, ComplianceScoreSnapshot>
     */
    #[Computed]
    public function snapshots(): \Illuminate\Support\Collection
    {
        $company = $this->company;
        if (! $company) {
            return collect();
        }

        return ComplianceScoreSnapshot::query()
            ->where('company_id', $company->id)
            ->where('snapshot_date', '>=', today()->subDays(30)->toDateString())
            ->orderBy('snapshot_date')
            ->get();
    }

    /**
     * Mini-Stats für „Heute" / „vor 7 Tagen" / „vor 30 Tagen".
     *
     * @return array{today: int, week: ?int, month: ?int, week_delta: ?int, month_delta: ?int}
     */
    public function trendStats(): array
    {
        $snapshots = $this->snapshots;
        if ($snapshots->isEmpty()) {
            return ['today' => 0, 'week' => null, 'month' => null, 'week_delta' => null, 'month_delta' => null];
        }

        $today = (int) $snapshots->last()->score;

        $week = $this->scoreNearDaysAgo($snapshots, 7);
        $month = $this->scoreNearDaysAgo($snapshots, 30);

        return [
            'today' => $today,
            'week' => $week,
            'month' => $month,
            'week_delta' => $week !== null ? $today - $week : null,
            'month_delta' => $month !== null ? $today - $month : null,
        ];
    }

    /**
     * Sucht den Snapshot, dessen Datum am nächsten an „vor X Tagen" liegt.
     */
    private function scoreNearDaysAgo(\Illuminate\Support\Collection $snapshots, int $daysAgo): ?int
    {
        $target = today()->subDays($daysAgo);
        $best = null;
        $bestDiff = PHP_INT_MAX;

        foreach ($snapshots as $snap) {
            $diff = abs($snap->snapshot_date->diffInDays($target));
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $best = $snap;
            }
        }

        if ($best === null) {
            return null;
        }

        // Wenn das nächste Datum mehr als 3 Tage entfernt ist, lieber kein
        // Vergleichswert (sonst wirkt der Trend irreführend).
        if ($bestDiff > 3) {
            return null;
        }

        return (int) $best->score;
    }

    /**
     * SVG-Trend-Pfad als d-Attribut, vorberechnet auf 0..100 viewBox-X / 0..100 viewBox-Y.
     *
     * @return array{points: list<array{x: float, y: float, score: int, date: string}>, path: string}
     */
    public function trendPath(): array
    {
        $snapshots = $this->snapshots;
        $count = $snapshots->count();
        if ($count < 2) {
            return ['points' => [], 'path' => ''];
        }

        $points = [];
        $segments = [];
        $i = 0;
        foreach ($snapshots as $snap) {
            $x = $count === 1 ? 0.0 : ($i / ($count - 1)) * 100.0;
            // Y invertieren: score 100 → top (0), score 0 → bottom (100)
            $y = 100.0 - (float) $snap->score;
            $points[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'score' => (int) $snap->score,
                'date' => $snap->snapshot_date->isoFormat('DD.MM.YYYY'),
            ];
            $segments[] = ($i === 0 ? 'M' : 'L').round($x, 2).' '.round($y, 2);
            $i++;
        }

        return ['points' => $points, 'path' => implode(' ', $segments)];
    }

    /**
     * Hex-Farbe je nach aktuellem Score (gleiche Skala wie Reifegrad-Karte).
     */
    public function trendColorHex(): string
    {
        $score = $this->snapshots->isNotEmpty() ? (int) $this->snapshots->last()->score : 0;

        return match (true) {
            $score >= 90 => '#10b981',
            $score >= 75 => '#84cc16',
            $score >= 50 => '#f59e0b',
            $score >= 25 => '#f97316',
            default => '#f43f5e',
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Compliance') }}</flux:heading>
        <flux:subheading>
            {{ __('Reifegrad nach BSI 200-4 / NIS2 – berechnet aus Ihren Stammdaten. Konkrete Aktionen mit Direktlink zur Korrektur.') }}
        </flux:subheading>
    </div>

    @php
        $report = $this->report;
    @endphp

    @if (! $report)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @else
        @php
            $score = $report->score();
            $color = $report->readinessColor();
            $hex = $report->readinessHex();
            $counts = $report->counts();
        @endphp

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 md:col-span-1">
                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Reifegrad') }}</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-5xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $score }}</span>
                    <span class="text-lg text-zinc-500 dark:text-zinc-400">/ 100</span>
                </div>
                <flux:badge :color="$color" size="sm" class="mt-3">{{ $report->readinessLabel() }}</flux:badge>
                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full" style="width: {{ $score }}%; background-color: {{ $hex }}"></div>
                </div>
                <flux:text class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Stand: :time', ['time' => $report->generatedAt->isoFormat('DD.MM.YYYY HH:mm')]) }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 md:col-span-2">
                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Bereiche') }}</div>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach (ComplianceCategory::ordered() as $cat)
                        @php $catReport = $report->category($cat); @endphp
                        @if ($catReport && $catReport->totalCounted() > 0)
                            <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <flux:icon :name="$cat->icon()" variant="mini" style="color: {{ $cat->hex() }}" />
                                        <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-50">{{ $cat->label() }}</span>
                                    </div>
                                    <span class="tabular-nums text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $catReport->score() }}</span>
                                </div>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full rounded-full" style="width: {{ $catReport->score() }}%; background-color: {{ $cat->hex() }}"></div>
                                </div>
                                <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $catReport->passCount() }}/{{ $catReport->totalCounted() }} {{ __('Checks erfüllt') }}
                                </flux:text>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        @php
            $snapshots = $this->snapshots;
            $stats = $this->trendStats();
            $trend = $this->trendPath();
            $trendColor = $this->trendColorHex();
        @endphp

        <div class="mt-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:heading size="lg">{{ __('Verlauf der letzten 30 Tage') }}</flux:heading>
                    <flux:subheading>{{ __('Tägliche Reifegrad-Snapshots zeigen, wie sich Ihre Compliance entwickelt.') }}</flux:subheading>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Datenpunkte') }}</div>
                    <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $snapshots->count() }}</div>
                </div>
            </div>

            @if ($snapshots->count() < 2)
                <div class="mt-4 rounded-lg border border-zinc-100 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
                    {{ __('Noch zu wenig Daten für einen Trend (mind. 2 Tage). Snapshots werden täglich um 03:00 erstellt.') }}
                </div>
            @else
                <div class="mt-4">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-20 w-full" aria-hidden="true">
                        {{-- Y-Achsen-Marker bei 100 / 50 / 0 --}}
                        <line x1="0" y1="0" x2="100" y2="0" stroke="currentColor" stroke-width="0.2" stroke-dasharray="1 1" class="text-zinc-300 dark:text-zinc-700" />
                        <line x1="0" y1="50" x2="100" y2="50" stroke="currentColor" stroke-width="0.2" stroke-dasharray="1 1" class="text-zinc-300 dark:text-zinc-700" />
                        <line x1="0" y1="100" x2="100" y2="100" stroke="currentColor" stroke-width="0.2" stroke-dasharray="1 1" class="text-zinc-300 dark:text-zinc-700" />

                        {{-- Trend-Linie --}}
                        <path d="{{ $trend['path'] }}" fill="none" stroke="{{ $trendColor }}" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />

                        {{-- Datenpunkte --}}
                        @foreach ($trend['points'] as $point)
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="0.9" fill="{{ $trendColor }}" vector-effect="non-scaling-stroke">
                                <title>{{ $point['date'] }}: {{ $point['score'] }}/100</title>
                            </circle>
                        @endforeach
                    </svg>
                    <div class="mt-1 flex justify-between text-xs text-zinc-400 dark:text-zinc-500">
                        <span>{{ $snapshots->first()->snapshot_date->isoFormat('DD.MM.') }}</span>
                        <span>{{ $snapshots->last()->snapshot_date->isoFormat('DD.MM.') }}</span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Heute') }}</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $stats['today'] }}</div>
                    </div>

                    <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Vor 7 Tagen') }}</div>
                        @if ($stats['week'] !== null)
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $stats['week'] }}</span>
                                @if ($stats['week_delta'] !== null && $stats['week_delta'] !== 0)
                                    @php
                                        $deltaSign = $stats['week_delta'] > 0 ? '+' : '';
                                        $deltaColor = $stats['week_delta'] > 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-rose-600 dark:text-rose-400';
                                    @endphp
                                    <span class="text-sm font-medium tabular-nums {{ $deltaColor }}">{{ $deltaSign }}{{ $stats['week_delta'] }}</span>
                                @elseif ($stats['week_delta'] === 0)
                                    <span class="text-sm font-medium tabular-nums text-zinc-500 dark:text-zinc-400">±0</span>
                                @endif
                            </div>
                        @else
                            <div class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">{{ __('keine Daten') }}</div>
                        @endif
                    </div>

                    <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Vor 30 Tagen') }}</div>
                        @if ($stats['month'] !== null)
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $stats['month'] }}</span>
                                @if ($stats['month_delta'] !== null && $stats['month_delta'] !== 0)
                                    @php
                                        $deltaSign = $stats['month_delta'] > 0 ? '+' : '';
                                        $deltaColor = $stats['month_delta'] > 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-rose-600 dark:text-rose-400';
                                    @endphp
                                    <span class="text-sm font-medium tabular-nums {{ $deltaColor }}">{{ $deltaSign }}{{ $stats['month_delta'] }}</span>
                                @elseif ($stats['month_delta'] === 0)
                                    <span class="text-sm font-medium tabular-nums text-zinc-500 dark:text-zinc-400">±0</span>
                                @endif
                            </div>
                        @else
                            <div class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">{{ __('keine Daten') }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @php $actions = $report->topActions(5); @endphp
        @if (count($actions) > 0)
            <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Top-Aktionen für mehr Reifegrad') }}</flux:heading>
                <flux:subheading>{{ __('Diese Punkte bringen aktuell den größten Score-Gewinn.') }}</flux:subheading>
                <ol class="mt-4 space-y-3">
                    @foreach ($actions as $i => $action)
                        <li class="flex items-start justify-between gap-4 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:badge :color="$action['result']->status->color()" size="sm">{{ $action['result']->status->label() }}</flux:badge>
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $action['check']->label }}</span>
                                </div>
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $action['result']->message }}</flux:text>
                            </div>
                            @if ($action['result']->action)
                                <flux:button
                                    size="sm"
                                    variant="primary"
                                    :href="route($action['result']->action['route'], $action['result']->action['params'] ?? [])"
                                    wire:navigate
                                >
                                    {{ $action['result']->action['label'] }}
                                </flux:button>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        <div class="mt-6 space-y-6">
            @foreach (ComplianceCategory::ordered() as $cat)
                @php $catReport = $report->category($cat); @endphp
                @if (! $catReport || count($catReport->items) === 0)
                    @continue
                @endif

                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <flux:icon :name="$cat->icon()" variant="mini" style="color: {{ $cat->hex() }}" />
                                <flux:heading size="lg">{{ $cat->label() }}</flux:heading>
                            </div>
                            <flux:subheading>{{ $cat->description() }}</flux:subheading>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $catReport->score() }}</div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">/ 100</flux:text>
                        </div>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($catReport->items as $entry)
                            @php($r = $entry['result'])
                            @php($c = $entry['check'])
                            <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:icon :name="$r->status->icon()" variant="mini" style="color: {{ $r->status->hex() }}" />
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $c->label }}</span>
                                        <flux:badge :color="$r->status->color()" size="sm">{{ $r->status->label() }}</flux:badge>
                                        @if ($r->isCounted())
                                            <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $r->score }}/100</span>
                                        @endif
                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Gewicht') }} {{ $c->weight }}</span>
                                    </div>
                                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $r->message }}</flux:text>
                                    @if (! empty($r->details))
                                        <ul class="mt-2 space-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            @foreach ($r->details as $detail)
                                                <li class="flex gap-1.5">
                                                    <span class="text-zinc-400">•</span>
                                                    <span>{{ $detail }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <flux:text class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $c->description }}</flux:text>
                                </div>
                                @if ($r->action && $r->status !== Status::Pass && $r->status !== Status::NotApplicable)
                                    <div class="shrink-0">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :href="route($r->action['route'], $r->action['params'] ?? [])"
                                            wire:navigate
                                        >
                                            {{ $r->action['label'] }}
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
