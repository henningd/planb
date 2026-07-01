<?php

use App\Models\System;
use App\Support\Accessibility\SeverityIndicator;
use App\Support\Duration;
use App\Support\RecoveryOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Wiederanlauf')] class extends Component {
    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return array{stages: array<int, array<int, System>>, cycles: array<int, System>}
     */
    #[Computed]
    public function plan(): array
    {
        $systems = System::with(['priority', 'dependencies', 'dependents'])
            ->orderBy('name')
            ->get();

        return RecoveryOrder::compute($systems);
    }
}; ?>

<section class="w-full" x-data="{ expandAll: false }">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Wiederanlauf-Reihenfolge') }}</flux:heading>
        <flux:subheading>
            {{ __('In welcher Reihenfolge werden Ihre Systeme nach einem Ausfall wieder angefahren? Schritte mit gleicher Stufe können parallel bearbeitet werden.') }}
        </flux:subheading>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @else
        @php($plan = $this->plan)

        @if (empty($plan['stages']) && empty($plan['cycles']))
            <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Systeme erfasst.') }}
                </flux:text>
                <div class="mt-4">
                    <flux:button size="sm" variant="primary" :href="route('systems.index')" icon="plus" wire:navigate>
                        {{ __('Systeme anlegen') }}
                    </flux:button>
                </div>
            </div>
        @else
            @php($systemCount = collect($plan['stages'])->flatten()->count())
            @php($blockedCount = count($plan['cycles']))

            {{-- Übersichtsleiste --}}
            <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm">
                    <span class="flex items-center gap-1.5">
                        <flux:icon.server class="h-4 w-4 text-zinc-400" />
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $systemCount }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ trans_choice('System|Systeme', $systemCount) }}</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <flux:icon.bars-arrow-down class="h-4 w-4 text-zinc-400" />
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ count($plan['stages']) }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ trans_choice('Stufe|Stufen', count($plan['stages'])) }}</span>
                    </span>
                    @if ($blockedCount > 0)
                        <span class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
                            <flux:icon.exclamation-triangle class="h-4 w-4" />
                            <span class="font-semibold">{{ $blockedCount }}</span>
                            <span>{{ __('blockiert') }}</span>
                        </span>
                    @endif
                </div>

                <div class="ms-auto flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" @click="expandAll = !expandAll">
                        <span x-show="! expandAll">{{ __('Details anzeigen') }}</span>
                        <span x-show="expandAll" x-cloak>{{ __('Details ausblenden') }}</span>
                    </flux:button>
                    <flux:button size="sm" variant="ghost" icon="chart-bar" :href="route('recovery-gantt.index')" wire:navigate>
                        {{ __('Zeitplan') }}
                    </flux:button>
                </div>
            </div>

            <div class="space-y-4">
                @foreach ($plan['stages'] as $index => $stageSystems)
                    @php($stageRto = collect($stageSystems)->pluck('rto_minutes')->filter()->max())
                    @php($stageCount = count($stageSystems))
                    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center gap-3 border-b border-zinc-100 bg-zinc-50/60 px-5 py-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="base">{{ __('Stufe :n', ['n' => $index + 1]) }}</flux:heading>
                                    <flux:badge color="zinc" size="sm">{{ trans_choice(':count System|:count Systeme', $stageCount, ['count' => $stageCount]) }}</flux:badge>
                                </div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($index === 0)
                                        {{ __('Systeme ohne Abhängigkeiten – können als erstes angefahren werden.') }}
                                    @else
                                        {{ __('Erst starten, wenn alle Systeme aus Stufe :prev laufen.', ['prev' => $index]) }}
                                    @endif
                                </flux:text>
                            </div>
                            @if ($stageRto)
                                <flux:badge color="emerald" size="sm" icon="clock">
                                    {{ __('Stufe spätestens nach :d verfügbar', ['d' => Duration::format($stageRto)]) }}
                                </flux:badge>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($stageSystems as $system)
                                @php($depCount = $system->dependencies->count())
                                @php($dependentCount = $system->dependents->count())
                                @php($priorityColor = $system->priority ? match ($system->priority->sort) { 1 => 'rose', 2 => 'amber', default => 'zinc' } : 'zinc')
                                @php($priorityDotClass = match ($priorityColor) { 'rose' => 'bg-rose-500', 'amber' => 'bg-amber-500', default => 'bg-zinc-400' })
                                <div
                                    x-data="{ open: false }"
                                    class="flex flex-col rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900/40"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                @if ($system->priority)
                                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $priorityDotClass }}" aria-hidden="true"></span>
                                                @endif
                                                <span class="truncate font-medium">{{ $system->name }}</span>
                                            </div>
                                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $system->category->label() }}</div>
                                        </div>
                                        @if ($system->priority)
                                            @php($priorityIcon = SeverityIndicator::systemPriorityIcon((int) $system->priority->sort))
                                            <span data-severity-icon="{{ $priorityIcon }}" class="shrink-0">
                                                <flux:badge :color="$priorityColor" size="sm" :icon="$priorityIcon">
                                                    {{ $system->priority->name }}
                                                </flux:badge>
                                            </span>
                                        @endif
                                    </div>

                                    @php($hasMetrics = $system->rto_minutes || $system->rpo_minutes || $system->downtime_cost_per_hour)
                                    @if ($hasMetrics || $depCount || $dependentCount)
                                        <div class="mt-2.5 border-t border-zinc-100 pt-2.5 dark:border-zinc-800">
                                            <button
                                                type="button"
                                                @click="open = ! open"
                                                class="flex w-full items-center gap-1.5 text-xs text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                                            >
                                                <flux:icon.chevron-right
                                                    class="h-3.5 w-3.5 shrink-0 transition-transform"
                                                    ::class="(open || expandAll) && 'rotate-90'"
                                                />
                                                <span class="flex flex-wrap items-center gap-x-1.5">
                                                    @if ($hasMetrics)
                                                        <span>{{ __('Kennzahlen') }}</span>
                                                    @endif
                                                    @if ($hasMetrics && ($depCount || $dependentCount))
                                                        <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                                    @endif
                                                    @if ($depCount)
                                                        <span>{{ __('braucht') }} <span class="font-medium text-sky-600 dark:text-sky-400">{{ $depCount }}</span></span>
                                                    @endif
                                                    @if ($depCount && $dependentCount)
                                                        <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                                    @endif
                                                    @if ($dependentCount)
                                                        <span>{{ __('blockiert') }} <span class="font-medium text-violet-600 dark:text-violet-400">{{ $dependentCount }}</span></span>
                                                    @endif
                                                </span>
                                            </button>

                                            <div x-show="open || expandAll" x-collapse x-cloak class="space-y-2.5 pt-2.5">
                                                @if ($hasMetrics)
                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                        @if ($system->rto_minutes)
                                                            <div class="flex items-center gap-1.5">
                                                                <flux:icon.clock class="h-3.5 w-3.5 text-zinc-400" />
                                                                <span>{{ __('Max. Ausfall') }}: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ Duration::format($system->rto_minutes) }}</span></span>
                                                            </div>
                                                        @endif
                                                        @if ($system->rpo_minutes)
                                                            <div class="flex items-center gap-1.5">
                                                                <flux:icon.circle-stack class="h-3.5 w-3.5 text-zinc-400" />
                                                                <span>{{ __('Max. Datenverlust') }}: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ Duration::format($system->rpo_minutes) }}</span></span>
                                                            </div>
                                                        @endif
                                                        @if ($system->downtime_cost_per_hour)
                                                            <div class="flex items-center gap-1.5">
                                                                <flux:icon.banknotes class="h-3.5 w-3.5 text-zinc-400" />
                                                                <span>{{ number_format($system->downtime_cost_per_hour, 0, ',', '.') }} € / h</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if ($system->dependencies->isNotEmpty())
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <flux:icon.link class="h-3.5 w-3.5 text-zinc-400" />
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Braucht:') }}</span>
                                                        @foreach ($system->dependencies as $dep)
                                                            <flux:badge color="sky" size="sm">{{ $dep->name }}</flux:badge>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if ($system->dependents->isNotEmpty())
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <flux:icon.arrow-right class="h-3.5 w-3.5 text-zinc-400" />
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Blockiert:') }}</span>
                                                        @foreach ($system->dependents as $dep)
                                                            <flux:badge color="violet" size="sm">{{ $dep->name }}</flux:badge>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if (! empty($plan['cycles']))
                    <div class="overflow-hidden rounded-xl border border-rose-300 bg-rose-50 dark:border-rose-800 dark:bg-rose-950">
                        <div class="flex items-center gap-3 border-b border-rose-200 px-5 py-4 dark:border-rose-900">
                            <flux:icon.exclamation-triangle class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                            <div>
                                <flux:heading size="base" class="text-rose-900 dark:text-rose-100">
                                    {{ __('Zirkuläre Abhängigkeit') }}
                                </flux:heading>
                                <flux:text class="text-sm text-rose-700 dark:text-rose-300">
                                    {{ __('Diese Systeme hängen gegenseitig voneinander ab. Bitte Abhängigkeit auflösen.') }}
                                </flux:text>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 p-4">
                            @foreach ($plan['cycles'] as $system)
                                <flux:badge color="rose" size="sm" icon="arrow-path">{{ $system->name }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endunless
</section>
