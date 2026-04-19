<?php

use App\Models\EmergencyLevel;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notfall-Level')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $reaction = '';

    public int $sort = 0;

    public ?string $deletingId = null;

    #[Computed]
    public function levels()
    {
        return EmergencyLevel::orderBy('sort')->orderBy('id')->get();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->sort = ((int) EmergencyLevel::max('sort')) + 1;
        Flux::modal('level-form')->show();
    }

    public function openEdit(string $id): void
    {
        $level = EmergencyLevel::findOrFail($id);

        $this->editingId = $level->id;
        $this->name = $level->name;
        $this->description = (string) $level->description;
        $this->reaction = (string) $level->reaction;
        $this->sort = (int) $level->sort;

        Flux::modal('level-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reaction' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0', 'max:1000'],
        ]);

        if ($this->editingId) {
            EmergencyLevel::findOrFail($this->editingId)->update($validated);
        } else {
            EmergencyLevel::create($validated);
        }

        Flux::modal('level-form')->close();
        $this->resetForm();
        unset($this->levels);

        Flux::toast(variant: 'success', text: __('Notfall-Level gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('level-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            EmergencyLevel::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->levels);
            Flux::modal('level-delete')->close();
            Flux::toast(variant: 'success', text: __('Notfall-Level gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'reaction', 'sort']);
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Notfall-Level') }}</flux:heading>
            <flux:subheading>
                {{ __('Definieren Sie Eskalationsstufen und die jeweilige Reaktion im Ernstfall.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neues Level') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($this->levels as $level)
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <flux:badge color="zinc" size="sm">#{{ $level->sort }}</flux:badge>
                        <span class="font-medium">{{ $level->name }}</span>
                    </div>
                    @if ($level->description)
                        <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $level->description }}</flux:text>
                    @endif
                    @if ($level->reaction)
                        <div class="mt-2 rounded-md bg-zinc-50 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <span class="font-medium">{{ __('Reaktion') }}:</span> {{ $level->reaction }}
                        </div>
                    @endif
                </div>

                <flux:dropdown align="end">
                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                    <flux:menu>
                        <flux:menu.item icon="pencil" wire:click="openEdit({{ $level->id }})">
                            {{ __('Bearbeiten') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $level->id }})">
                            {{ __('Löschen') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Notfall-Level angelegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="level-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Level bearbeiten') : __('Neues Level anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Typisch: Kritisch, Wichtig, Beobachten.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Kritisch" />

            <flux:textarea
                wire:model="description"
                :label="__('Beschreibung')"
                rows="3"
                placeholder="Was bedeutet dieses Level?"
            />

            <flux:textarea
                wire:model="reaction"
                :label="__('Reaktion')"
                rows="3"
                placeholder="Was passiert konkret bei diesem Level?"
            />

            <flux:input wire:model="sort" :label="__('Reihenfolge')" type="number" min="0" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="level-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Level löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
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
