<?php

use App\Enums\ComplianceCategory;
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
