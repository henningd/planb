<?php

use App\Enums\RiskCategory;
use App\Enums\RiskStatus;
use App\Models\Risk;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Risiko-Register')] class extends Component {
    public string $category = '';

    public string $status = '';

    public bool $only_critical = false;

    public bool $only_overdue = false;

    public ?int $cell_probability = null;

    public ?int $cell_impact = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    #[Computed]
    public function risks()
    {
        $query = Risk::with(['owner', 'mitigations', 'systems'])
            ->orderByDesc('created_at');

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->only_overdue) {
            $query->whereNotNull('review_due_at')->where('review_due_at', '<', now());
        }

        $risks = $query->get();

        if ($this->cell_probability !== null && $this->cell_impact !== null) {
            $risks = $risks->filter(fn (Risk $r) => $r->probability === $this->cell_probability && $r->impact === $this->cell_impact)->values();
        }

        if ($this->only_critical) {
            $risks = $risks->filter(fn (Risk $r) => $r->score() >= 15)->values();
        }

        return $risks;
    }

    /**
     * @return array<int, array<int, int>>
     */
    #[Computed]
    public function heatmap(): array
    {
        $matrix = [];
        for ($p = 1; $p <= 5; $p++) {
            for ($i = 1; $i <= 5; $i++) {
                $matrix[$p][$i] = 0;
            }
        }

        foreach (Risk::all() as $risk) {
            $matrix[$risk->probability][$risk->impact] = ($matrix[$risk->probability][$risk->impact] ?? 0) + 1;
        }

        return $matrix;
    }

    /**
     * @return array{critical: int, high: int, medium: int, low: int}
     */
    #[Computed]
    public function stats(): array
    {
        $stats = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        foreach (Risk::all() as $risk) {
            $stats[$risk->severityLevel()]++;
        }

        return $stats;
    }

    public function selectCell(int $probability, int $impact): void
    {
        if ($this->cell_probability === $probability && $this->cell_impact === $impact) {
            $this->cell_probability = null;
            $this->cell_impact = null;

            return;
        }

        $this->cell_probability = $probability;
        $this->cell_impact = $impact;
    }

    public function clearFilters(): void
    {
        $this->reset(['category', 'status', 'only_critical', 'only_overdue', 'cell_probability', 'cell_impact']);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Risiko-Register') }}</flux:heading>
            <flux:subheading>
                {{ __('Risiken bewerten, Maßnahmen planen, Restrisiko nachweisen — Grundlage für NIS2 und ISO 27001.') }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('risks.create')" wire:navigate :disabled="! $this->hasCompany">
            {{ __('Neues Risiko') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950">
            <div class="text-2xl font-semibold text-rose-700 dark:text-rose-300">{{ $this->stats['critical'] }}</div>
            <div class="text-xs uppercase text-rose-600 dark:text-rose-400">{{ __('Kritisch (Score ≥ 15)') }}</div>
        </div>
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900 dark:bg-orange-950">
            <div class="text-2xl font-semibold text-orange-700 dark:text-orange-300">{{ $this->stats['high'] }}</div>
            <div class="text-xs uppercase text-orange-600 dark:text-orange-400">{{ __('Hoch') }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
            <div class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $this->stats['medium'] }}</div>
            <div class="text-xs uppercase text-amber-600 dark:text-amber-400">{{ __('Mittel') }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-2xl font-semibold text-zinc-700 dark:text-zinc-300">{{ $this->stats['low'] }}</div>
            <div class="text-xs uppercase text-zinc-600 dark:text-zinc-400">{{ __('Niedrig') }}</div>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">{{ __('Risiko-Heatmap') }}</flux:heading>
        <flux:subheading class="mb-4">
            {{ __('Klicken Sie auf eine Zelle, um nach dieser Wahrscheinlichkeit/Schadens-Kombination zu filtern.') }}
        </flux:subheading>

        <div class="flex">
            <div class="flex flex-col items-center justify-around pr-3 text-xs text-zinc-500">
                <div class="rotate-180 [writing-mode:vertical-rl]">{{ __('Wahrscheinlichkeit') }}</div>
            </div>
            <div class="flex-1">
                <div class="grid grid-cols-5 gap-1">
                    @for ($p = 5; $p >= 1; $p--)
                        @for ($i = 1; $i <= 5; $i++)
                            @php
                                $score = $p * $i;
                                $count = $this->heatmap[$p][$i] ?? 0;
                                $bg = match (true) {
                                    $score >= 15 => 'bg-rose-200 dark:bg-rose-900',
                                    $score >= 10 => 'bg-orange-200 dark:bg-orange-900',
                                    $score >= 5 => 'bg-amber-200 dark:bg-amber-900',
                                    default => 'bg-zinc-100 dark:bg-zinc-800',
                                };
                                $isSelected = $cell_probability === $p && $cell_impact === $i;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectCell({{ $p }}, {{ $i }})"
                                class="aspect-square rounded {{ $bg }} flex flex-col items-center justify-center text-xs cursor-pointer hover:opacity-80 transition {{ $isSelected ? 'ring-2 ring-offset-1 ring-sky-500' : '' }}"
                                title="W={{ $p }} · S={{ $i }} · Score={{ $score }}"
                            >
                                <span class="text-base font-semibold">{{ $count }}</span>
                                <span class="text-[10px] text-zinc-600 dark:text-zinc-400">{{ $score }}</span>
                            </button>
                        @endfor
                    @endfor
                </div>
                <div class="mt-2 flex justify-between text-xs text-zinc-500">
                    <span>← {{ __('weniger Schaden') }}</span>
                    <span>{{ __('Schaden') }}</span>
                    <span>{{ __('mehr Schaden') }} →</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <flux:select wire:model.live="category" size="sm" class="w-48">
            <flux:select.option value="">{{ __('Alle Kategorien') }}</flux:select.option>
            @foreach (RiskCategory::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" size="sm" class="w-48">
            <flux:select.option value="">{{ __('Alle Status') }}</flux:select.option>
            @foreach (RiskStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button size="sm" :variant="$only_critical ? 'primary' : 'filled'" wire:click="$toggle('only_critical')">
            {{ __('Nur kritisch') }}
        </flux:button>

        <flux:button size="sm" :variant="$only_overdue ? 'primary' : 'filled'" wire:click="$toggle('only_overdue')">
            {{ __('Review überfällig') }}
        </flux:button>

        @if ($category || $status || $only_critical || $only_overdue || $cell_probability !== null)
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">
                {{ __('Filter zurücksetzen') }}
            </flux:button>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3">{{ __('Titel') }}</th>
                    <th class="px-4 py-3">{{ __('Kategorie') }}</th>
                    <th class="px-4 py-3">{{ __('Score') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Eigentümer') }}</th>
                    <th class="px-4 py-3">{{ __('Review') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                @forelse ($this->risks as $risk)
                    <tr class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800" wire:click="$dispatch('navigate-to', { url: '{{ route('risks.show', $risk) }}' })">
                        <td class="px-4 py-3">
                            <a href="{{ route('risks.show', $risk) }}" wire:navigate class="font-medium hover:underline">
                                {{ $risk->title }}
                            </a>
                            @if ($risk->systems->count() > 0)
                                <div class="text-xs text-zinc-500">
                                    {{ $risk->systems->count() }} {{ __('verknüpfte Systeme') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$risk->category->color()" size="sm">{{ $risk->category->label() }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$risk->severityColor()" size="sm">
                                {{ $risk->score() }} · {{ $risk->severityLabel() }}
                            </flux:badge>
                            @if ($risk->residualScore() !== null)
                                <div class="mt-1 text-xs text-zinc-500">
                                    {{ __('Restrisiko:') }} {{ $risk->residualScore() }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$risk->status->color()" size="sm">{{ $risk->status->label() }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $risk->owner?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            @if ($risk->review_due_at)
                                <span @class(['text-rose-600 dark:text-rose-400 font-medium' => $risk->isOverdue()])>
                                    {{ $risk->review_due_at->format('d.m.Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                            {{ __('Keine Risiken gefunden — entweder Filter zu eng oder noch keine erfasst.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
