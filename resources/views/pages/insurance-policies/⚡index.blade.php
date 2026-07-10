<?php

use App\Enums\InsuranceType;
use App\Models\InsurancePolicy;
use App\Models\Role;
use App\Models\Scenario;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Versicherungen')] class extends Component {
    public ?string $editingId = null;

    public string $type = '';

    public string $insurer = '';

    public string $policy_number = '';

    public ?string $valid_from = null;

    public ?string $valid_until = null;

    public string $hotline = '';

    public string $email = '';

    public string $reporting_window = '';

    public string $required_documents = '';

    public string $deductible = '';

    public string $coverage_amount = '';

    public string $contact_name = '';

    public string $responsible_role_id = '';

    public bool $approval_required = false;

    public string $approval_note = '';

    public ?string $claims_process_tested_at = null;

    public ?string $last_reviewed_at = null;

    public ?string $next_review_at = null;

    public string $notes = '';

    /** @var list<string> */
    public array $selectedScenarios = [];

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
        return InsurancePolicy::with(['responsibleRole', 'scenarios'])
            ->orderBy('type')
            ->orderBy('insurer')
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
     * @return Collection<int, Scenario>
     */
    #[Computed]
    public function scenarios(): Collection
    {
        return Scenario::orderBy('name')->get();
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
        $policy = InsurancePolicy::with('scenarios')->findOrFail($id);

        $this->editingId = $policy->id;
        $this->type = $policy->type->value;
        $this->insurer = $policy->insurer;
        $this->policy_number = (string) $policy->policy_number;
        $this->valid_from = $policy->valid_from?->toDateString();
        $this->valid_until = $policy->valid_until?->toDateString();
        $this->hotline = (string) $policy->hotline;
        $this->email = (string) $policy->email;
        $this->reporting_window = (string) $policy->reporting_window;
        $this->required_documents = (string) $policy->required_documents;
        $this->deductible = (string) $policy->deductible;
        $this->coverage_amount = (string) $policy->coverage_amount;
        $this->contact_name = (string) $policy->contact_name;
        $this->responsible_role_id = (string) ($policy->responsible_role_id ?? '');
        $this->approval_required = (bool) $policy->approval_required;
        $this->approval_note = (string) $policy->approval_note;
        $this->claims_process_tested_at = $policy->claims_process_tested_at?->toDateString();
        $this->last_reviewed_at = $policy->last_reviewed_at?->toDateString();
        $this->next_review_at = $policy->next_review_at?->toDateString();
        $this->notes = (string) $policy->notes;
        $this->selectedScenarios = $policy->scenarios->pluck('id')->all();

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
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'hotline' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'reporting_window' => ['nullable', 'string', 'max:255'],
            'required_documents' => ['nullable', 'string', 'max:2000'],
            'deductible' => ['nullable', 'string', 'max:100'],
            'coverage_amount' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'responsible_role_id' => ['nullable', 'string', Rule::exists('roles', 'id')],
            'approval_required' => ['boolean'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
            'claims_process_tested_at' => ['nullable', 'date'],
            'last_reviewed_at' => ['nullable', 'date'],
            'next_review_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'selectedScenarios' => ['array'],
            'selectedScenarios.*' => ['string', Rule::exists('scenarios', 'id')],
        ]);

        $scenarioIds = $validated['selectedScenarios'] ?? [];
        unset($validated['selectedScenarios']);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();
        $payload['approval_required'] = $this->approval_required;

        if ($this->editingId) {
            $policy = InsurancePolicy::findOrFail($this->editingId);
            $policy->update($payload);
        } else {
            $policy = InsurancePolicy::create($payload);
        }

        $policy->scenarios()->sync($scenarioIds);

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
        $this->reset([
            'editingId', 'insurer', 'policy_number', 'valid_from', 'valid_until', 'hotline', 'email',
            'reporting_window', 'required_documents', 'deductible', 'coverage_amount', 'contact_name',
            'responsible_role_id', 'approval_required', 'approval_note', 'claims_process_tested_at',
            'last_reviewed_at', 'next_review_at', 'notes', 'selectedScenarios',
        ]);
        $this->type = InsuranceType::Cyber->value;
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Versicherungen') }}</flux:heading>
            <flux:subheading>
                {{ __('Policen, Nachweise und Schadenmeldung: Deckung, Meldefristen, Unterlagen und Freigaben — im Schadensfall zählt jede Minute.') }}
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

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->policies as $policy)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">
                            <a href="{{ route('insurance-policies.show', $policy) }}" wire:navigate class="hover:underline">
                                {{ $policy->insurer }}
                            </a>
                        </flux:heading>
                        @if ($policy->contact_name)
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $policy->contact_name }}
                            </flux:text>
                        @endif
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$policy->type === \App\Enums\InsuranceType::Cyber ? 'sky' : 'zinc'" size="sm">
                                {{ $policy->type->label() }}
                            </flux:badge>
                            @if ($policy->isExpired())
                                <flux:badge color="red" size="sm">{{ __('abgelaufen') }}</flux:badge>
                            @elseif ($policy->valid_until)
                                <flux:badge color="zinc" size="sm" icon="calendar">{{ __('bis') }} {{ $policy->valid_until->format('d.m.Y') }}</flux:badge>
                            @endif
                            @if ($policy->approval_required)
                                <flux:badge color="amber" size="sm">{{ __('Freigabe nötig') }}</flux:badge>
                            @endif
                            @if ($policy->isReviewOverdue())
                                <flux:badge color="red" size="sm" icon="arrow-path">{{ __('Prüfung überfällig') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="eye" :href="route('insurance-policies.show', $policy)" wire:navigate>
                                {{ __('Details') }}
                            </flux:menu.item>
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
                        <div class="flex items-start gap-2">
                            <flux:icon.document-text class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Police-Nummer') }}</div>
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $policy->policy_number }}</span>
                            </div>
                        </div>
                    @endif
                    @if ($policy->hotline)
                        <div class="flex items-start gap-2">
                            <flux:icon.phone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Schadenhotline') }}</div>
                                <a href="tel:{{ $policy->hotline }}" class="font-medium hover:underline">{{ $policy->hotline }}</a>
                            </div>
                        </div>
                    @endif
                    @if ($policy->reporting_window)
                        <div class="flex items-start gap-2">
                            <flux:icon.clock class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Meldefrist') }}</div>
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $policy->reporting_window }}</span>
                            </div>
                        </div>
                    @endif
                    @if ($policy->coverage_amount || $policy->deductible)
                        <div class="flex items-start gap-2">
                            <flux:icon.banknotes class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Deckung / Selbstbehalt') }}</div>
                                <span class="text-zinc-700 dark:text-zinc-200">@if ($policy->coverage_amount){{ $policy->coverage_amount }}@endif @if ($policy->coverage_amount && $policy->deductible) · @endif @if ($policy->deductible){{ __('SB') }} {{ $policy->deductible }}@endif</span>
                            </div>
                        </div>
                    @endif
                    @if ($policy->scenarios->isNotEmpty())
                        <div class="flex flex-wrap gap-1 pt-1">
                            @foreach ($policy->scenarios as $scenario)
                                <flux:badge color="zinc" size="sm">{{ $scenario->name }}</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($policy->notes)
                    <flux:text class="mt-4 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        {{ $policy->notes }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Versicherung hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="policy-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Versicherung bearbeiten') : __('Neue Versicherung') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Diese Daten müssen im Schadensfall griffbereit sein.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="type" :label="__('Art der Versicherung')" required>
                    @foreach (\App\Enums\InsuranceType::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="insurer" :label="__('Versicherer')" type="text" required placeholder="z. B. Musterversicherung AG" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="policy_number" :label="__('Policennummer')" type="text" />
                <flux:input wire:model="valid_from" :label="__('Laufzeit von')" type="date" />
                <flux:input wire:model="valid_until" :label="__('Laufzeit bis')" type="date" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="coverage_amount" :label="__('Deckungssumme')" type="text" placeholder="z. B. 5 Mio €" />
                <flux:input wire:model="deductible" :label="__('Selbstbehalt')" type="text" placeholder="z. B. 2.500 €" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="hotline" :label="__('Schadenhotline')" type="text" placeholder="z. B. 0800 1234567" />
                <flux:input wire:model="email" :label="__('E-Mail Schadenmeldung')" type="email" />
            </div>

            <flux:input wire:model="reporting_window" :label="__('Meldefristen')" type="text" placeholder="z. B. unverzüglich, spätestens 72 Stunden" />
            <flux:textarea wire:model="required_documents" :label="__('Benötigte Unterlagen im Schadenfall')" rows="2" placeholder="z. B. Schadenanzeige, Fotos, Polizei-/IT-Forensik-Bericht, Inventarliste" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="contact_name" :label="__('Ansprechpartner beim Versicherer')" type="text" />
                <flux:select wire:model="responsible_role_id" :label="__('Zuständige interne Rolle')">
                    <flux:select.option value="">{{ __('— keine —') }}</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                <flux:checkbox wire:model="approval_required" :label="__('Freigabe des Versicherers nötig vor Forensik / Sanierung / Ersatzbeschaffung')" />
                <flux:textarea wire:model="approval_note" :label="__('Hinweis zur Freigabe')" rows="2" class="mt-3" placeholder="z. B. Cyber-Versicherer muss VOR Beauftragung von IT-Forensik informiert werden, sonst kein Deckungsschutz." />
            </div>

            @if ($this->scenarios->isNotEmpty())
                <flux:fieldset>
                    <flux:legend>{{ __('Bezug zu Szenarien') }}</flux:legend>
                    <flux:text class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Für welche Notlagen greift diese Versicherung? (z. B. Cyberangriff, Brand, Wasserschaden, Betriebsunterbrechung)') }}</flux:text>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($this->scenarios as $scenario)
                            <flux:checkbox wire:model="selectedScenarios" value="{{ $scenario->id }}" :label="$scenario->name" />
                        @endforeach
                    </div>
                </flux:fieldset>
            @endif

            <flux:fieldset>
                <flux:legend>{{ __('Prüfung & Test') }}</flux:legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="claims_process_tested_at" :label="__('Schadenmeldeweg getestet am')" type="date" />
                    <flux:input wire:model="last_reviewed_at" :label="__('Letzte Prüfung')" type="date" />
                    <flux:input wire:model="next_review_at" :label="__('Nächste Prüfung')" type="date" />
                </div>
            </flux:fieldset>

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />

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
