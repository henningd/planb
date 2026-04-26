<?php

use App\Enums\ScenarioRunMode;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ?string $scenarioId = null;

    public string $mode = 'real';

    public string $titleOverride = '';

    /**
     * Liefert alle Szenarien des aktuellen Mandanten alphabetisch sortiert.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Scenario>
     */
    #[Computed]
    public function scenarios()
    {
        return Scenario::orderBy('name')->get();
    }

    #[Computed]
    public function hasScenarios(): bool
    {
        return $this->scenarios->isNotEmpty();
    }

    public function open(): void
    {
        if (! Auth::user()?->currentCompany()) {
            Flux::toast(
                variant: 'warning',
                text: __('Bitte zuerst ein Firmenprofil anlegen.'),
            );

            return;
        }

        if (! $this->hasScenarios) {
            Flux::toast(
                variant: 'warning',
                text: __('Bitte zuerst Szenarien anlegen.'),
            );

            return;
        }

        $this->reset(['scenarioId', 'titleOverride']);
        $this->mode = ScenarioRunMode::Real->value;
        $this->scenarioId = $this->scenarios->first()?->id;

        Flux::modal('incident-launcher')->show();
    }

    public function start(): void
    {
        if (! Auth::user()?->currentCompany()) {
            return;
        }

        $validated = $this->validate([
            'scenarioId' => ['required', 'string', 'exists:scenarios,id'],
            'mode' => ['required', 'in:'.collect(ScenarioRunMode::cases())->pluck('value')->implode(',')],
            'titleOverride' => ['nullable', 'string', 'max:255'],
        ]);

        $scenario = Scenario::with('steps')->findOrFail($validated['scenarioId']);

        $title = filled($validated['titleOverride'])
            ? $validated['titleOverride']
            : $scenario->name.' · '.now()->format('d.m.Y H:i');

        $run = DB::transaction(function () use ($scenario, $validated, $title) {
            $run = ScenarioRun::create([
                'scenario_id' => $scenario->id,
                'started_by_user_id' => Auth::id(),
                'title' => $title,
                'mode' => $validated['mode'],
                'started_at' => now(),
            ]);

            foreach ($scenario->steps as $step) {
                $run->steps()->create([
                    'sort' => $step->sort,
                    'title' => $step->title,
                    'description' => $step->description,
                    'responsible' => $step->responsible,
                ]);
            }

            return $run;
        });

        Flux::modal('incident-launcher')->close();
        $this->reset(['scenarioId', 'titleOverride']);

        $this->redirectRoute('scenario-runs.show', ['run' => $run->id], navigate: true);
    }
}; ?>

<div class="px-2 pb-2">
    <flux:button
        variant="danger"
        icon="megaphone"
        wire:click="open"
        class="w-full justify-center in-data-flux-sidebar-collapsed-desktop:px-0"
        data-test="incident-launcher-trigger"
    >
        <span class="in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Notfall melden') }}</span>
    </flux:button>

    <flux:modal name="incident-launcher" class="max-w-md" focusable>
        <form wire:submit="start" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Notfall melden') }}</flux:heading>
                <flux:subheading>
                    {{ __('Wählen Sie das passende Szenario und starten Sie den Ablauf. Jeder Schritt wird mit Zeitstempel protokolliert.') }}
                </flux:subheading>
            </div>

            <flux:select wire:model="scenarioId" :label="__('Szenario')" required data-test="incident-launcher-scenario">
                @foreach ($this->scenarios as $scenario)
                    <flux:select.option value="{{ $scenario->id }}">{{ $scenario->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:field>
                <flux:label>{{ __('Modus') }}</flux:label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" wire:model="mode" value="real" class="peer sr-only">
                        <div class="rounded-lg border border-zinc-200 p-3 text-center peer-checked:border-rose-500 peer-checked:bg-rose-50 dark:border-zinc-700 dark:peer-checked:bg-rose-950">
                            <flux:icon.exclamation-triangle class="mx-auto mb-1 h-5 w-5" />
                            <div class="text-sm font-medium">{{ __('Echte Lage') }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Ernstfall') }}</div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" wire:model="mode" value="drill" class="peer sr-only">
                        <div class="rounded-lg border border-zinc-200 p-3 text-center peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:border-zinc-700 dark:peer-checked:bg-indigo-950">
                            <flux:icon.academic-cap class="mx-auto mb-1 h-5 w-5" />
                            <div class="text-sm font-medium">{{ __('Tabletop') }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Übung') }}</div>
                        </div>
                    </label>
                </div>
            </flux:field>

            <flux:input
                wire:model="titleOverride"
                :label="__('Bezeichnung (optional)')"
                :placeholder="__('Default: Szenario-Name + Datum')"
                data-test="incident-launcher-title"
            />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" type="submit" icon="play" data-test="incident-launcher-submit">
                    {{ __('Run starten') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
