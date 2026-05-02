<?php

use App\Models\DataProtectionAuthority;
use App\Models\DataProtectionAuthorityPostalCodeRange;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Behörde bearbeiten')] class extends Component {
    public DataProtectionAuthority $authority;

    public string $key = '';

    public string $name = '';

    public string $short_name = '';

    public string $state = '';

    public string $street = '';

    public string $postal_code = '';

    public string $city = '';

    public string $phone = '';

    public string $email = '';

    public string $website = '';

    public string $breach_notification_url = '';

    public string $notes = '';

    public int $sort = 0;

    public ?string $editingRangeId = null;

    public string $rangeFrom = '';

    public string $rangeTo = '';

    public string $rangeNotes = '';

    public ?string $deletingRangeId = null;

    public function mount(DataProtectionAuthority $authority): void
    {
        $this->authority = $authority->load('postalCodeRanges');
        $this->key = $authority->key;
        $this->name = $authority->name;
        $this->short_name = (string) $authority->short_name;
        $this->state = (string) $authority->state;
        $this->street = (string) $authority->street;
        $this->postal_code = (string) $authority->postal_code;
        $this->city = (string) $authority->city;
        $this->phone = (string) $authority->phone;
        $this->email = (string) $authority->email;
        $this->website = (string) $authority->website;
        $this->breach_notification_url = (string) $authority->breach_notification_url;
        $this->notes = (string) $authority->notes;
        $this->sort = (int) $authority->sort;
    }

    public function saveMeta(): void
    {
        $validated = $this->validate([
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', 'unique:data_protection_authorities,key,'.$this->authority->id],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:128'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'breach_notification_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0', 'max:9999'],
        ]);

        $this->authority->update(collect($validated)
            ->map(fn ($v) => $v === '' ? null : $v)
            ->toArray());

        Flux::toast(variant: 'success', text: __('Stammdaten gespeichert.'));
    }

    public function openAddRange(): void
    {
        $this->resetRangeForm();
        Flux::modal('dpa-range-form')->show();
    }

    public function openEditRange(string $id): void
    {
        $range = $this->authority->postalCodeRanges()->findOrFail($id);
        $this->editingRangeId = $range->id;
        $this->rangeFrom = $range->plz_from;
        $this->rangeTo = $range->plz_to;
        $this->rangeNotes = (string) $range->notes;
        Flux::modal('dpa-range-form')->show();
    }

    public function saveRange(): void
    {
        $validated = $this->validate([
            'rangeFrom' => ['required', 'string', 'regex:/^\d{5}$/'],
            'rangeTo' => ['required', 'string', 'regex:/^\d{5}$/', 'gte:rangeFrom'],
            'rangeNotes' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'plz_from' => $validated['rangeFrom'],
            'plz_to' => $validated['rangeTo'],
            'notes' => $validated['rangeNotes'] ?: null,
        ];

        if ($this->editingRangeId) {
            $this->authority->postalCodeRanges()->findOrFail($this->editingRangeId)->update($payload);
        } else {
            $this->authority->postalCodeRanges()->create($payload);
        }

        Flux::modal('dpa-range-form')->close();
        $this->resetRangeForm();
        $this->authority->load('postalCodeRanges');

        Flux::toast(variant: 'success', text: __('PLZ-Bereich gespeichert.'));
    }

    public function confirmDeleteRange(string $id): void
    {
        $this->deletingRangeId = $id;
        Flux::modal('dpa-range-delete')->show();
    }

    public function deleteRange(): void
    {
        if ($this->deletingRangeId) {
            $this->authority->postalCodeRanges()->findOrFail($this->deletingRangeId)->delete();
            $this->deletingRangeId = null;
            $this->authority->load('postalCodeRanges');
            Flux::modal('dpa-range-delete')->close();
            Flux::toast(variant: 'success', text: __('PLZ-Bereich gelöscht.'));
        }
    }

    private function resetRangeForm(): void
    {
        $this->reset(['editingRangeId', 'rangeFrom', 'rangeTo', 'rangeNotes']);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Diese Daten gelten plattformweit für alle Mandanten.') }}
    </div>

    <div class="mb-6">
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('admin.data-protection-authorities.index')" wire:navigate>
            {{ __('Zurück zur Übersicht') }}
        </flux:button>
        <flux:heading size="xl" class="mt-2">{{ $authority->short_name ?? $authority->name }}</flux:heading>
        <flux:subheading>{{ $authority->name }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <form wire:submit="saveMeta" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <flux:heading size="lg">{{ __('Stammdaten') }}</flux:heading>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="key" :label="__('Schlüssel')" required />
                    <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />
                </div>

                <flux:input wire:model="name" :label="__('Voller Name')" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="short_name" :label="__('Kurzname')" />
                    <flux:input wire:model="state" :label="__('Bundesland')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="street" :label="__('Straße')" />
                    <flux:input wire:model="postal_code" :label="__('PLZ')" />
                    <flux:input wire:model="city" :label="__('Stadt')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="phone" :label="__('Telefon')" />
                    <flux:input wire:model="email" :label="__('E-Mail')" type="email" />
                </div>

                <flux:input wire:model="website" :label="__('Website')" placeholder="https://…" />
                <flux:input wire:model="breach_notification_url" :label="__('Datenpannen-Meldeformular (URL)')" placeholder="https://…" />

                <flux:textarea wire:model="notes" :label="__('Hinweise')" rows="3" />

                <div class="flex justify-end border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:button variant="primary" type="submit">{{ __('Speichern') }}</flux:button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 p-4 dark:border-zinc-800">
                    <div>
                        <flux:heading size="base">{{ __('PLZ-Bereiche') }}</flux:heading>
                        <flux:text class="text-xs text-zinc-500">{{ $authority->postalCodeRanges->count() }} {{ __('Eintrag/Einträge') }}</flux:text>
                    </div>
                    <flux:button size="xs" variant="primary" icon="plus" wire:click="openAddRange">
                        {{ __('Hinzufügen') }}
                    </flux:button>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($authority->postalCodeRanges->sortBy('plz_from') as $range)
                        <div class="flex items-start justify-between gap-2 p-3" wire:key="range-{{ $range->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-mono text-sm tabular-nums text-zinc-900 dark:text-zinc-50">
                                    {{ $range->plz_from }}–{{ $range->plz_to }}
                                </div>
                                @if ($range->notes)
                                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $range->notes }}</div>
                                @endif
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="openEditRange('{{ $range->id }}')" />
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDeleteRange('{{ $range->id }}')" />
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-sm text-zinc-500">
                            {{ __('Noch keine Bereiche — diese Behörde wird PLZ-basiert nicht vorgeschlagen.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                {{ __('Hinweis: PLZ-Bereiche dürfen sich nicht überlappen — der Resolver gibt sonst die zuerst gefundene Behörde zurück (sortiert nach plz_from).') }}
            </div>
        </div>
    </div>

    <flux:modal name="dpa-range-form" class="md:max-w-md">
        <form wire:submit="saveRange" class="space-y-4">
            <flux:heading size="lg">{{ $editingRangeId ? __('PLZ-Bereich bearbeiten') : __('PLZ-Bereich hinzufügen') }}</flux:heading>

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Von (5 Ziffern)') }}</flux:label>
                    <flux:input wire:model="rangeFrom" placeholder="70001" maxlength="5" required />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Bis (5 Ziffern)') }}</flux:label>
                    <flux:input wire:model="rangeTo" placeholder="79999" maxlength="5" required />
                </flux:field>
            </div>

            <flux:input wire:model="rangeNotes" :label="__('Anmerkung (optional)')" placeholder="z. B. Stuttgart, Karlsruhe" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="dpa-range-delete" class="md:max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('PLZ-Bereich löschen?') }}</flux:heading>
            <flux:subheading>{{ __('Mandanten, deren Hauptstandort in diesem PLZ-Bereich liegt, werden anschließend keinen Vorschlag mehr für diese Behörde erhalten.') }}</flux:subheading>
            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteRange">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
