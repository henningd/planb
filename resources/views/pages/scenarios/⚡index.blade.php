<?php

use App\Enums\ScenarioRunMode;
use App\Models\GlobalScenario;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Szenarien')] class extends Component {
    public ?string $startingScenarioId = null;

    public string $runTitle = '';

    public string $runMode = 'drill';

    public string $newName = '';

    public string $newDescription = '';

    public string $newTrigger = '';

    public ?string $deletingId = null;

    #[Computed]
    public function scenarios()
    {
        return Scenario::with('steps')->orderBy('name')->get();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    public function openStart(string $id): void
    {
        $scenario = Scenario::findOrFail($id);

        $this->startingScenarioId = $id;
        $this->runTitle = $scenario->name.' · '.now()->format('d.m.Y H:i');
        $this->runMode = ScenarioRunMode::Drill->value;

        Flux::modal('scenario-start')->show();
    }

    public function openCreate(): void
    {
        $this->reset(['newName', 'newDescription', 'newTrigger']);
        Flux::modal('scenario-create')->show();
    }

    #[Computed]
    public function canLoadTemplates(): bool
    {
        return $this->hasCompany
            && $this->scenarios->isEmpty()
            && GlobalScenario::where('is_active', true)->exists();
    }

    public function loadTemplates(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        $company = Auth::user()->currentCompany();
        $globals = GlobalScenario::where('is_active', true)->with('steps')->orderBy('sort')->get();

        $imported = 0;

        DB::transaction(function () use ($company, $globals, &$imported) {
            foreach ($globals as $global) {
                if ($company->scenarios()->where('name', $global->name)->exists()) {
                    continue;
                }

                $scenario = $company->scenarios()->create([
                    'name' => $global->name,
                    'description' => $global->description,
                    'trigger' => $global->trigger,
                ]);

                foreach ($global->steps as $step) {
                    $scenario->steps()->create([
                        'sort' => $step->sort,
                        'title' => $step->title,
                        'description' => $step->description,
                        'responsible' => $step->responsible,
                    ]);
                }

                $imported++;
            }
        });

        unset($this->scenarios);

        Flux::toast(
            variant: 'success',
            text: __(':count Vorlagen-Szenarien geladen.', ['count' => $imported]),
        );
    }

    public function createScenario(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newDescription' => ['nullable', 'string', 'max:2000'],
            'newTrigger' => ['nullable', 'string', 'max:2000'],
        ]);

        $scenario = Scenario::create([
            'name' => $validated['newName'],
            'description' => $validated['newDescription'] ?: null,
            'trigger' => $validated['newTrigger'] ?: null,
        ]);

        Flux::modal('scenario-create')->close();
        $this->reset(['newName', 'newDescription', 'newTrigger']);

        $this->redirectRoute('scenarios.show', ['scenario' => $scenario->id], navigate: true);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('scenario-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Scenario::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->scenarios);
            Flux::modal('scenario-delete')->close();
            Flux::toast(variant: 'success', text: __('Szenario gelöscht.'));
        }
    }

    public function start(): void
    {
        $validated = $this->validate([
            'runTitle' => ['required', 'string', 'max:255'],
            'runMode' => ['required', 'in:'.collect(ScenarioRunMode::cases())->pluck('value')->implode(',')],
        ]);

        $scenario = Scenario::with('steps')->findOrFail($this->startingScenarioId);

        $run = DB::transaction(function () use ($scenario, $validated) {
            $run = ScenarioRun::create([
                'scenario_id' => $scenario->id,
                'started_by_user_id' => Auth::id(),
                'title' => $validated['runTitle'],
                'mode' => $validated['runMode'],
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

        Flux::modal('scenario-start')->close();
        $this->reset(['startingScenarioId', 'runTitle', 'runMode']);

        $this->redirectRoute('scenario-runs.show', ['run' => $run->id], navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Szenarien & Playbooks') }}</flux:heading>
            <flux:subheading>
                {{ __('Vorbereitete Abläufe für typische Notfälle. Starten Sie eine Übung oder einen Ernstfall – Schritte werden automatisch protokolliert.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neues Szenario') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany && $this->scenarios->isEmpty())
        <div class="mb-6 rounded-xl border border-dashed border-indigo-300 bg-indigo-50 p-8 text-center dark:border-indigo-800 dark:bg-indigo-950/40">
            <flux:heading size="base" class="text-indigo-900 dark:text-indigo-100">
                {{ __('Keine Szenarien angelegt') }}
            </flux:heading>
            <flux:text class="mt-2 text-sm text-indigo-900/80 dark:text-indigo-200/80">
                {{ __('Sie können mit den vorgefertigten Standard-Szenarien starten oder ein eigenes anlegen.') }}
            </flux:text>
            <div class="mt-4 flex items-center justify-center gap-2">
                @if ($this->canLoadTemplates)
                    <flux:button variant="primary" icon="sparkles" wire:click="loadTemplates">
                        {{ __('Standard-Vorlagen laden') }}
                    </flux:button>
                @endif
                <flux:button variant="filled" icon="plus" wire:click="openCreate">
                    {{ __('Eigenes Szenario anlegen') }}
                </flux:button>
            </div>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($this->scenarios as $scenario)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <flux:heading size="base">{{ $scenario->name }}</flux:heading>
                        @if ($scenario->description)
                            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $scenario->description }}
                            </flux:text>
                        @endif
                    </div>
                    <flux:badge color="zinc" size="sm">{{ $scenario->steps->count() }} {{ __('Schritte') }}</flux:badge>
                </div>

                @if ($scenario->trigger)
                    <div class="mt-3 rounded-md bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <span class="font-medium">{{ __('Auslöser') }}:</span> {{ $scenario->trigger }}
                    </div>
                @endif

                <ol class="mt-4 space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                    @foreach ($scenario->steps->take(4) as $step)
                        <li class="flex gap-2">
                            <span class="text-zinc-400">{{ $step->sort }}.</span>
                            <span>{{ $step->title }}</span>
                        </li>
                    @endforeach
                    @if ($scenario->steps->count() > 4)
                        <li class="text-xs text-zinc-500 dark:text-zinc-400">
                            + {{ $scenario->steps->count() - 4 }} {{ __('weitere Schritte') }}
                        </li>
                    @endif
                </ol>

                <div class="mt-5 flex items-center justify-between gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:dropdown align="start">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" :href="route('scenarios.show', $scenario)" wire:navigate>
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $scenario->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <flux:button size="sm" variant="primary" wire:click="openStart('{{ $scenario->id }}')" icon="play">
                        {{ __('Starten') }}
                    </flux:button>
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="scenario-start" class="max-w-md">
        <form wire:submit="start" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Szenario starten') }}</flux:heading>
                <flux:subheading>{{ __('Übung oder Ernstfall – wird klar getrennt protokolliert.') }}</flux:subheading>
            </div>

            <flux:input wire:model="runTitle" :label="__('Bezeichnung')" required />

            <flux:field>
                <flux:label>{{ __('Modus') }}</flux:label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" wire:model="runMode" value="drill" class="peer sr-only">
                        <div class="rounded-lg border border-zinc-200 p-3 text-center peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:border-zinc-700 dark:peer-checked:bg-indigo-950">
                            <flux:icon.academic-cap class="mx-auto mb-1 h-5 w-5" />
                            <div class="text-sm font-medium">{{ __('Übung') }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Trockenübung') }}</div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" wire:model="runMode" value="real" class="peer sr-only">
                        <div class="rounded-lg border border-zinc-200 p-3 text-center peer-checked:border-rose-500 peer-checked:bg-rose-50 dark:border-zinc-700 dark:peer-checked:bg-rose-950">
                            <flux:icon.exclamation-triangle class="mx-auto mb-1 h-5 w-5" />
                            <div class="text-sm font-medium">{{ __('Ernstfall') }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Echter Vorfall') }}</div>
                        </div>
                    </label>
                </div>
            </flux:field>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" icon="play">
                    {{ __('Jetzt starten') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="scenario-create" class="max-w-xl">
        <form wire:submit="createScenario" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Neues Szenario anlegen') }}</flux:heading>
                <flux:subheading>{{ __('Sie können Schritte im nächsten Schritt hinzufügen.') }}</flux:subheading>
            </div>

            <flux:input wire:model="newName" :label="__('Name')" required placeholder="z. B. Ausfall Buchhaltungsserver" />
            <flux:textarea wire:model="newDescription" :label="__('Beschreibung')" rows="3" placeholder="{{ __('Was genau passiert in diesem Szenario?') }}" />
            <flux:textarea wire:model="newTrigger" :label="__('Auslöser')" rows="2" placeholder="{{ __('Woran erkennen Sie, dass dieses Szenario zutrifft?') }}" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Anlegen & bearbeiten') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="scenario-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Szenario löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Alle zugehörigen Schritte werden entfernt. Laufende und abgeschlossene Protokolle bleiben erhalten, verlieren aber die Szenario-Verknüpfung.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
