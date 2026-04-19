<?php

use App\Enums\Industry;
use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Kunden')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $industry = '';

    public ?int $employee_count = null;

    public ?int $locations_count = null;

    public ?string $deletingId = null;

    #[Computed]
    public function companies()
    {
        return Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->with(['team', 'contacts', 'systems'])
            ->orderBy('name')
            ->get();
    }

    public function openEdit(string $id): void
    {
        $company = Company::withoutGlobalScope(CurrentCompanyScope::class)->findOrFail($id);

        $this->editingId = $company->id;
        $this->name = $company->name;
        $this->industry = $company->industry->value;
        $this->employee_count = $company->employee_count;
        $this->locations_count = $company->locations_count;

        Flux::modal('admin-company-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'in:'.collect(Industry::cases())->pluck('value')->implode(',')],
            'employee_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'locations_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $company = Company::withoutGlobalScope(CurrentCompanyScope::class)->findOrFail($this->editingId);
        $company->update($validated);

        Flux::modal('admin-company-form')->close();
        unset($this->companies);
        Flux::toast(variant: 'success', text: __('Kunde gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('admin-company-delete')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $company = Company::withoutGlobalScope(CurrentCompanyScope::class)->findOrFail($this->deletingId);
        $company->delete();

        $this->deletingId = null;
        unset($this->companies);
        Flux::modal('admin-company-delete')->close();
        Flux::toast(variant: 'success', text: __('Kunde archiviert.'));
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Sie bearbeiten hier Daten fremder Mandanten.') }}
    </div>

    <div class="mb-6">
        <flux:heading size="xl">{{ __('Kunden') }}</flux:heading>
        <flux:subheading>{{ __('Alle im System angelegten Firmen, über alle Teams hinweg.') }}</flux:subheading>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                <tr>
                    <th class="px-5 py-3">{{ __('Firma') }}</th>
                    <th class="px-5 py-3">{{ __('Branche') }}</th>
                    <th class="px-5 py-3">{{ __('Team') }}</th>
                    <th class="px-5 py-3 text-right">{{ __('Kontakte') }}</th>
                    <th class="px-5 py-3 text-right">{{ __('Systeme') }}</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->companies as $company)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $company->name }}</div>
                            @if ($company->employee_count)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $company->employee_count }} MA · {{ $company->locations_count ?? 1 }} Standort(e)</div>
                            @endif
                        </td>
                        <td class="px-5 py-3">{{ $company->industry->label() }}</td>
                        <td class="px-5 py-3">
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $company->team->name ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $company->contacts->count() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $company->systems->count() }}</td>
                        <td class="px-5 py-3 text-right">
                            <flux:dropdown align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="openEdit('{{ $company->id }}')">
                                        {{ __('Bearbeiten') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $company->id }}')">
                                        {{ __('Löschen') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Kunden angelegt.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="admin-company-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Kunde bearbeiten') }}</flux:heading>
            </div>

            <flux:input wire:model="name" :label="__('Firmenname')" required />

            <flux:select wire:model="industry" :label="__('Branche')" required>
                @foreach (Industry::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="employee_count" :label="__('Mitarbeitende')" type="number" min="0" />
                <flux:input wire:model="locations_count" :label="__('Standorte')" type="number" min="0" />
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="admin-company-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Kunde löschen?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Die Firma wird soft-gelöscht. Alle zugehörigen Kontakte, Systeme und Szenarien verbleiben in der Datenbank, sind aber nicht mehr über die normale Oberfläche erreichbar.') }}
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
