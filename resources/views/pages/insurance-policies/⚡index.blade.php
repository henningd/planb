<?php

use App\Enums\InsuranceType;
use App\Models\InsurancePolicy;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Versicherungen')] class extends Component {
    public ?string $editingId = null;

    public string $type = '';

    public string $insurer = '';

    public string $policy_number = '';

    public string $hotline = '';

    public string $email = '';

    public string $reporting_window = '';

    public string $contact_name = '';

    public string $notes = '';

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->type = InsuranceType::Cyber->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, InsurancePolicy>
     */
    #[Computed]
    public function policies(): Collection
    {
        return InsurancePolicy::orderBy('type')->orderBy('insurer')->get();
    }

    public function openCreate(?string $type = null): void
    {
        $this->resetForm();
        if ($type) {
            $this->type = $type;
        }
        Flux::modal('policy-form')->show();
    }

    public function openEdit(string $id): void
    {
        $policy = InsurancePolicy::findOrFail($id);

        $this->editingId = $policy->id;
        $this->type = $policy->type->value;
        $this->insurer = $policy->insurer;
        $this->policy_number = (string) $policy->policy_number;
        $this->hotline = (string) $policy->hotline;
        $this->email = (string) $policy->email;
        $this->reporting_window = (string) $policy->reporting_window;
        $this->contact_name = (string) $policy->contact_name;
        $this->notes = (string) $policy->notes;

        Flux::modal('policy-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'type' => ['required', 'in:'.collect(InsuranceType::cases())->pluck('value')->implode(',')],
            'insurer' => ['required', 'string', 'max:255'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'hotline' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'reporting_window' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->editingId) {
            InsurancePolicy::findOrFail($this->editingId)->update($validated);
        } else {
            InsurancePolicy::create($validated);
        }

        Flux::modal('policy-form')->close();
        $this->resetForm();
        unset($this->policies);

        Flux::toast(variant: 'success', text: __('Versicherung gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('policy-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            InsurancePolicy::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->policies);
            Flux::modal('policy-delete')->close();
            Flux::toast(variant: 'success', text: __('Versicherung gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'insurer', 'policy_number', 'hotline', 'email', 'reporting_window', 'contact_name', 'notes']);
        $this->type = InsuranceType::Cyber->value;
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Versicherungen') }}</flux:heading>
            <flux:subheading>
                {{ __('Police-Nummer, Hotline und Meldefrist – im Schadensfall zählt jede Minute.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Versicherung') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($this->policies as $policy)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:badge :color="$policy->type === \App\Enums\InsuranceType::Cyber ? 'sky' : 'zinc'" size="sm">
                                {{ $policy->type->label() }}
                            </flux:badge>
                        </div>
                        <flux:heading size="base" class="mt-1">{{ $policy->insurer }}</flux:heading>
                        @if ($policy->contact_name)
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $policy->contact_name }}
                            </flux:text>
                        @endif
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $policy->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $policy->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($policy->policy_number)
                        <div class="flex items-center gap-2">
                            <flux:icon.document-text class="h-4 w-4 text-zinc-400" />
                            <span class="text-zinc-600 dark:text-zinc-300">{{ __('Police') }}: {{ $policy->policy_number }}</span>
                        </div>
                    @endif
                    @if ($policy->hotline)
                        <div class="flex items-center gap-2">
                            <flux:icon.phone class="h-4 w-4 text-zinc-400" />
                            <span class="font-medium">{{ $policy->hotline }}</span>
                        </div>
                    @endif
                    @if ($policy->email)
                        <div class="flex items-center gap-2">
                            <flux:icon.envelope class="h-4 w-4 text-zinc-400" />
                            <span>{{ $policy->email }}</span>
                        </div>
                    @endif
                    @if ($policy->reporting_window)
                        <div class="flex items-center gap-2">
                            <flux:icon.clock class="h-4 w-4 text-zinc-400" />
                            <span class="text-zinc-600 dark:text-zinc-300">{{ __('Meldefrist') }}: {{ $policy->reporting_window }}</span>
                        </div>
                    @endif
                </div>

                @if ($policy->notes)
                    <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $policy->notes }}</flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Versicherung hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="policy-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Versicherung bearbeiten') : __('Neue Versicherung') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Diese Daten müssen im Schadensfall griffbereit sein.') }}
                </flux:subheading>
            </div>

            <flux:select wire:model="type" :label="__('Art')" required>
                @foreach (\App\Enums\InsuranceType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="insurer" :label="__('Versicherer')" type="text" required placeholder="z. B. Musterversicherung AG" />
            <flux:input wire:model="policy_number" :label="__('Police-Nummer')" type="text" />
            <flux:input wire:model="contact_name" :label="__('Ansprechpartner beim Versicherer')" type="text" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="hotline" :label="__('Notfall-Hotline')" type="text" placeholder="z. B. 0800 1234567" />
                <flux:input wire:model="reporting_window" :label="__('Meldefrist')" type="text" placeholder="z. B. 24h, unverzüglich" />
            </div>

            <flux:input wire:model="email" :label="__('E-Mail')" type="email" />

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

    <flux:modal name="policy-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Versicherung löschen?') }}</flux:heading>
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
