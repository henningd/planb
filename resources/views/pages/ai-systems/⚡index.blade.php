<?php

use App\Enums\AiRiskClass;
use App\Enums\AiSystemRole;
use App\Models\AiSystem;
use App\Models\Role;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('KI-Governance')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $purpose = '';

    public string $provider_name = '';

    public string $role = 'deployer';

    public string $risk_class = 'unclassified';

    public string $annex_category = '';

    public string $data_sources = '';

    public string $human_oversight = '';

    public string $responsible_role_id = '';

    public string $conformity_status = '';

    public string $eu_db_registration = '';

    public string $transparency_measures = '';

    public ?string $last_reviewed_at = null;

    public ?string $next_review_at = null;

    public string $notes = '';

    public string $filterRiskClass = '';

    public ?string $deletingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, AiSystem>
     */
    #[Computed]
    public function systems(): Collection
    {
        return AiSystem::query()
            ->with('responsibleRole')
            ->when($this->filterRiskClass !== '', fn ($q) => $q->where('risk_class', $this->filterRiskClass))
            ->orderByRaw("CASE risk_class WHEN 'prohibited' THEN 0 WHEN 'high' THEN 1 WHEN 'limited' THEN 2 WHEN 'minimal' THEN 3 ELSE 4 END")
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

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('ai-system-form')->show();
    }

    public function openEdit(string $id): void
    {
        $system = AiSystem::findOrFail($id);

        $this->editingId = $system->id;
        $this->name = (string) $system->name;
        $this->purpose = (string) $system->purpose;
        $this->provider_name = (string) $system->provider_name;
        $this->role = $system->role->value;
        $this->risk_class = $system->risk_class->value;
        $this->annex_category = (string) $system->annex_category;
        $this->data_sources = (string) $system->data_sources;
        $this->human_oversight = (string) $system->human_oversight;
        $this->responsible_role_id = (string) ($system->responsible_role_id ?? '');
        $this->conformity_status = (string) $system->conformity_status;
        $this->eu_db_registration = (string) $system->eu_db_registration;
        $this->transparency_measures = (string) $system->transparency_measures;
        $this->last_reviewed_at = $system->last_reviewed_at?->toDateString();
        $this->next_review_at = $system->next_review_at?->toDateString();
        $this->notes = (string) $system->notes;

        Flux::modal('ai-system-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', new Enum(AiSystemRole::class)],
            'risk_class' => ['required', new Enum(AiRiskClass::class)],
            'annex_category' => ['nullable', 'string', 'max:255'],
            'data_sources' => ['nullable', 'string', 'max:5000'],
            'human_oversight' => ['nullable', 'string', 'max:5000'],
            'responsible_role_id' => ['nullable', 'string', Rule::exists('roles', 'id')],
            'conformity_status' => ['nullable', 'string', 'max:255'],
            'eu_db_registration' => ['nullable', 'string', 'max:255'],
            'transparency_measures' => ['nullable', 'string', 'max:5000'],
            'last_reviewed_at' => ['nullable', 'date'],
            'next_review_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        if ($this->editingId) {
            AiSystem::findOrFail($this->editingId)->update($payload);
        } else {
            AiSystem::create($payload);
        }

        Flux::modal('ai-system-form')->close();
        $this->resetForm();
        unset($this->systems);

        Flux::toast(variant: 'success', text: __('KI-System gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('ai-system-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            AiSystem::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->systems);
            Flux::modal('ai-system-delete')->close();
            Flux::toast(variant: 'success', text: __('KI-System gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'purpose', 'provider_name', 'annex_category', 'data_sources',
            'human_oversight', 'responsible_role_id', 'conformity_status', 'eu_db_registration',
            'transparency_measures', 'last_reviewed_at', 'next_review_at', 'notes',
        ]);
        $this->role = 'deployer';
        $this->risk_class = 'unclassified';
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('KI-Governance (EU-KI-Verordnung)') }}</flux:heading>
            <flux:subheading>
                {{ __('Register der eingesetzten KI-Systeme nach Verordnung (EU) 2024/1689: Rolle, Risikoklasse, menschliche Aufsicht, Konformität und Prüfung — Grundlage für Klassifizierung, Pflichten-Nachweis und Protokollierung.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neues KI-System') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="mb-4">
        <flux:select wire:model.live="filterRiskClass" class="max-w-xs">
            <flux:select.option value="">{{ __('Alle Risikoklassen') }}</flux:select.option>
            @foreach (App\Enums\AiRiskClass::options() as $option)
                <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->systems as $system)
            <div wire:key="ai-system-{{ $system->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $system->name }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$system->risk_class->color()" size="sm">{{ $system->risk_class->label() }}</flux:badge>
                            <flux:badge color="zinc" size="sm">{{ $system->role->label() }}</flux:badge>
                            @if ($system->isReviewOverdue())
                                <flux:badge color="red" size="sm" icon="arrow-path">{{ __('Prüfung überfällig') }}</flux:badge>
                            @elseif ($system->next_review_at)
                                <flux:badge color="zinc" size="sm" icon="arrow-path">{{ __('Prüfung') }}: {{ $system->next_review_at->format('d.m.Y') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $system->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $system->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($system->purpose)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($system->purpose, 140) }}</flux:text>
                    @endif
                    @if ($system->provider_name)
                        <div class="flex items-center gap-2">
                            <flux:icon.building-office class="h-4 w-4 text-zinc-400" />
                            <span>{{ __('Anbieter') }}: {{ $system->provider_name }}</span>
                        </div>
                    @endif
                    @if ($system->responsibleRole)
                        <div class="flex items-center gap-2">
                            <flux:icon.identification class="h-4 w-4 text-zinc-400" />
                            <span>{{ __('Verantwortlich') }}: {{ $system->responsibleRole->name }}</span>
                        </div>
                    @endif
                    <div class="rounded-md bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <span class="font-medium">{{ __('Pflichten') }}:</span> {{ $system->risk_class->obligationHint() }}
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch kein KI-System erfasst.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="ai-system-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('KI-System bearbeiten') : __('Neues KI-System') }}
                </flux:heading>
                <flux:subheading>{{ __('Welches KI-System, in welcher Rolle, mit welchem Risiko?') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Bezeichnung')" type="text" required placeholder="z. B. Bewerber-Vorauswahl, Chatbot Kundenservice" />
            <flux:textarea wire:model="purpose" :label="__('Zweck / Einsatzkontext')" rows="2" placeholder="Wofür wird das System eingesetzt, welche Personen/Entscheidungen sind betroffen?" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="provider_name" :label="__('Anbieter / Hersteller')" type="text" placeholder="z. B. OpenAI, internes Team" />
                <flux:select wire:model="role" :label="__('Unsere Rolle')" required>
                    @foreach (App\Enums\AiSystemRole::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="risk_class" :label="__('Risikoklasse')" required>
                    @foreach (App\Enums\AiRiskClass::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="annex_category" :label="__('Annex-III-Kategorie (falls Hochrisiko)')" type="text" placeholder="z. B. Beschäftigung / Personalauswahl" />
            </div>

            <flux:textarea wire:model="human_oversight" :label="__('Menschliche Aufsicht')" rows="2" placeholder="Wie wird das System durch Menschen überwacht/übersteuert? (Art. 14)" />
            <flux:textarea wire:model="data_sources" :label="__('Datenquellen / Trainingsdaten')" rows="2" />
            <flux:textarea wire:model="transparency_measures" :label="__('Transparenzmaßnahmen')" rows="2" placeholder="z. B. Kennzeichnung als KI, Hinweis auf KI-generierte Inhalte (Art. 50)" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="conformity_status" :label="__('Konformitätsstatus')" type="text" placeholder="z. B. CE, Konformitätserklärung liegt vor" />
                <flux:input wire:model="eu_db_registration" :label="__('EU-Datenbank-Registrierung')" type="text" placeholder="Registrierungsnummer (Hochrisiko)" />
            </div>

            <flux:select wire:model="responsible_role_id" :label="__('Zuständige interne Rolle')">
                <flux:select.option value="">{{ __('— keine —') }}</flux:select.option>
                @foreach ($this->roles as $role)
                    <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="last_reviewed_at" :label="__('Letzte Prüfung')" type="date" />
                <flux:input wire:model="next_review_at" :label="__('Nächste Prüfung')" type="date" />
            </div>

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />

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

    <flux:modal name="ai-system-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('KI-System löschen?') }}</flux:heading>
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
