<?php

use App\Models\EmergencyResourceCategory;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notfallressourcen-Kategorien')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public int $sort = 0;

    public ?string $deletingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, EmergencyResourceCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return EmergencyResourceCategory::withCount('emergencyResources')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('category-form')->show();
    }

    public function openEdit(string $id): void
    {
        $category = EmergencyResourceCategory::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->sort = $category->sort;

        Flux::modal('category-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $companyId = Auth::user()->currentCompany()?->id;

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('emergency_resource_categories', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($this->editingId),
            ],
            'sort' => ['integer', 'min:0'],
        ]);

        if ($this->editingId) {
            EmergencyResourceCategory::findOrFail($this->editingId)->update($validated);
        } else {
            EmergencyResourceCategory::create($validated);
        }

        Flux::modal('category-form')->close();
        $this->resetForm();
        unset($this->categories);

        Flux::toast(variant: 'success', text: __('Kategorie gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('category-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            EmergencyResourceCategory::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->categories);
            Flux::modal('category-delete')->close();
            Flux::toast(variant: 'success', text: __('Kategorie gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sort']);
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('emergency-resources.index')" wire:navigate class="text-sm">
            ← {{ __('Zu den Notfallressourcen') }}
        </flux:link>
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Notfallressourcen-Kategorien') }}</flux:heading>
            <flux:subheading>
                {{ __('Frei konfigurierbare Kategorien für Ihre Notfallressourcen (Sofortmittel). Diese Liste bestimmt die Auswahl beim Anlegen einer Ressource.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Kategorie') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->categories as $category)
            <div wire:key="category-{{ $category->id }}" class="flex items-start justify-between gap-2 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="min-w-0 flex-1">
                    <flux:heading size="base">{{ $category->name }}</flux:heading>
                    <flux:badge color="zinc" size="sm" class="mt-1">
                        {{ trans_choice('{0} Keine Ressourcen|{1} 1 Ressource|[2,*] :count Ressourcen', $category->emergency_resources_count, ['count' => $category->emergency_resources_count]) }}
                    </flux:badge>
                </div>
                <flux:dropdown align="end">
                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                    <flux:menu>
                        <flux:menu.item icon="pencil" wire:click="openEdit('{{ $category->id }}')">
                            {{ __('Bearbeiten') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $category->id }}')">
                            {{ __('Löschen') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Kategorie hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="category-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Kategorie bearbeiten') : __('Neue Kategorie') }}
                </flux:heading>
                <flux:subheading>{{ __('Bezeichnung der Ressourcen-Kategorie.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Drohnen / Sonderausstattung" />
            <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="category-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Kategorie löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Ressourcen dieser Kategorie verlieren ihre Zuordnung (bleiben aber erhalten und lassen sich neu zuordnen).') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" type="button" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
