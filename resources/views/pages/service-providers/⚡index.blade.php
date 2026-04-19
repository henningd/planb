<?php

use App\Models\ServiceProvider;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dienstleister')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $contact_name = '';

    public string $hotline = '';

    public string $email = '';

    public string $contract_number = '';

    public string $sla = '';

    public string $notes = '';

    public ?int $deletingId = null;

    #[Computed]
    public function providers()
    {
        return ServiceProvider::with('systems')->orderBy('name')->get();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('provider-form')->show();
    }

    public function openEdit(int $id): void
    {
        $provider = ServiceProvider::findOrFail($id);

        $this->editingId = $provider->id;
        $this->name = $provider->name;
        $this->contact_name = (string) $provider->contact_name;
        $this->hotline = (string) $provider->hotline;
        $this->email = (string) $provider->email;
        $this->contract_number = (string) $provider->contract_number;
        $this->sla = (string) $provider->sla;
        $this->notes = (string) $provider->notes;

        Flux::modal('provider-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'hotline' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'sla' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->editingId) {
            ServiceProvider::findOrFail($this->editingId)->update($validated);
        } else {
            ServiceProvider::create($validated);
        }

        Flux::modal('provider-form')->close();
        $this->resetForm();
        unset($this->providers);

        Flux::toast(variant: 'success', text: __('Dienstleister gespeichert.'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('provider-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            ServiceProvider::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->providers);
            Flux::modal('provider-delete')->close();
            Flux::toast(variant: 'success', text: __('Dienstleister gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'contact_name', 'hotline', 'email', 'contract_number', 'sla', 'notes']);
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Externe Dienstleister') }}</flux:heading>
            <flux:subheading>
                {{ __('IT-Dienstleister, Support-Hotlines und externe Partner. Im Ernstfall zählt, wen Sie für welches System anrufen.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Dienstleister') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($this->providers as $provider)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <flux:heading size="base">{{ $provider->name }}</flux:heading>
                        @if ($provider->contact_name)
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $provider->contact_name }}
                            </flux:text>
                        @endif
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit({{ $provider->id }})">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $provider->id }})">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($provider->hotline)
                        <div class="flex items-center gap-2">
                            <flux:icon.phone class="h-4 w-4 text-zinc-400" />
                            <span class="font-medium">{{ $provider->hotline }}</span>
                            @if ($provider->sla)<flux:badge color="zinc" size="sm">{{ $provider->sla }}</flux:badge>@endif
                        </div>
                    @endif
                    @if ($provider->email)
                        <div class="flex items-center gap-2">
                            <flux:icon.envelope class="h-4 w-4 text-zinc-400" />
                            <span>{{ $provider->email }}</span>
                        </div>
                    @endif
                    @if ($provider->contract_number)
                        <div class="flex items-center gap-2">
                            <flux:icon.document-text class="h-4 w-4 text-zinc-400" />
                            <span class="text-zinc-600 dark:text-zinc-300">{{ __('Vertrag') }}: {{ $provider->contract_number }}</span>
                        </div>
                    @endif
                </div>

                @if ($provider->systems->isNotEmpty())
                    <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Zugeordnete Systeme') }}</flux:text>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($provider->systems as $system)
                                <flux:badge color="zinc" size="sm">{{ $system->name }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($provider->notes)
                    <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $provider->notes }}</flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Dienstleister angelegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="provider-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Dienstleister bearbeiten') : __('Neuen Dienstleister anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Diese Daten müssen im Ernstfall griffbereit sein.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Firma')" type="text" required placeholder="z. B. Musterdienstleister GmbH" />
            <flux:input wire:model="contact_name" :label="__('Ansprechpartner')" type="text" placeholder="z. B. Frau Müller" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="hotline" :label="__('Notfall-Hotline')" type="text" placeholder="z. B. 0800 1234567" />
                <flux:input wire:model="sla" :label="__('Erreichbarkeit / SLA')" type="text" placeholder="z. B. 24/7" />
            </div>

            <flux:input wire:model="email" :label="__('E-Mail')" type="email" />
            <flux:input wire:model="contract_number" :label="__('Vertrags- / Kundennummer')" type="text" />

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="3" />

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

    <flux:modal name="provider-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Dienstleister löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Zuordnungen zu Systemen werden ebenfalls entfernt.') }}</flux:subheading>
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
