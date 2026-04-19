<?php

use App\Models\GlobalScenario;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Globales Szenario')] class extends Component {
    public GlobalScenario $globalScenario;

    public string $name = '';

    public string $description = '';

    public string $trigger = '';

    public bool $is_active = true;

    public ?string $editingStepId = null;

    public string $stepTitle = '';

    public string $stepDescription = '';

    public string $stepResponsible = '';

    public int $stepSort = 1;

    public ?string $deletingStepId = null;

    public function mount(GlobalScenario $globalScenario): void
    {
        $this->globalScenario = $globalScenario->load('steps');
        $this->name = $globalScenario->name;
        $this->description = (string) $globalScenario->description;
        $this->trigger = (string) $globalScenario->trigger;
        $this->is_active = $globalScenario->is_active;
    }

    public function saveMeta(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        $this->globalScenario->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'trigger' => $validated['trigger'] ?: null,
            'is_active' => $validated['is_active'],
        ]);

        Flux::toast(variant: 'success', text: __('Szenario gespeichert.'));
    }

    public function openAddStep(): void
    {
        $this->resetStepForm();
        $this->stepSort = ((int) $this->globalScenario->steps()->max('sort')) + 1;
        Flux::modal('global-step-form')->show();
    }

    public function openEditStep(string $id): void
    {
        $step = $this->globalScenario->steps()->findOrFail($id);

        $this->editingStepId = $step->id;
        $this->stepTitle = $step->title;
        $this->stepDescription = (string) $step->description;
        $this->stepResponsible = (string) $step->responsible;
        $this->stepSort = (int) $step->sort;

        Flux::modal('global-step-form')->show();
    }

    public function saveStep(): void
    {
        $validated = $this->validate([
            'stepTitle' => ['required', 'string', 'max:255'],
            'stepDescription' => ['nullable', 'string', 'max:2000'],
            'stepResponsible' => ['nullable', 'string', 'max:255'],
            'stepSort' => ['integer', 'min:0', 'max:1000'],
        ]);

        $payload = [
            'title' => $validated['stepTitle'],
            'description' => $validated['stepDescription'] ?: null,
            'responsible' => $validated['stepResponsible'] ?: null,
            'sort' => $validated['stepSort'],
        ];

        if ($this->editingStepId) {
            $this->globalScenario->steps()->findOrFail($this->editingStepId)->update($payload);
        } else {
            $this->globalScenario->steps()->create($payload);
        }

        Flux::modal('global-step-form')->close();
        $this->resetStepForm();
        $this->globalScenario->load('steps');

        Flux::toast(variant: 'success', text: __('Schritt gespeichert.'));
    }

    public function confirmDeleteStep(string $id): void
    {
        $this->deletingStepId = $id;
        Flux::modal('global-step-delete')->show();
    }

    public function deleteStep(): void
    {
        if ($this->deletingStepId) {
            $this->globalScenario->steps()->findOrFail($this->deletingStepId)->delete();
            $this->deletingStepId = null;
            $this->globalScenario->load('steps');
            Flux::modal('global-step-delete')->close();
            Flux::toast(variant: 'success', text: __('Schritt gelöscht.'));
        }
    }

    protected function resetStepForm(): void
    {
        $this->reset(['editingStepId', 'stepTitle', 'stepDescription', 'stepResponsible']);
        $this->stepSort = 1;
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="mb-2">
        <flux:link :href="route('admin.scenarios.index')" wire:navigate class="text-sm">
            ← {{ __('Alle globalen Szenarien') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="saveMeta" class="space-y-5 p-6">
            <div class="flex items-start justify-between gap-4">
                <flux:heading size="xl">{{ __('Globales Szenario bearbeiten') }}</flux:heading>
                <flux:badge color="zinc" size="sm">{{ $globalScenario->steps->count() }} {{ __('Schritte') }}</flux:badge>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="3" />
            <flux:textarea wire:model="trigger" :label="__('Auslöser')" rows="2" />
            <flux:switch wire:model="is_active" :label="__('Aktiv – an neue Kunden ausliefern')" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button variant="primary" type="submit">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Schritte') }}</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="openAddStep">
            {{ __('Schritt hinzufügen') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($globalScenario->steps as $step)
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <flux:badge color="zinc" size="sm">#{{ $step->sort }}</flux:badge>
                        <span class="font-medium">{{ $step->title }}</span>
                    </div>
                    @if ($step->description)
                        <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $step->description }}</flux:text>
                    @endif
                    @if ($step->responsible)
                        <div class="mt-2">
                            <flux:badge color="zinc" size="sm">{{ __('Wer') }}: {{ $step->responsible }}</flux:badge>
                        </div>
                    @endif
                </div>

                <flux:dropdown align="end">
                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                    <flux:menu>
                        <flux:menu.item icon="pencil" wire:click="openEditStep('{{ $step->id }}')">
                            {{ __('Bearbeiten') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDeleteStep('{{ $step->id }}')">
                            {{ __('Löschen') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Schritte. Das Szenario wird inaktiv bleiben, solange es leer ist.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="global-step-form" class="max-w-xl">
        <form wire:submit="saveStep" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingStepId ? __('Schritt bearbeiten') : __('Neuen Schritt anlegen') }}
                </flux:heading>
            </div>
            <flux:input wire:model="stepTitle" :label="__('Titel')" required />
            <flux:textarea wire:model="stepDescription" :label="__('Beschreibung')" rows="3" />
            <flux:input wire:model="stepResponsible" :label="__('Verantwortlich')" />
            <flux:input wire:model="stepSort" :label="__('Reihenfolge')" type="number" min="0" />
            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingStepId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="global-step-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Schritt löschen?') }}</flux:heading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteStep">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
