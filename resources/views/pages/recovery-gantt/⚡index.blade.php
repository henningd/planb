<?php

use App\Models\Company;
use App\Support\Graph\RecoveryTimelineBuilder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recovery-Zeitplan')] class extends Component {
    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()?->currentCompany();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function timeline(): array
    {
        $company = $this->company;
        if (! $company) {
            return [
                'entries' => [],
                'cycles' => [],
                'total_minutes' => 0,
                'missing_rto_count' => 0,
                'stats' => ['systems' => 0, 'total_minutes' => 0, 'missing_rto' => 0, 'cycles' => 0],
            ];
        }

        return RecoveryTimelineBuilder::build($company);
    }
}; ?>

<section class="w-full">
    <div class="mb-4">
        <flux:heading size="xl">{{ __('Recovery-Zeitplan') }}</flux:heading>
        <flux:subheading>
            {{ __('Wenn nach einem Vorfall alle Systeme gleichzeitig wieder anlaufen, zeigt diese Zeitleiste, wann welches System nach Berücksichtigung seiner Abhängigkeiten verfügbar ist. Die Recovery Time Objective (RTO) ist die maximal tolerierte Wiederanlaufzeit pro System; fehlt sie, wird mit 60 Minuten gerechnet.') }}
        </flux:subheading>
    </div>

    @php
        $timeline = $this->timeline;
        $entries = $timeline['entries'];
        $cycles = $timeline['cycles'];
        $stats = $timeline['stats'];
        $totalMinutes = (int) $timeline['total_minutes'];
        $hasEntries = count($entries) > 0;
    @endphp

    @if (! $this->company)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @elseif (! $hasEntries && count($cycles) === 0)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <div>{{ __('Keine Systeme erfasst. Legen Sie zuerst Systeme an, dann kann hier der Recovery-Zeitplan visualisiert werden.') }}</div>
            <div class="mt-3">
                <flux:button size="sm" variant="primary" :href="route('systems.index')" icon="plus" wire:navigate>
                    {{ __('Systeme öffnen') }}
                </flux:button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Systeme') }}</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 tabular-nums dark:text-zinc-50">{{ $stats['systems'] }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Gesamt-RTO') }}</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 tabular-nums dark:text-zinc-50">{{ \App\Support\Graph\RecoveryTimelineBuilder::formatMinutes($totalMinutes) }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Ohne RTO (60 min angenommen)') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums {{ $stats['missing_rto'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-50' }}">{{ $stats['missing_rto'] }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Im Zyklus') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums {{ $stats['cycles'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-900 dark:text-zinc-50' }}">{{ $stats['cycles'] }}</div>
            </div>
        </div>

        @if ($hasEntries)
            @php
                $tickStep = 60;
                if ($totalMinutes <= 0) {
                    $tickStep = 60;
                } elseif ($totalMinutes <= 60) {
                    $tickStep = 15;
                } elseif ($totalMinutes <= 180) {
                    $tickStep = 30;
                } elseif ($totalMinutes <= 600) {
                    $tickStep = 60;
                } elseif ($totalMinutes <= 1440) {
                    $tickStep = 120;
                } else {
                    $tickStep = (int) (ceil($totalMinutes / 10 / 60) * 60);
                }
                $scaleMax = max($totalMinutes, $tickStep);
                $ticks = [];
                for ($t = 0; $t <= $scaleMax; $t += $tickStep) {
                    $ticks[] = $t;
                }
                if (end($ticks) !== $scaleMax) {
                    $ticks[] = $scaleMax;
                }
            @endphp

            <div class="mt-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-100 p-3 dark:border-zinc-800">
                    <flux:heading size="base">{{ __('Wiederanlauf-Zeitleiste') }}</flux:heading>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Gesamt-Wiederanlaufzeit') }}: <strong class="text-zinc-700 dark:text-zinc-200">{{ \App\Support\Graph\RecoveryTimelineBuilder::formatMinutes($totalMinutes) }}</strong>
                    </p>
                </div>

                <div class="p-3">
                    {{-- Time scale --}}
                    <div class="grid grid-cols-[minmax(8rem,30%)_1fr] gap-3 pb-2">
                        <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('System') }}</div>
                        <div class="relative h-6 border-b border-zinc-200 dark:border-zinc-700">
                            @foreach ($ticks as $tick)
                                @php
                                    $left = $scaleMax > 0 ? ($tick / $scaleMax) * 100 : 0;
                                @endphp
                                <div class="absolute top-0 flex h-full -translate-x-1/2 flex-col items-center" style="left: {{ $left }}%">
                                    <div class="h-2 w-px bg-zinc-300 dark:bg-zinc-600"></div>
                                    <div class="mt-0.5 text-[10px] text-zinc-500 tabular-nums dark:text-zinc-400">{{ \App\Support\Graph\RecoveryTimelineBuilder::formatMinutes($tick) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Bars --}}
                    <div class="space-y-1.5">
                        @foreach ($entries as $entry)
                            @php
                                $system = $entry['system'];
                                $start = (int) $entry['start'];
                                $end = (int) $entry['end'];
                                $duration = (int) $entry['duration'];
                                $level = $system->emergencyLevel;
                                $marginLeft = $scaleMax > 0 ? ($start / $scaleMax) * 100 : 0;
                                $width = $scaleMax > 0 ? ($duration / $scaleMax) * 100 : 0;
                                $tooltip = __('Start').': '.\App\Support\Graph\RecoveryTimelineBuilder::formatMinutes($start)
                                    .' | '.__('Ende').': '.\App\Support\Graph\RecoveryTimelineBuilder::formatMinutes($end)
                                    .' | '.__('RTO').': '.\App\Support\Graph\RecoveryTimelineBuilder::formatMinutes($duration);
                                if ($entry['rto_missing']) {
                                    $tooltip .= ' ('.__('Default').')';
                                }
                            @endphp

                            <div class="grid grid-cols-[minmax(8rem,30%)_1fr] items-center gap-3">
                                <div class="flex min-w-0 items-center gap-1.5">
                                    @if ($level)
                                        <span class="inline-block h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $entry['level_color'] }}" title="{{ $level->name }}"></span>
                                    @endif
                                    <a
                                        href="{{ route('systems.show', ['current_team' => $this->company->team?->slug, 'system' => $system->id]) }}"
                                        wire:navigate
                                        class="truncate text-sm font-medium text-zinc-900 hover:text-zinc-700 hover:underline dark:text-zinc-100 dark:hover:text-zinc-300"
                                        title="{{ $system->name }}"
                                    >{{ $system->name }}</a>
                                </div>
                                <div class="relative h-7 rounded bg-zinc-50 dark:bg-zinc-800/60">
                                    <div
                                        class="absolute top-0 flex h-full min-w-[2px] items-center overflow-hidden rounded px-1.5 text-[10px] font-medium text-white shadow-sm"
                                        style="margin-left: {{ $marginLeft }}%; width: {{ max($width, 0.5) }}%; background-color: {{ $entry['level_color'] }};"
                                        title="{{ $tooltip }}"
                                    >
                                        <span class="truncate tabular-nums">{{ \App\Support\Graph\RecoveryTimelineBuilder::formatMinutes($duration) }}@if ($entry['rto_missing']) *@endif</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Legend --}}
                    <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full" style="background-color: #f43f5e"></span>
                            {{ __('Stufe 1 (kritisch)') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full" style="background-color: #f59e0b"></span>
                            {{ __('Stufe 2') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full" style="background-color: #0ea5e9"></span>
                            {{ __('Stufe 3') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full" style="background-color: #10b981"></span>
                            {{ __('Stufe 4') }}
                        </span>
                        @if ($stats['missing_rto'] > 0)
                            <span class="ml-auto text-amber-600 dark:text-amber-400">* {{ __('RTO fehlt – 60 min angenommen') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if (count($cycles) > 0)
            <div class="mt-4 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-700 dark:bg-rose-950 dark:text-rose-100">
                <div class="font-semibold">{{ __('Zyklische Abhängigkeiten erkannt') }}</div>
                <p class="mt-1">
                    {{ __('Die folgenden Systeme bilden einen Kreis und können nicht in eine Reihenfolge gebracht werden. Bitte lösen Sie den Zyklus auf, damit diese Systeme im Zeitplan erscheinen:') }}
                </p>
                <ul class="mt-2 list-inside list-disc space-y-0.5">
                    @foreach ($cycles as $cycle)
                        <li>{{ $cycle['name'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</section>
