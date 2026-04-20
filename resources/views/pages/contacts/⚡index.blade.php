<?php

use App\Enums\ContactType;
use App\Models\Contact;
use App\Support\IndustryTemplates;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ansprechpartner')] class extends Component {
    public const PLACEHOLDER_NAME = '(zu benennen)';

    public ?string $editingId = null;

    public string $name = '';

    public string $role = '';

    public string $phone = '';

    public string $email = '';

    public string $type = 'intern';

    public bool $is_primary = false;

    public ?string $deletingId = null;

    public string $roleTemplateKey = '';

    public function mount(): void
    {
        $industry = Auth::user()->currentCompany()?->industry;
        $this->roleTemplateKey = IndustryTemplates::defaultFor($industry) ?? '';
    }

    #[Computed]
    public function contacts()
    {
        return Contact::orderByDesc('is_primary')->orderBy('name')->get();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    #[Computed]
    public function hasPrimaryContact(): bool
    {
        return Auth::user()->currentCompany()?->hasPrimaryContact() ?? false;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('contact-form')->show();
    }

    public function openEdit(string $id): void
    {
        $contact = Contact::findOrFail($id);

        $this->editingId = $contact->id;
        $this->name = $contact->name;
        $this->role = (string) $contact->role;
        $this->phone = (string) $contact->phone;
        $this->email = (string) $contact->email;
        $this->type = $contact->type->value;
        $this->is_primary = $contact->is_primary;

        Flux::modal('contact-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', 'in:'.collect(ContactType::cases())->pluck('value')->implode(',')],
            'is_primary' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            if ($validated['is_primary']) {
                Contact::where('is_primary', true)
                    ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                    ->update(['is_primary' => false]);
            }

            if ($this->editingId) {
                Contact::findOrFail($this->editingId)->update($validated);
            } else {
                Contact::create($validated);
            }
        });

        Flux::modal('contact-form')->close();
        $this->resetForm();
        unset($this->contacts, $this->hasPrimaryContact);

        Flux::toast(variant: 'success', text: __('Ansprechpartner gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('contact-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Contact::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->contacts, $this->hasPrimaryContact);
            Flux::modal('contact-delete')->close();
            Flux::toast(variant: 'success', text: __('Ansprechpartner gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'role', 'phone', 'email', 'is_primary']);
        $this->type = ContactType::Internal->value;
    }

    /**
     * @return array<string, array{label: string, count: int}>
     */
    public function roleCatalog(): array
    {
        return IndustryTemplates::contactCatalog();
    }

    public function openRoleTemplate(): void
    {
        Flux::modal('contact-role-template')->show();
    }

    public function loadRoleTemplate(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        $roles = IndustryTemplates::contactRolesFor($this->roleTemplateKey);

        if ($roles === []) {
            $this->addError('roleTemplateKey', __('Bitte eine Branche wählen.'));

            return;
        }

        $existing = Contact::pluck('role')->filter()->map(fn ($r) => mb_strtolower(trim($r)))->all();

        $added = 0;
        $skipped = 0;

        DB::transaction(function () use ($roles, $existing, &$added, &$skipped) {
            foreach ($roles as $entry) {
                if (in_array(mb_strtolower(trim($entry['role'])), $existing, true)) {
                    $skipped++;

                    continue;
                }

                Contact::create([
                    'name' => self::PLACEHOLDER_NAME,
                    'role' => $entry['role'],
                    'type' => $entry['type'],
                    'is_primary' => false,
                ]);
                $added++;
            }
        });

        unset($this->contacts, $this->hasPrimaryContact);
        Flux::modal('contact-role-template')->close();

        $message = __(':count Rollen-Platzhalter hinzugefügt.', ['count' => $added]);
        if ($skipped > 0) {
            $message .= ' '.__(':count bereits vorhanden.', ['count' => $skipped]);
        }

        Flux::toast(variant: 'success', text: $message);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Ansprechpartner') }}</flux:heading>
            <flux:subheading>
                {{ __('Interne und externe Kontakte, die im Notfall erreichbar sein müssen.') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="filled" icon="sparkles" wire:click="openRoleTemplate" :disabled="! $this->hasCompany">
                {{ __('Rollen-Vorlage') }}
            </flux:button>
            <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
                {{ __('Neuer Kontakt') }}
            </flux:button>
        </div>
    </div>

    @if (! $this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an, bevor Sie Ansprechpartner hinzufügen.') }}
        </div>
    @elseif (! $this->hasPrimaryContact)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Es ist noch kein Hauptansprechpartner festgelegt. Legen Sie einen Kontakt an – der erste Kontakt wird automatisch als Hauptansprechpartner markiert.') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($this->contacts as $contact)
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                <div class="flex min-w-0 flex-1 items-start gap-3">
                    <flux:avatar :name="$contact->name" size="sm" class="mt-0.5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ $contact->name }}</span>
                            @if ($contact->is_primary)
                                <flux:badge color="emerald" size="sm">{{ __('Hauptansprechpartner') }}</flux:badge>
                            @endif
                            <flux:badge color="zinc" size="sm">{{ $contact->type->label() }}</flux:badge>
                        </div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $contact->role ?: __('Keine Rolle hinterlegt') }}
                        </flux:text>

                        @if ($contact->phone || $contact->email)
                            <div class="mt-2 flex flex-col gap-1 text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($contact->phone)
                                    <div class="flex items-center gap-2">
                                        <flux:icon.phone class="h-4 w-4 text-zinc-400" />
                                        <span>{{ $contact->phone }}</span>
                                    </div>
                                @endif
                                @if ($contact->email)
                                    <div class="flex items-center gap-2">
                                        <flux:icon.envelope class="h-4 w-4 text-zinc-400" />
                                        <span class="truncate">{{ $contact->email }}</span>
                                    </div>
                                @endif
                            </div>
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
        @empty
            <div class="px-5 py-12 text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Ansprechpartner angelegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="contact-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Ansprechpartner bearbeiten') : __('Neuen Ansprechpartner anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Wer ist im Ernstfall erreichbar und wofür verantwortlich?') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required />
            <flux:input wire:model="role" :label="__('Rolle / Funktion')" type="text" placeholder="z. B. Geschäftsführung" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="phone" :label="__('Telefon')" type="text" />
                <flux:input wire:model="email" :label="__('E-Mail')" type="email" />
            </div>

            <flux:select wire:model="type" :label="__('Typ')" required>
                @foreach (\App\Enums\ContactType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:switch wire:model="is_primary" :label="__('Als Hauptansprechpartner markieren')" />

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

    <flux:modal name="contact-role-template" class="max-w-xl">
        <form wire:submit="loadRoleTemplate" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Branchentypische Rollen einfügen') }}</flux:heading>
                <flux:subheading>
                    {{ __('Für die gewählte Branche werden typische Rollen als Platzhalter angelegt. Name und Kontaktdaten tragen Sie anschließend selbst ein.') }}
                </flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Branche') }}</flux:label>
                <flux:select wire:model.live="roleTemplateKey" required>
                    <flux:select.option value="">{{ __('Bitte wählen') }}</flux:select.option>
                    @foreach ($this->roleCatalog() as $key => $tpl)
                        <flux:select.option value="{{ $key }}">
                            {{ $tpl['label'] }} ({{ $tpl['count'] }} {{ __('Rollen') }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('roleTemplateKey') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            @if ($roleTemplateKey)
                <div class="rounded-md bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                    <div class="mb-1 font-medium">{{ __('Diese Rollen werden als Platzhalter angelegt:') }}</div>
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach (\App\Support\IndustryTemplates::contactRolesFor($roleTemplateKey) as $entry)
                            <li>{{ $entry['role'] }} <span class="text-zinc-400">· {{ \App\Enums\ContactType::from($entry['type'])->label() }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-md bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                {{ __('Bereits vorhandene Rollen (gleiche Bezeichnung) werden übersprungen.') }}
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" icon="sparkles">
                    {{ __('Rollen einfügen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="contact-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Ansprechpartner löschen?') }}</flux:heading>
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
