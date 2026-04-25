<?php

use App\Models\Location;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Standorte')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $street = '';

    public string $postal_code = '';

    public string $city = '';

    public string $country = 'DE';

    public bool $is_headquarters = false;

    public string $phone = '';

    public string $notes = '';

    public int $sort = 0;

    public ?string $deletingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, Location>
     */
    #[Computed]
    public function locations(): Collection
    {
        return Location::orderByDesc('is_headquarters')->orderBy('sort')->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('location-form')->show();
    }

    public function openEdit(string $id): void
    {
        $location = Location::findOrFail($id);

        $this->editingId = $location->id;
        $this->name = $location->name;
        $this->street = $location->street;
        $this->postal_code = $location->postal_code;
        $this->city = $location->city;
        $this->country = $location->country;
        $this->is_headquarters = $location->is_headquarters;
        $this->phone = (string) $location->phone;
        $this->notes = (string) $location->notes;
        $this->sort = $location->sort;

        Flux::modal('location-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'is_headquarters' => ['boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            if ($validated['is_headquarters']) {
                $query = Location::where('is_headquarters', true);
                if ($this->editingId) {
                    $query->where('id', '!=', $this->editingId);
                }
                $query->update(['is_headquarters' => false]);
            }

            if ($this->editingId) {
                Location::findOrFail($this->editingId)->update($validated);
            } else {
                Location::create($validated);
            }
        });

        Flux::modal('location-form')->close();
        $this->resetForm();
        unset($this->locations);

        Flux::toast(variant: 'success', text: __('Standort gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('location-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Location::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->locations);
            Flux::modal('location-delete')->close();
            Flux::toast(variant: 'success', text: __('Standort gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'street', 'postal_code', 'city', 'is_headquarters', 'phone', 'notes', 'sort']);
        $this->country = 'DE';
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Standorte') }}</flux:heading>
            <flux:subheading>
                {{ __('Adressen aller Betriebsstätten – Pflichtangabe für den Geltungsbereich des Notfallhandbuchs.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Standort') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->locations as $location)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $location->name }}</flux:heading>
                        @if ($location->is_headquarters)
                            <flux:badge color="sky" size="sm" class="mt-1">{{ __('Hauptsitz') }}</flux:badge>
                        @endif
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $location->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $location->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="text-zinc-700 dark:text-zinc-200">
                        {{ $location->street }}<br>
                        {{ $location->postal_code }} {{ $location->city }}<br>
                        {{ $location->country }}
                    </div>
                    @if ($location->phone)
                        <div class="flex items-start gap-2">
                            <flux:icon.phone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <a href="tel:{{ $location->phone }}" class="hover:underline">{{ $location->phone }}</a>
                        </div>
                    @endif
                </div>

                @if ($location->notes)
                    <flux:text class="mt-4 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        {{ $location->notes }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch kein Standort hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="location-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Standort bearbeiten') : __('Neuer Standort') }}
                </flux:heading>
                <flux:subheading>{{ __('Adresse und Erreichbarkeit der Betriebsstätte.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Bezeichnung')" type="text" required placeholder="z. B. Hauptsitz" />
            <flux:input wire:model="street" :label="__('Straße & Nr.')" type="text" required />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="postal_code" :label="__('PLZ')" type="text" required />
                <flux:input wire:model="city" :label="__('Ort')" type="text" required class="sm:col-span-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="country" :label="__('Land (ISO-2)')" type="text" required maxlength="2" />
                <flux:input wire:model="phone" :label="__('Telefon')" type="text" />
            </div>

            <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />

            <flux:checkbox wire:model="is_headquarters" :label="__('Hauptsitz')" />

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="3" />

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

    <flux:modal name="location-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Standort löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
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
