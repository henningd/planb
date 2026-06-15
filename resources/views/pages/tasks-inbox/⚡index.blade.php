<?php

use App\Models\EmergencyResource;
use App\Models\HandbookTest;
use App\Models\PreventiveMeasure;
use App\Models\Risk;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Aufgaben-Inbox')] class extends Component {
    public string $statusFilter = 'open';

    public string $typeFilter = 'all';

    public string $search = '';

    /**
     * Risiko-Reviews nur einbeziehen, wenn das Risikoregister aktiv ist und
     * der/die Nutzer:in es verwalten darf (Admin) — passend zur Sichtbarkeit
     * der Risiko-Seiten.
     */
    public function includeRisks(): bool
    {
        return (bool) config('features.risk_register') && (bool) Auth::user()?->isAtLeastConsultant();
    }

    /**
     * Präventivmaßnahmen nur einbeziehen, wenn das Modul aktiviert ist.
     */
    public function includeMeasures(): bool
    {
        return (bool) config('features.preventive_measures');
    }

    /**
     * Vereinheitlichte, gefilterte und sortierte Fälligkeitsliste aus
     * Sofortmittel-Prüfungen, Testplan-Fälligkeiten und Risiko-Reviews.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function items(): Collection
    {
        $items = collect();

        if (in_array($this->typeFilter, ['all', 'resources'], true)) {
            foreach (EmergencyResource::query()->whereNotNull('next_check_at')->get() as $resource) {
                $items->push([
                    'type' => 'resource',
                    'id' => $resource->id,
                    'title' => $resource->name ?? $resource->type->label(),
                    'detail' => $resource->location !== null && $resource->location !== ''
                        ? __('Standort: :loc', ['loc' => $resource->location])
                        : $resource->type->label(),
                    'due' => $resource->next_check_at,
                    'last_done' => $resource->last_check_at,
                    'source_label' => __('Sofortmittel'),
                    'source_color' => 'teal',
                    'source_icon' => 'lifebuoy',
                    'link' => route('emergency-resources.index').'#resource-'.$resource->id,
                ]);
            }
        }

        if (in_array($this->typeFilter, ['all', 'tests'], true)) {
            foreach (HandbookTest::query()->whereNotNull('next_due_at')->with(['responsible', 'responsibleRole'])->get() as $test) {
                $responsible = $test->responsible?->fullName() ?? $test->responsibleRole?->name;

                $items->push([
                    'type' => 'test',
                    'id' => $test->id,
                    'title' => $test->name ?? $test->type->label(),
                    'detail' => $responsible !== null
                        ? __(':type · Verantwortlich: :who', ['type' => $test->type->label(), 'who' => $responsible])
                        : $test->type->label(),
                    'due' => $test->next_due_at,
                    'last_done' => $test->last_executed_at,
                    'source_label' => __('Testplan'),
                    'source_color' => 'indigo',
                    'source_icon' => 'clipboard-document-check',
                    'link' => route('handbook-tests.index').'#test-'.$test->id,
                ]);
            }
        }

        if ($this->includeMeasures() && in_array($this->typeFilter, ['all', 'measures'], true)) {
            foreach (PreventiveMeasure::recurringDue()->with(['system', 'responsible', 'responsibleRole'])->get() as $measure) {
                $responsible = $measure->responsible?->fullName() ?? $measure->responsibleRole?->name;

                $items->push([
                    'type' => 'measure',
                    'id' => $measure->id,
                    'title' => $measure->title,
                    'detail' => $responsible !== null
                        ? __(':category · :system · Verantwortlich: :who', ['category' => $measure->category->label(), 'system' => $measure->system?->name, 'who' => $responsible])
                        : __(':category · :system', ['category' => $measure->category->label(), 'system' => $measure->system?->name]),
                    'due' => $measure->next_due_at,
                    'last_done' => $measure->last_executed_at,
                    'source_label' => __('Prävention'),
                    'source_color' => 'emerald',
                    'source_icon' => 'shield-check',
                    'link' => route('preventive-measures.index', ['system' => $measure->system_id]),
                ]);
            }
        }

        if ($this->includeRisks() && in_array($this->typeFilter, ['all', 'risks'], true)) {
            foreach (Risk::query()->whereNotNull('review_due_at')->where('status', '!=', 'closed')->get() as $risk) {
                $items->push([
                    'type' => 'risk',
                    'id' => $risk->id,
                    'title' => __('Review: :name', ['name' => $risk->title]),
                    'detail' => __(':category · :severity', ['category' => $risk->category->label(), 'severity' => $risk->severityLabel()]),
                    'due' => $risk->review_due_at,
                    'last_done' => null,
                    'source_label' => __('Risiko'),
                    'source_color' => 'rose',
                    'source_icon' => 'shield-exclamation',
                    'link' => route('risks.show', ['risk' => $risk->id]),
                ]);
            }
        }

        $today = now()->startOfDay();

        if ($this->statusFilter === 'overdue') {
            $items = $items->filter(fn (array $i) => $i['due'] !== null && $i['due']->lt($today));
        }

        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $items = $items->filter(fn (array $i) => str_contains(mb_strtolower($i['title'].' '.$i['detail']), $term));
        }

        return $items->sort(function (array $a, array $b) use ($today) {
            $bucket = function (array $i) use ($today): int {
                if ($i['due'] === null) {
                    return 2;
                }

                return $i['due']->lt($today) ? 0 : 1;
            };

            $ba = $bucket($a);
            $bb = $bucket($b);
            if ($ba !== $bb) {
                return $ba <=> $bb;
            }

            if ($a['due'] === null && $b['due'] === null) {
                return strcasecmp($a['title'], $b['title']);
            }
            if ($a['due'] === null) {
                return 1;
            }
            if ($b['due'] === null) {
                return -1;
            }

            return $a['due'] <=> $b['due'];
        })->values();
    }

    #[Computed]
    public function openCount(): int
    {
        $count = EmergencyResource::whereNotNull('next_check_at')->count()
            + HandbookTest::whereNotNull('next_due_at')->count();

        if ($this->includeMeasures()) {
            $count += PreventiveMeasure::recurringDue()->count();
        }

        if ($this->includeRisks()) {
            $count += Risk::whereNotNull('review_due_at')->where('status', '!=', 'closed')->count();
        }

        return $count;
    }

    #[Computed]
    public function overdueCount(): int
    {
        $today = now()->toDateString();

        $count = EmergencyResource::whereNotNull('next_check_at')->whereDate('next_check_at', '<', $today)->count()
            + HandbookTest::whereNotNull('next_due_at')->whereDate('next_due_at', '<', $today)->count();

        if ($this->includeMeasures()) {
            $count += PreventiveMeasure::recurringDue()->whereDate('next_due_at', '<', $today)->count();
        }

        if ($this->includeRisks()) {
            $count += Risk::whereNotNull('review_due_at')->where('status', '!=', 'closed')->whereDate('review_due_at', '<', $today)->count();
        }

        return $count;
    }

    #[Computed]
    public function doneTodayCount(): int
    {
        $today = now()->toDateString();

        $count = EmergencyResource::whereDate('last_check_at', $today)->count()
            + HandbookTest::whereDate('last_executed_at', $today)->count();

        if ($this->includeMeasures()) {
            $count += PreventiveMeasure::whereDate('last_executed_at', $today)->count();
        }

        return $count;
    }

    /**
     * Sofortmittel-Prüfung als erledigt markieren: letzte Prüfung = heute,
     * nächste Prüfung leeren (kein Intervall hinterlegt). Der Eintrag
     * verschwindet damit aus der Liste; das nächste Datum kann auf der
     * Sofortmittel-Seite gesetzt werden.
     */
    public function markResourceChecked(string $id): void
    {
        $resource = EmergencyResource::findOrFail($id);
        $resource->update(['last_check_at' => now()->toDateString(), 'next_check_at' => null]);

        $this->clearCaches();
        Flux::toast(variant: 'success', text: __('Prüfung als erledigt vermerkt. Nächstes Datum bei Bedarf in den Sofortmitteln setzen.'));
    }

    /**
     * Testplan-Eintrag als durchgeführt markieren: schreibt das nächste
     * Fälligkeitsdatum gemäß Intervall fort.
     */
    public function markTestExecuted(string $id): void
    {
        $test = HandbookTest::findOrFail($id);
        $test->markExecuted();

        $this->clearCaches();
        Flux::toast(variant: 'success', text: __('Test als durchgeführt vermerkt.'));
    }

    /**
     * Präventivmaßnahme als durchgeführt markieren: schreibt das nächste
     * Fälligkeitsdatum gemäß Intervall fort.
     */
    public function markMeasureExecuted(string $id): void
    {
        PreventiveMeasure::findOrFail($id)->markExecuted();

        $this->clearCaches();
        Flux::toast(variant: 'success', text: __('Maßnahme als durchgeführt vermerkt.'));
    }

    private function clearCaches(): void
    {
        unset($this->items, $this->openCount, $this->overdueCount, $this->doneTodayCount);
    }

    public function resetFilters(): void
    {
        $this->statusFilter = 'open';
        $this->typeFilter = 'all';
        $this->search = '';
    }

    public function hasActiveFilters(): bool
    {
        return $this->statusFilter !== 'open' || $this->typeFilter !== 'all' || $this->search !== '';
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{text: string, color: string}
     */
    public function dueLabel(array $item): array
    {
        $due = $item['due'];
        if ($due === null) {
            return ['text' => __('Ohne Fälligkeit'), 'color' => 'zinc'];
        }

        $today = now()->startOfDay();
        $endOfWeek = now()->endOfWeek();

        if ($due->lt($today)) {
            return ['text' => __('Überfällig: :date', ['date' => $due->format('d.m.Y')]), 'color' => 'red'];
        }
        if ($due->isSameDay($today)) {
            return ['text' => __('Heute'), 'color' => 'amber'];
        }
        if ($due->lte($endOfWeek)) {
            return ['text' => __('Diese Woche: :date', ['date' => $due->format('d.m.')]), 'color' => 'sky'];
        }

        return ['text' => $due->format('d.m.Y'), 'color' => 'zinc'];
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Aufgaben-Inbox') }}</flux:heading>
        <flux:subheading>{{ __('Fällige Sofortmittel-Prüfungen, Testplan-Termine, Präventivmaßnahmen und Risiko-Reviews auf einen Blick.') }}</flux:subheading>
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center gap-2 text-sm">
            <flux:badge color="sky" size="sm">{{ $this->openCount }} {{ __('offen') }}</flux:badge>
            <flux:badge color="red" size="sm">{{ $this->overdueCount }} {{ __('überfällig') }}</flux:badge>
            <flux:badge color="emerald" size="sm">{{ $this->doneTodayCount }} {{ __('heute erledigt') }}</flux:badge>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:select wire:model.live="statusFilter" :label="__('Status')" class="min-w-40">
            <flux:select.option value="open">{{ __('Offen') }}</flux:select.option>
            <flux:select.option value="overdue">{{ __('Überfällig') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="typeFilter" :label="__('Art')" class="min-w-48">
            <flux:select.option value="all">{{ __('Alle') }}</flux:select.option>
            <flux:select.option value="resources">{{ __('Sofortmittel') }}</flux:select.option>
            <flux:select.option value="tests">{{ __('Testplan') }}</flux:select.option>
            @if ($this->includeMeasures())
                <flux:select.option value="measures">{{ __('Prävention') }}</flux:select.option>
            @endif
            @if ($this->includeRisks())
                <flux:select.option value="risks">{{ __('Risiko-Reviews') }}</flux:select.option>
            @endif
        </flux:select>

        <flux:input wire:model.live.debounce.300ms="search" :label="__('Suche')" type="search" :placeholder="__('Titel oder Detail…')" class="min-w-56" />

        @if ($this->hasActiveFilters())
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters" type="button">
                {{ __('Filter zurücksetzen') }}
            </flux:button>
        @endif
    </div>

    <div class="space-y-3">
        @forelse ($this->items as $item)
            @php($due = $this->dueLabel($item))
            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 sm:flex-row sm:items-start sm:justify-between dark:border-zinc-700 dark:bg-zinc-900">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="base">{{ $item['title'] }}</flux:heading>
                        <flux:badge :color="$item['source_color']" size="sm" :icon="$item['source_icon']">{{ $item['source_label'] }}</flux:badge>
                        <flux:badge :color="$due['color']" size="sm">{{ $due['text'] }}</flux:badge>
                    </div>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $item['detail'] }}</flux:text>
                    @if ($item['last_done'])
                        <flux:text class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                            {{ __('Zuletzt: :date', ['date' => $item['last_done']->format('d.m.Y')]) }}
                        </flux:text>
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="$item['link']" type="button">
                        {{ __('Öffnen') }}
                    </flux:button>
                    @if ($item['type'] === 'resource')
                        <flux:button size="sm" variant="primary" icon="check" wire:click="markResourceChecked('{{ $item['id'] }}')" type="button">
                            {{ __('Geprüft') }}
                        </flux:button>
                    @elseif ($item['type'] === 'test')
                        <flux:button size="sm" variant="primary" icon="check" wire:click="markTestExecuted('{{ $item['id'] }}')" type="button">
                            {{ __('Durchgeführt') }}
                        </flux:button>
                    @elseif ($item['type'] === 'measure')
                        <flux:button size="sm" variant="primary" icon="check" wire:click="markMeasureExecuted('{{ $item['id'] }}')" type="button">
                            {{ __('Durchgeführt') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Keine fälligen Prüfungen oder Tests.') }}
                </flux:text>
                @if ($this->hasActiveFilters())
                    <div class="mt-3">
                        <flux:button size="sm" variant="filled" icon="x-mark" wire:click="resetFilters" type="button">
                            {{ __('Filter zurücksetzen') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</section>
