<?php

use App\Enums\AuthorityContactType;
use App\Models\AuthorityContact;
use App\Models\CommunicationTemplate;
use App\Models\Role;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Behörden & Meldestellen')] class extends Component {
    public ?string $editingId = null;

    public ?string $deletingId = null;

    public string $filterType = '';

    public string $type = 'other';

    public string $name = '';

    public string $occasion = '';

    public string $deadline = '';

    public string $phone = '';

    public string $email = '';

    public string $contact_way = '';

    public string $address = '';

    public string $contact_name = '';

    public string $responsible_role_id = '';

    public string $communication_template_id = '';

    public string $notes = '';

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, AuthorityContact>
     */
    #[Computed]
    public function contacts(): Collection
    {
        return AuthorityContact::with(['responsibleRole', 'communicationTemplate'])
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->orderBy('type')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    /**
     * @return Collection<int, CommunicationTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return CommunicationTemplate::orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('contact-form')->show();
    }

    public function openEdit(string $id): void
    {
        $contact = AuthorityContact::findOrFail($id);

        $this->editingId = $contact->id;
        $this->type = $contact->type->value;
        $this->name = $contact->name;
        $this->occasion = (string) $contact->occasion;
        $this->deadline = (string) $contact->deadline;
        $this->phone = (string) $contact->phone;
        $this->email = (string) $contact->email;
        $this->contact_way = (string) $contact->contact_way;
        $this->address = (string) $contact->address;
        $this->contact_name = (string) $contact->contact_name;
        $this->responsible_role_id = (string) ($contact->responsible_role_id ?? '');
        $this->communication_template_id = (string) ($contact->communication_template_id ?? '');
        $this->notes = (string) $contact->notes;

        Flux::modal('contact-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'type' => ['required', Rule::enum(AuthorityContactType::class)],
            'name' => ['required', 'string', 'max:255'],
            'occasion' => ['nullable', 'string', 'max:2000'],
            'deadline' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_way' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'responsible_role_id' => ['nullable', 'string', Rule::exists('roles', 'id')],
            'communication_template_id' => ['nullable', 'string', Rule::exists('communication_templates', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = [
            ...$validated,
            'responsible_role_id' => $this->responsible_role_id ?: null,
            'communication_template_id' => $this->communication_template_id ?: null,
            'company_id' => Auth::user()->currentCompany()?->id,
        ];

        if ($this->editingId) {
            AuthorityContact::findOrFail($this->editingId)->update($payload);
            Flux::toast(text: __('Kontakt aktualisiert.'));
        } else {
            AuthorityContact::create($payload);
            Flux::toast(text: __('Kontakt angelegt.'));
        }

        $this->resetForm();
        Flux::modal('contact-form')->close();
        unset($this->contacts);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('contact-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            AuthorityContact::findOrFail($this->deletingId)->delete();
            Flux::toast(text: __('Kontakt gelöscht.'));
        }

        $this->deletingId = null;
        Flux::modal('contact-delete')->close();
        unset($this->contacts);
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'type', 'name', 'occasion', 'deadline', 'phone', 'email',
            'contact_way', 'address', 'contact_name', 'responsible_role_id',
            'communication_template_id', 'notes',
        ]);
        $this->type = 'other';
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Behörden, Meldestellen und externe Einrichtungen') }}</flux:heading>
            <flux:subheading>
                {{ __('Wer ist im Ernst-/Meldefall zu kontaktieren — mit Anlass, Frist, Kontaktweg, zuständiger Rolle und passender Kommunikationsvorlage.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Kontakt') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->contacts->isNotEmpty())
        <div class="mb-4 max-w-xs">
            <flux:select wire:model.live="filterType" size="sm">
                <flux:select.option value="">{{ __('Alle Arten') }}</flux:select.option>
                @foreach (AuthorityContactType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->contacts as $contact)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $contact->name }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$contact->type->color()" size="sm">{{ $contact->type->label() }}</flux:badge>
                            @if ($contact->deadline)
                                <flux:badge color="amber" size="sm" icon="clock">{{ $contact->deadline }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $contact->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $contact->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($contact->occasion)
                        <div class="flex items-start gap-2">
                            <flux:icon.megaphone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Anlass') }}</div>
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $contact->occasion }}</span>
                            </div>
                        </div>
                    @endif
                    @if ($contact->phone)
                        <div class="flex items-start gap-2">
                            <flux:icon.phone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Telefon') }}</div>
                                <a href="tel:{{ $contact->phone }}" class="font-medium hover:underline">{{ $contact->phone }}</a>
                            </div>
                        </div>
                    @endif
                    @if ($contact->email)
                        <div class="flex items-start gap-2">
                            <flux:icon.envelope class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('E-Mail') }}</div>
                                <a href="mailto:{{ $contact->email }}" class="font-medium hover:underline">{{ $contact->email }}</a>
                            </div>
                        </div>
                    @endif
                    @if ($contact->contact_way)
                        <div class="flex items-start gap-2">
                            <flux:icon.globe-alt class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Kontaktweg') }}</div>
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $contact->contact_way }}</span>
                            </div>
                        </div>
                    @endif
                    @if ($contact->contact_name)
                        <div class="flex items-start gap-2">
                            <flux:icon.user class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Ansprechpartner') }}</div>
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $contact->contact_name }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-1 pt-1">
                        @if ($contact->responsibleRole)
                            <flux:badge color="zinc" size="sm" icon="identification">{{ $contact->responsibleRole->name }}</flux:badge>
                        @endif
                        @if ($contact->communicationTemplate)
                            <flux:badge color="indigo" size="sm" icon="document-duplicate">{{ $contact->communicationTemplate->name }}</flux:badge>
                        @endif
                    </div>
                </div>

                @if ($contact->notes)
                    <flux:text class="mt-4 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        {{ $contact->notes }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch kein Behörden-/Meldestellen-Kontakt hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="contact-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Kontakt bearbeiten') : __('Neuer Kontakt') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Behörde, Meldestelle oder externe Einrichtung — im Ernst-/Meldefall griffbereit.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="type" :label="__('Art der Stelle')" required>
                    @foreach (AuthorityContactType::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="name" :label="__('Bezeichnung der Stelle')" type="text" required placeholder="z. B. Landesdatenschutzbehörde Bayern" />
            </div>

            <flux:textarea wire:model="occasion" :label="__('Anlass / Meldepflicht')" rows="2" placeholder="z. B. Meldepflichtige Datenschutzverletzung (Art. 33 DSGVO)" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="deadline" :label="__('Frist')" type="text" placeholder="z. B. binnen 72 Stunden" />
                <flux:input wire:model="contact_way" :label="__('Kontaktweg')" type="text" placeholder="z. B. Online-Meldeportal, 24/7-Hotline" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="phone" :label="__('Telefon')" type="text" placeholder="z. B. 0800 1234567" />
                <flux:input wire:model="email" :label="__('E-Mail')" type="email" />
            </div>

            <flux:input wire:model="address" :label="__('Anschrift')" type="text" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="contact_name" :label="__('Ansprechpartner')" type="text" />
                <flux:select wire:model="responsible_role_id" :label="__('Zuständige interne Rolle')">
                    <flux:select.option value="">{{ __('— keine —') }}</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:select wire:model="communication_template_id" :label="__('Passende Kommunikationsvorlage')">
                <flux:select.option value="">{{ __('— keine —') }}</flux:select.option>
                @foreach ($this->templates as $template)
                    <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="contact-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Kontakt löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Dieser Vorgang kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
