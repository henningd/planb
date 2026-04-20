<?php

use App\Models\GlobalScenario;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Globale Szenarien')] class extends Component {
    public string $name = '';

    public string $description = '';

    public string $trigger = '';

    public ?string $deletingId = null;

    #[Computed]
    public function scenarios()
    {
        return GlobalScenario::with('steps')->orderBy('sort')->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'description', 'trigger']);
        Flux::modal('global-scenario-create')->show();
    }

    public function create(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger' => ['nullable', 'string', 'max:2000'],
        ]);

        $scenario = GlobalScenario::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'trigger' => $validated['trigger'] ?: null,
            'is_active' => true,
            'sort' => ((int) GlobalScenario::max('sort')) + 1,
        ]);

        Flux::modal('global-scenario-create')->close();

        $this->redirectRoute('admin.scenarios.show', ['globalScenario' => $scenario->id], navigate: true);
    }

    public function toggleActive(string $id): void
    {
        $scenario = GlobalScenario::findOrFail($id);
        $scenario->update(['is_active' => ! $scenario->is_active]);

        unset($this->scenarios);

        Flux::toast(text: $scenario->is_active ? __('Aktiv geschaltet.') : __('Deaktiviert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('global-scenario-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            GlobalScenario::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->scenarios);
            Flux::modal('global-scenario-delete')->close();
            Flux::toast(variant: 'success', text: __('Szenario gelöscht.'));
        }
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Änderungen hier wirken sich auf alle künftig neu angelegten Firmen aus. Bestehende Firmen behalten ihre eigenen Kopien.') }}
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Globale Szenario-Bibliothek') }}</flux:heading>
            <flux:subheading>
                {{ __('Aktive Szenarien werden beim Anlegen einer neuen Firma als Startset kopiert.') }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">
            {{ __('Neues Szenario') }}
        </flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($this->scenarios as $scenario)
            <div class="flex flex-col rounded-xl border p-5 {{ $scenario->is_active ? 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' : 'border-zinc-200 bg-zinc-50 opacity-70 dark:border-zinc-800 dark:bg-zinc-900/50' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <flux:heading size="base">{{ $scenario->name }}</flux:heading>
                            @if ($scenario->is_active)
                                <flux:badge color="emerald" size="sm">{{ __('Aktiv') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Inaktiv') }}</flux:badge>
                            @endif
                        </div>
                        @if ($scenario->description)
                            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $scenario->description }}
                            </flux:text>
                        @endif
                    </div>
                    <flux:badge color="zinc" size="sm">{{ $scenario->steps->count() }} {{ __('Schritte') }}</flux:badge>
                </div>

                <div class="mt-5 flex items-center justify-between gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:dropdown align="start">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" :href="route('admin.scenarios.show', $scenario)" wire:navigate>
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.item icon="power" wire:click="toggleActive('{{ $scenario->id }}')">
                                {{ $scenario->is_active ? __('Deaktivieren') : __('Aktivieren') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $scenario->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <flux:button size="sm" variant="primary" :href="route('admin.scenarios.show', $scenario)" wire:navigate icon="pencil">
                        {{ __('Bearbeiten') }}
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine globalen Szenarien angelegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="global-scenario-create" class="max-w-xl">
        <form wire:submit="create" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Neues globales Szenario') }}</flux:heading>
                <flux:subheading>{{ __('Wird aktiv, sobald Sie es auf der Detailseite als aktiv markieren und Schritte hinzugefügt haben.') }}</flux:subheading>
            </div>
            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="3" />
            <flux:textarea wire:model="trigger" :label="__('Auslöser')" rows="2" />
            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Anlegen & bearbeiten') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="global-scenario-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Globales Szenario löschen?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Kunden-Kopien bleiben erhalten. Nur die Bibliotheks-Vorlage wird entfernt.') }}
                </flux:subheading>
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
