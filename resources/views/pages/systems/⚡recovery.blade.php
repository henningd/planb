<?php

use App\Models\System;
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

<section class="w-full">
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
            <div class="space-y-4">
                @foreach ($plan['stages'] as $index => $stageSystems)
                    @php($stageRto = collect($stageSystems)->pluck('rto_minutes')->filter()->max())
                    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1">
                                <flux:heading size="base">{{ __('Stufe :n', ['n' => $index + 1]) }}</flux:heading>
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

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($stageSystems as $system)
                                <div class="flex items-start justify-between gap-4 px-5 py-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ $system->name }}</span>
                                            @if ($system->priority)
                                                <flux:badge
                                                    :color="match ($system->priority->sort) { 1 => 'rose', 2 => 'amber', default => 'zinc' }"
                                                    size="sm"
                                                >
                                                    {{ $system->priority->name }}
                                                </flux:badge>
                                            @endif
                                            <flux:badge color="zinc" size="sm">{{ $system->category->label() }}</flux:badge>
                                        </div>
                                        @if ($system->rto_minutes || $system->rpo_minutes || $system->downtime_cost_per_hour)
                                            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                @if ($system->rto_minutes)
                                                    <div class="flex items-center gap-1.5">
                                                        <flux:icon.clock class="h-3.5 w-3.5 text-zinc-400" />
                                                        <span>{{ __('Max. Ausfall') }}: <span class="font-medium">{{ Duration::format($system->rto_minutes) }}</span></span>
                                                    </div>
                                                @endif
                                                @if ($system->rpo_minutes)
                                                    <div class="flex items-center gap-1.5">
                                                        <flux:icon.circle-stack class="h-3.5 w-3.5 text-zinc-400" />
                                                        <span>{{ __('Max. Datenverlust') }}: <span class="font-medium">{{ Duration::format($system->rpo_minutes) }}</span></span>
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
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                <flux:icon.link class="h-3.5 w-3.5 text-zinc-400" />
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Braucht:') }}</span>
                                                @foreach ($system->dependencies as $dep)
                                                    <flux:badge color="sky" size="sm">{{ $dep->name }}</flux:badge>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($system->dependents->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                <flux:icon.arrow-right class="h-3.5 w-3.5 text-zinc-400" />
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Blockiert:') }}</span>
                                                @foreach ($system->dependents as $dep)
                                                    <flux:badge color="violet" size="sm">{{ $dep->name }}</flux:badge>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
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
                        <div class="divide-y divide-rose-200 dark:divide-rose-900">
                            @foreach ($plan['cycles'] as $system)
                                <div class="px-5 py-3 text-sm text-rose-900 dark:text-rose-100">
                                    {{ $system->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endunless
</section>
