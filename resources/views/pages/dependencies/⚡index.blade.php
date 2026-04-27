<?php

use App\Models\Company;
use App\Support\Graph\DependencyGraphBuilder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Abhängigkeiten')] class extends Component {
    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()?->currentCompany();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function graph(): array
    {
        $company = $this->company;
        if (! $company) {
            return ['nodes' => [], 'edges' => [], 'stats' => ['systems' => 0, 'edges' => 0, 'isolated' => 0, 'cycles' => 0], 'levels' => [], 'categories' => []];
        }

        return DependencyGraphBuilder::build($company);
    }
}; ?>

<section class="w-full">
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Abhängigkeiten') }}</flux:heading>
            <flux:subheading>
                {{ __('Visualisiert, welche Systeme voneinander abhängen. Hover zeigt die Kette stromauf- und abwärts, Klick öffnet das Detail-Panel.') }}
            </flux:subheading>
        </div>
    </div>

    @php
        $graph = $this->graph;
        $stats = $graph['stats'];
        $hasNodes = count($graph['nodes']) > 0;
    @endphp

    @if (! $this->company)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @elseif (! $hasNodes)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Keine Systeme erfasst. Legen Sie zuerst Systeme an, dann können hier ihre Abhängigkeiten visualisiert werden.') }}
        </div>
    @else
        <div
            x-data="{
                graph: @js($graph),
                instance: null,
                layout: 'dagre',
                search: '',
                selectedLevels: [],
                selectedCategories: [],
                selected: null,
                init() {
                    this.$nextTick(() => {
                        this.instance = window.PlanB.initDependencyGraph({
                            containerId: 'dep-canvas',
                            nodes: this.graph.nodes,
                            edges: this.graph.edges,
                            layout: this.layout,
                            onSelect: (data) => { this.selected = data; },
                        });
                    });
                },
                applyFilter() {
                    if (!this.instance) return;
                    this.instance.applyFilter({
                        levels: this.selectedLevels,
                        categories: this.selectedCategories,
                        search: this.search,
                    });
                },
                relayout(name) {
                    this.layout = name;
                    if (this.instance) this.instance.relayout(name);
                },
                fit() { if (this.instance) this.instance.fit(); },
                zoomIn() { if (this.instance) this.instance.zoomBy(1.5); },
                zoomOut() { if (this.instance) this.instance.zoomBy(1 / 1.5); },
                resetZoom() { if (this.instance) this.instance.resetZoom(); },
            }"
            x-init="init()"
            class="grid gap-4 lg:grid-cols-[1fr_22rem]"
        >
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 p-3 dark:border-zinc-800">
                    <div class="flex items-center gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="layout === 'dagre' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                            @click="relayout('dagre')"
                        >{{ __('Hierarchisch') }}</button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="layout === 'fcose' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                            @click="relayout('fcose')"
                        >{{ __('Organisch') }}</button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="layout === 'breadthfirst' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                            @click="relayout('breadthfirst')"
                        >{{ __('Ebenen') }}</button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="layout === 'concentric' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-600 dark:text-zinc-300'"
                            @click="relayout('concentric')"
                        >{{ __('Radial') }}</button>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <flux:input
                            x-model.debounce.250ms="search"
                            @input="applyFilter()"
                            size="sm"
                            icon="magnifying-glass"
                            :placeholder="__('Suchen…')"
                            class="w-44"
                        />
                        <div class="flex items-center gap-0.5 rounded-lg bg-zinc-100 p-0.5 dark:bg-zinc-800">
                            <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomOut()" :title="__('Verkleinern')">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M5 9.75A.75.75 0 0 1 5.75 9h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 5 9.75Z"/></svg>
                            </button>
                            <button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="resetZoom()" :title="__('Zoom zurücksetzen')">
                                1:1
                            </button>
                            <button type="button" class="rounded-md px-2 py-1 text-zinc-700 hover:bg-white dark:text-zinc-200 dark:hover:bg-zinc-700" @click="zoomIn()" :title="__('Vergrößern')">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 5.75a.75.75 0 0 0-1.5 0V9h-3.5a.75.75 0 0 0 0 1.5h3.5v3.25a.75.75 0 0 0 1.5 0V10.5h3.5a.75.75 0 0 0 0-1.5h-3.5V5.75Z"/></svg>
                            </button>
                        </div>
                        <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" @click="fit()">{{ __('Einpassen') }}</flux:button>
                    </div>
                </div>

                <div id="dep-canvas" class="h-[640px] w-full bg-zinc-50 dark:bg-zinc-950"></div>

                <div class="flex flex-wrap items-center gap-3 border-t border-zinc-100 p-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span><strong class="text-zinc-700 dark:text-zinc-200">{{ $stats['systems'] }}</strong> {{ __('Systeme') }}</span>
                    <span><strong class="text-zinc-700 dark:text-zinc-200">{{ $stats['edges'] }}</strong> {{ __('Abhängigkeiten') }}</span>
                    @if ($stats['isolated'] > 0)
                        <span><strong class="text-amber-600 dark:text-amber-400">{{ $stats['isolated'] }}</strong> {{ __('isoliert') }}</span>
                    @endif
                    @if ($stats['cycles'] > 0)
                        <span><strong class="text-rose-600 dark:text-rose-400">{{ $stats['cycles'] }}</strong> {{ __('in Zyklus') }}</span>
                    @endif
                    <span class="ml-auto inline-flex items-center gap-3">
                        @foreach ($graph['levels'] as $lvl)
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-block h-2 w-2 rounded-full" style="background-color: {{ $lvl['color'] }}"></span>
                                {{ $lvl['name'] }}
                            </span>
                        @endforeach
                    </span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="base">{{ __('Filter') }}</flux:heading>
                    <div class="mt-3 space-y-3 text-sm">
                        <div>
                            <div class="mb-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Notfall-Level') }}</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($graph['levels'] as $lvl)
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 px-2.5 py-1 text-xs text-zinc-700 transition hover:bg-zinc-50 has-[:checked]:bg-zinc-900 has-[:checked]:text-white dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:has-[:checked]:bg-zinc-50 dark:has-[:checked]:text-zinc-900">
                                        <input type="checkbox" class="hidden" value="{{ $lvl['id'] }}" x-model="selectedLevels" @change="applyFilter()">
                                        <span class="inline-block h-2 w-2 rounded-full" style="background-color: {{ $lvl['color'] }}"></span>
                                        {{ $lvl['name'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="mb-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Kategorie') }}</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($graph['categories'] as $cat)
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 px-2.5 py-1 text-xs text-zinc-700 transition hover:bg-zinc-50 has-[:checked]:bg-zinc-900 has-[:checked]:text-white dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:has-[:checked]:bg-zinc-50 dark:has-[:checked]:text-zinc-900">
                                        <input type="checkbox" class="hidden" value="{{ $cat['value'] }}" x-model="selectedCategories" @change="applyFilter()">
                                        {{ $cat['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <template x-if="!selected">
                        <div class="p-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Klicken Sie ein System im Diagramm an, um Details zu sehen.') }}
                        </div>
                    </template>
                    <template x-if="selected">
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading size="base" x-text="selected.label"></flux:heading>
                                <button type="button" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200" @click="selected = null">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.28 4.28a.75.75 0 0 1 1.06 0L10 8.94l4.66-4.66a.75.75 0 1 1 1.06 1.06L11.06 10l4.66 4.66a.75.75 0 1 1-1.06 1.06L10 11.06l-4.66 4.66a.75.75 0 0 1-1.06-1.06L8.94 10 4.28 5.34a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                </button>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :style="`background-color: ${selected.level_color}; color: ${selected.level_text}`"
                                    x-text="selected.level_name"
                                ></span>
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" x-text="selected.category_label"></span>
                            </div>
                            <dl class="mt-4 space-y-2 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Hängt ab von') }}</dt>
                                    <dd class="font-semibold text-zinc-900 tabular-nums dark:text-zinc-50" x-text="selected.dependencies_count"></dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Abhängige Systeme') }}</dt>
                                    <dd class="font-semibold text-zinc-900 tabular-nums dark:text-zinc-50" x-text="selected.dependents_count"></dd>
                                </div>
                                <template x-if="selected.rto">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('RTO (Min.)') }}</dt>
                                        <dd class="font-semibold text-zinc-900 tabular-nums dark:text-zinc-50" x-text="selected.rto"></dd>
                                    </div>
                                </template>
                            </dl>
                            <flux:button
                                size="sm"
                                variant="primary"
                                class="mt-4 w-full"
                                :href="''"
                                x-bind:href="selected.show_url"
                                wire:navigate
                                icon-trailing="arrow-right"
                            >{{ __('Zum System') }}</flux:button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    @endif
</section>
