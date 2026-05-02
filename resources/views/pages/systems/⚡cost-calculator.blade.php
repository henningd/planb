<?php

use App\Enums\SystemCategory;
use App\Models\Company;
use App\Models\System;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ausfallrechner')] class extends Component {
    /** @var array<int, string> */
    public array $selectedSystemIds = [];

    public float $durationHours = 4.0;

    public bool $onlyWithCosts = true;

    public function selectAll(): void
    {
        $this->selectedSystemIds = $this->visibleSystems->pluck('id')->all();
    }

    public function selectAllWithCosts(): void
    {
        $this->selectedSystemIds = System::query()
            ->where('downtime_cost_per_hour', '>', 0)
            ->pluck('id')
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedSystemIds = [];
    }

    public function setDuration(float $hours): void
    {
        $this->durationHours = max(0.0, $hours);
    }

    public function toggleOnlyWithCosts(): void
    {
        $this->onlyWithCosts = ! $this->onlyWithCosts;
    }

    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()?->currentCompany();
    }

    /**
     * Systeme, die im Auswahl-Block angezeigt werden (nach Filter-Toggle).
     *
     * @return Collection<int, System>
     */
    #[Computed]
    public function visibleSystems(): Collection
    {
        $query = System::query()
            ->select(['id', 'name', 'category', 'downtime_cost_per_hour']);

        if ($this->onlyWithCosts) {
            $query->where('downtime_cost_per_hour', '>', 0);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Sichtbare Systeme nach Kategorie gruppiert (für die Auswahl-UI).
     *
     * @return array<string, Collection<int, System>>
     */
    #[Computed]
    public function systemsByCategory(): array
    {
        $systems = $this->visibleSystems;
        $grouped = [];

        foreach (SystemCategory::cases() as $category) {
            $bucket = $systems->where('category', $category)->values();
            if ($bucket->isNotEmpty()) {
                $grouped[$category->value] = $bucket;
            }
        }

        return $grouped;
    }

    /**
     * Gesamtzahl Systeme im Mandanten (egal ob mit Kostenwert).
     */
    #[Computed]
    public function totalSystemsCount(): int
    {
        return System::query()->count();
    }

    /**
     * @return array{
     *     systems: list<array{id:string, name:string, hourly:int, partial:int}>,
     *     hourly_total: int,
     *     duration: float,
     *     total: int,
     *     selected_count: int,
     *     missing_cost_count: int,
     * }
     */
    #[Computed]
    public function summary(): array
    {
        $duration = max(0.0, (float) $this->durationHours);

        if (empty($this->selectedSystemIds)) {
            return [
                'systems' => [],
                'hourly_total' => 0,
                'duration' => $duration,
                'total' => 0,
                'selected_count' => 0,
                'missing_cost_count' => 0,
            ];
        }

        $selected = System::query()
            ->whereIn('id', $this->selectedSystemIds)
            ->orderByDesc('downtime_cost_per_hour')
            ->orderBy('name')
            ->get(['id', 'name', 'downtime_cost_per_hour']);

        $hourlyTotal = 0;
        $missing = 0;
        $rows = [];

        foreach ($selected as $system) {
            $hourly = (int) ($system->downtime_cost_per_hour ?? 0);
            if ($hourly <= 0) {
                $missing++;
            }
            $hourlyTotal += $hourly;

            $rows[] = [
                'id' => (string) $system->id,
                'name' => (string) $system->name,
                'hourly' => $hourly,
                'partial' => (int) round($hourly * $duration),
            ];
        }

        return [
            'systems' => $rows,
            'hourly_total' => $hourlyTotal,
            'duration' => $duration,
            'total' => (int) round($hourlyTotal * $duration),
            'selected_count' => count($rows),
            'missing_cost_count' => $missing,
        ];
    }
}; ?>

@php
    $fmt = fn (int $n) => number_format($n, 0, ',', '.');
@endphp

<section class="w-full">
    <div class="mb-4">
        <flux:heading size="xl">{{ __('Ausfallrechner') }}</flux:heading>
        <flux:subheading>
            {{ __('Schätzt den finanziellen Schaden, falls ein oder mehrere Systeme für eine bestimmte Dauer ausfallen. Grundlage ist der pro System hinterlegte Wert „Ausfallkosten pro Stunde".') }}
        </flux:subheading>
    </div>

    @if (! $this->company)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @elseif ($this->totalSystemsCount === 0)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <div>{{ __('Noch keine Systeme erfasst — ohne Systeme kann hier nichts berechnet werden.') }}</div>
            <div class="mt-3">
                <flux:button size="sm" variant="primary" :href="route('systems.index')" icon="plus" wire:navigate>
                    {{ __('Systeme öffnen') }}
                </flux:button>
            </div>
        </div>
    @else
        @php
            $summary = $this->summary;
            $hourlyTotal = (int) $summary['hourly_total'];
            $totalCost = (int) $summary['total'];
            $selectedCount = (int) $summary['selected_count'];
            $missingCount = (int) $summary['missing_cost_count'];
            $duration = (float) $summary['duration'];
        @endphp

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 p-4 dark:border-zinc-800">
                        <div>
                            <flux:heading size="base">{{ __('1. Welche Systeme fallen aus?') }}</flux:heading>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Mehrfachauswahl. Die Stundenkosten je System stehen in Klammern.') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button size="xs" variant="ghost" wire:click="selectAllWithCosts" icon="check">
                                {{ __('Alle mit Kosten') }}
                            </flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="selectAll" icon="check-circle">
                                {{ __('Alle sichtbaren') }}
                            </flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="clearSelection" icon="x-mark">
                                {{ __('Auswahl löschen') }}
                            </flux:button>
                        </div>
                    </div>

                    <div class="border-b border-zinc-100 p-3 dark:border-zinc-800">
                        <flux:checkbox
                            wire:model.live="onlyWithCosts"
                            :label="__('Nur Systeme mit hinterlegten Stundenkosten anzeigen')"
                        />
                    </div>

                    <div class="p-4">
                        @if (count($this->systemsByCategory) === 0)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Kein System trifft auf den aktuellen Filter zu.') }}
                            </p>
                        @else
                            <div class="space-y-5">
                                @foreach ($this->systemsByCategory as $categoryValue => $bucket)
                                    @php $category = SystemCategory::from($categoryValue); @endphp
                                    <div>
                                        <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                            {{ $category->label() }}
                                            <span class="text-zinc-400 dark:text-zinc-500">({{ $bucket->count() }})</span>
                                        </div>
                                        <div class="grid grid-cols-1 gap-1 md:grid-cols-2" wire:key="cat-{{ $categoryValue }}">
                                            @foreach ($bucket as $system)
                                                @php $hourly = (int) ($system->downtime_cost_per_hour ?? 0); @endphp
                                                <label
                                                    class="flex items-center justify-between gap-3 rounded-md border border-zinc-100 p-2 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800"
                                                    wire:key="sys-{{ $system->id }}"
                                                >
                                                    <flux:checkbox
                                                        wire:model.live="selectedSystemIds"
                                                        value="{{ $system->id }}"
                                                        :label="$system->name"
                                                    />
                                                    <span class="shrink-0 text-xs tabular-nums {{ $hourly > 0 ? 'text-zinc-600 dark:text-zinc-300' : 'text-zinc-400 dark:text-zinc-500' }}">
                                                        @if ($hourly > 0)
                                                            {{ $fmt($hourly) }} €/h
                                                        @else
                                                            {{ __('keine Kosten') }}
                                                        @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 p-4 dark:border-zinc-800">
                        <flux:heading size="base">{{ __('2. Wie lange dauert der Ausfall?') }}</flux:heading>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Angabe in Stunden. Wert kann auch in Bruchteilen erfasst werden (z. B. 0,5 = 30 min).') }}
                        </p>
                    </div>
                    <div class="space-y-3 p-4">
                        <flux:field>
                            <flux:label>{{ __('Ausfalldauer (Stunden)') }}</flux:label>
                            <flux:input
                                wire:model.live.debounce.250ms="durationHours"
                                type="number"
                                min="0"
                                step="0.25"
                            />
                        </flux:field>

                        <div class="flex flex-wrap gap-2">
                            <flux:button size="xs" variant="ghost" wire:click="setDuration(1)">{{ __('1h') }}</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="setDuration(4)">{{ __('4h (halber Tag)') }}</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="setDuration(8)">{{ __('8h (Arbeitstag)') }}</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="setDuration(24)">{{ __('24h (1 Tag)') }}</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="setDuration(72)">{{ __('72h (3 Tage)') }}</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="setDuration(168)">{{ __('168h (1 Woche)') }}</flux:button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="sticky top-4 rounded-xl border border-zinc-200 bg-gradient-to-b from-white to-zinc-50 p-4 dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-950" data-test="cost-summary">
                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('Geschätzter Gesamtschaden') }}
                    </div>
                    <div class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-zinc-50" data-test="cost-total">
                        {{ $fmt($totalCost) }} €
                    </div>
                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $fmt($hourlyTotal) }} €/h &times; {{ rtrim(rtrim(number_format($duration, 2, ',', '.'), '0'), ',') }} h
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Systeme ausgefallen') }}</div>
                            <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $selectedCount }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Stundenrate') }}</div>
                            <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $fmt($hourlyTotal) }} €</div>
                        </div>
                    </div>

                    @if ($missingCount > 0)
                        <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-2 text-xs text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                            {{ __(':count ausgewählte System(e) haben keine Stundenkosten hinterlegt und gehen mit 0 € in die Rechnung ein.', ['count' => $missingCount]) }}
                        </div>
                    @endif

                    @if ($selectedCount === 0)
                        <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Wählen Sie links ein oder mehrere Systeme aus, um eine Schätzung zu sehen.') }}
                        </div>
                    @endif
                </div>

                @if ($selectedCount > 0)
                    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <flux:heading size="sm">{{ __('Aufschlüsselung') }}</flux:heading>
                        </div>
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($summary['systems'] as $row)
                                <div class="flex items-center justify-between gap-3 px-4 py-2 text-sm" wire:key="sum-{{ $row['id'] }}">
                                    <div class="min-w-0 flex-1 truncate text-zinc-800 dark:text-zinc-200">{{ $row['name'] }}</div>
                                    <div class="shrink-0 text-right">
                                        <div class="tabular-nums text-zinc-900 dark:text-zinc-50">{{ $fmt((int) $row['partial']) }} €</div>
                                        <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $fmt((int) $row['hourly']) }} €/h</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    {{ __('Hinweis: Es handelt sich um eine grobe Schätzung — der tatsächliche Schaden hängt von Tageszeit, Wochentag, Saison, Vertragsstrafen und Folgekosten ab.') }}
                </div>
            </div>
        </div>
    @endif
</section>
