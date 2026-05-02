<?php

use App\Models\DataProtectionAuthority;
use Database\Seeders\DataProtectionAuthoritiesSeeder;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Datenschutz-Aufsichtsbehörden')] class extends Component {
    public string $key = '';

    public string $name = '';

    public string $short_name = '';

    public string $state = '';

    public string $city = '';

    public int $sort = 0;

    public ?string $deletingId = null;

    /**
     * @return Collection<int, DataProtectionAuthority>
     */
    #[Computed]
    public function authorities(): Collection
    {
        return DataProtectionAuthority::query()
            ->withCount('postalCodeRanges')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->reset(['key', 'name', 'short_name', 'state', 'city', 'sort']);
        $this->sort = ((int) DataProtectionAuthority::max('sort')) + 10;
        Flux::modal('dpa-create')->show();
    }

    public function create(): void
    {
        $validated = $this->validate([
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', 'unique:data_protection_authorities,key'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:128'],
            'sort' => ['integer', 'min:0', 'max:9999'],
        ]);

        $authority = DataProtectionAuthority::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?: null,
            'state' => $validated['state'] ?: null,
            'city' => $validated['city'] ?: null,
            'sort' => $validated['sort'],
        ]);

        Flux::modal('dpa-create')->close();

        $this->redirectRoute('admin.data-protection-authorities.show', ['authority' => $authority->id], navigate: true);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('dpa-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            DataProtectionAuthority::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->authorities);
            Flux::modal('dpa-delete')->close();
            Flux::toast(variant: 'success', text: __('Behörde gelöscht.'));
        }
    }

    /**
     * Setzt sämtliche Behörden-Daten + PLZ-Bereiche auf den Seeder-Stand zurück.
     * Achtung: bestehende PLZ-Bereiche werden komplett ersetzt.
     */
    public function reseed(): void
    {
        (new DataProtectionAuthoritiesSeeder)->run();
        unset($this->authorities);
        Flux::toast(variant: 'success', text: __('Seed neu eingespielt.'));
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Diese Daten gelten plattformweit für alle Mandanten.') }}
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('admin.index')" wire:navigate>
                {{ __('Zurück zur Admin-Übersicht') }}
            </flux:button>
            <flux:heading size="xl" class="mt-2">{{ __('Datenschutz-Aufsichtsbehörden') }}</flux:heading>
            <flux:subheading>
                {{ __('Plattformweite Stammdaten der 16 Landes-DPAs + BfDI inkl. PLZ-Bereichen für Auto-Zuordnung pro Mandant.') }}
            </flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button size="sm" variant="ghost" wire:click="reseed" icon="arrow-path" wire:confirm="{{ __('Wirklich auf Seeder-Defaults zurücksetzen? Bestehende manuelle Änderungen bleiben für Behörden mit übereinstimmendem Schlüssel überschrieben; PLZ-Bereiche werden komplett neu angelegt.') }}">
                {{ __('Seed neu laden') }}
            </flux:button>
            <flux:button size="sm" variant="primary" wire:click="openCreate" icon="plus">
                {{ __('Neue Behörde') }}
            </flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Sort') }}</th>
                    <th class="px-4 py-3">{{ __('Schlüssel') }}</th>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Bundesland') }}</th>
                    <th class="px-4 py-3">{{ __('Sitz') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('PLZ-Bereiche') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->authorities as $authority)
                    <tr wire:key="dpa-{{ $authority->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 tabular-nums text-zinc-500">{{ $authority->sort }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $authority->key }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.data-protection-authorities.show', ['authority' => $authority->id]) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-50">
                                {{ $authority->short_name ?? $authority->name }}
                            </a>
                            @if ($authority->short_name)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $authority->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $authority->state ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $authority->city ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge color="{{ $authority->postal_code_ranges_count > 0 ? 'sky' : 'zinc' }}" size="sm">
                                {{ $authority->postal_code_ranges_count }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil" :href="route('admin.data-protection-authorities.show', ['authority' => $authority->id])" wire:navigate>
                                    {{ __('Bearbeiten') }}
                                </flux:button>
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete('{{ $authority->id }}')" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">
                            {{ __('Noch keine Behörde erfasst — bitte „Seed neu laden" oder „Neue Behörde" verwenden.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="dpa-create" variant="flyout" class="md:max-w-lg">
        <form wire:submit="create" class="space-y-4">
            <flux:heading size="lg">{{ __('Neue Aufsichtsbehörde anlegen') }}</flux:heading>
            <flux:subheading>{{ __('Adresse, Telefon, E-Mail, Website und PLZ-Bereiche werden auf der Detail-Seite gepflegt.') }}</flux:subheading>

            <flux:field>
                <flux:label>{{ __('Schlüssel') }}</flux:label>
                <flux:description>{{ __('Eindeutiger interner Identifier — kleinbuchstaben, Ziffern, Bindestrich. Z. B. „lfdi-bw".') }}</flux:description>
                <flux:input wire:model="key" placeholder="lfdi-bw" required />
            </flux:field>

            <flux:input wire:model="name" :label="__('Voller Name')" placeholder="Landesbeauftragte für …" required />

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="short_name" :label="__('Kurzname')" placeholder="LfDI BW" />
                <flux:input wire:model="state" :label="__('Bundesland')" placeholder="Baden-Württemberg" />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="city" :label="__('Sitz')" placeholder="Stuttgart" />
                <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Anlegen & öffnen') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="dpa-delete" class="md:max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Behörde löschen?') }}</flux:heading>
            <flux:subheading>{{ __('Alle hinterlegten PLZ-Bereiche werden mit gelöscht. Bestehende Mandanten-Eingaben bleiben unangetastet (Behörden-Daten sind dort als Freitext gespeichert).') }}</flux:subheading>
            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Endgültig löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
