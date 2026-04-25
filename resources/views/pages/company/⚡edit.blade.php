<?php

use App\Enums\Industry;
use App\Enums\KritisRelevance;
use App\Enums\LegalForm;
use App\Enums\Nis2Classification;
use App\Models\Company;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Firma')] class extends Component {
    public string $name = '';

    public string $industry = '';

    public string $legal_form = '';

    public string $kritis_relevant = '';

    public string $nis2_classification = '';

    public ?string $valid_from = null;

    public ?int $employee_count = null;

    public ?int $locations_count = null;

    public string $cyber_insurance_deductible = '';

    public ?string $budget_it_lead = null;

    public ?string $budget_emergency_officer = null;

    public ?string $budget_management = null;

    public string $data_protection_authority_name = '';

    public string $data_protection_authority_phone = '';

    public string $data_protection_authority_website = '';

    public bool $exists = false;

    public function mount(): void
    {
        $company = Auth::user()->currentCompany();

        if ($company) {
            $this->exists = true;
            $this->name = $company->name;
            $this->industry = $company->industry->value;
            $this->legal_form = $company->legal_form?->value ?? '';
            $this->kritis_relevant = $company->kritis_relevant?->value ?? '';
            $this->nis2_classification = $company->nis2_classification?->value ?? '';
            $this->valid_from = $company->valid_from?->toDateString();
            $this->employee_count = $company->employee_count;
            $this->locations_count = $company->locations_count;
            $this->cyber_insurance_deductible = (string) $company->cyber_insurance_deductible;
            $this->budget_it_lead = $company->budget_it_lead !== null ? (string) $company->budget_it_lead : null;
            $this->budget_emergency_officer = $company->budget_emergency_officer !== null ? (string) $company->budget_emergency_officer : null;
            $this->budget_management = $company->budget_management !== null ? (string) $company->budget_management : null;
            $this->data_protection_authority_name = (string) $company->data_protection_authority_name;
            $this->data_protection_authority_phone = (string) $company->data_protection_authority_phone;
            $this->data_protection_authority_website = (string) $company->data_protection_authority_website;

            return;
        }

        $this->name = Auth::user()->currentTeam->name;
        $this->industry = Industry::Sonstiges->value;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', Rule::in(collect(Industry::cases())->pluck('value'))],
            'legal_form' => ['nullable', 'string', Rule::in(collect(LegalForm::cases())->pluck('value'))],
            'kritis_relevant' => ['nullable', 'string', Rule::in(collect(KritisRelevance::cases())->pluck('value'))],
            'nis2_classification' => ['nullable', 'string', Rule::in(collect(Nis2Classification::cases())->pluck('value'))],
            'valid_from' => ['nullable', 'date'],
            'employee_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'locations_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'cyber_insurance_deductible' => ['nullable', 'string', 'max:100'],
            'budget_it_lead' => ['nullable', 'numeric', 'min:0'],
            'budget_emergency_officer' => ['nullable', 'numeric', 'min:0'],
            'budget_management' => ['nullable', 'numeric', 'min:0'],
            'data_protection_authority_name' => ['nullable', 'string', 'max:255'],
            'data_protection_authority_phone' => ['nullable', 'string', 'max:100'],
            'data_protection_authority_website' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = collect($validated)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->toArray();

        $team = Auth::user()->currentTeam;

        Company::updateOrCreate(
            ['team_id' => $team->id],
            $payload,
        );

        $this->exists = true;

        Flux::toast(variant: 'success', text: __('Firmenprofil gespeichert.'));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function industryOptions(): array
    {
        return Industry::options();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function legalFormOptions(): array
    {
        return LegalForm::options();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function kritisOptions(): array
    {
        return KritisRelevance::options();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function nis2Options(): array
    {
        return Nis2Classification::options();
    }
}; ?>

<section class="w-full">
    <div class="mb-8">
        <flux:heading size="xl">{{ __('Firmenprofil') }}</flux:heading>
        <flux:subheading>
            {{ __('Basisdaten Ihres Unternehmens. Diese Angaben bilden die Grundlage für das Notfallhandbuch.') }}
        </flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ __('Stammdaten') }}</flux:heading>
                <flux:subheading>{{ __('Grunddaten der Organisation – Name, Rechtsform, Größe.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Firmenname')" type="text" required autofocus />

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:select wire:model="industry" :label="__('Branche')" required>
                    @foreach ($this->industryOptions() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="legal_form" :label="__('Rechtsform')">
                    <flux:select.option value="">{{ __('— bitte wählen —') }}</flux:select.option>
                    @foreach ($this->legalFormOptions() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="employee_count" :label="__('Anzahl Mitarbeitende')" type="number" min="0" placeholder="z. B. 24" />
                <flux:input wire:model="locations_count" :label="__('Anzahl Standorte')" type="number" min="0" placeholder="z. B. 2" />
                <flux:input wire:model="valid_from" :label="__('Geltung ab')" type="date" />
            </div>
        </div>

        <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ __('Aufsicht & Compliance') }}</flux:heading>
                <flux:subheading>{{ __('KRITIS- und NIS2-Einordnung sowie zuständige Datenschutz-Aufsichtsbehörde (Kap. 14).') }}</flux:subheading>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:select wire:model="kritis_relevant" :label="__('KRITIS-relevant?')">
                    <flux:select.option value="">{{ __('— nicht angegeben —') }}</flux:select.option>
                    @foreach ($this->kritisOptions() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="nis2_classification" :label="__('NIS2-Einordnung')">
                    <flux:select.option value="">{{ __('— nicht angegeben —') }}</flux:select.option>
                    @foreach ($this->nis2Options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="data_protection_authority_name" :label="__('Datenschutz-Aufsichtsbehörde')" type="text" placeholder="z. B. LfDI Baden-Württemberg" />
                <flux:input wire:model="data_protection_authority_phone" :label="__('Telefon')" type="text" />
                <flux:input wire:model="data_protection_authority_website" :label="__('Website')" type="text" placeholder="www…" />
            </div>
        </div>

        <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ __('Finanzielle Befugnisse im Notfall') }}</flux:heading>
                <flux:subheading>
                    {{ __('Maximalbeträge je Einzelmaßnahme, die ohne weitere Freigabe ausgegeben werden dürfen (Kap. 8.1).') }}
                </flux:subheading>
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="budget_it_lead" :label="__('IT-Verantwortliche/r (€)')" type="number" min="0" step="0.01" placeholder="z. B. 500" />
                <flux:input wire:model="budget_emergency_officer" :label="__('Notfallbeauftragte/r (€)')" type="number" min="0" step="0.01" placeholder="z. B. 2000" />
                <flux:input wire:model="budget_management" :label="__('Geschäftsführung (€)')" type="number" min="0" step="0.01" placeholder="z. B. 20000" />
            </div>
        </div>

        <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ __('Cyber-Versicherung') }}</flux:heading>
                <flux:subheading>{{ __('Selbstbehalt der Cyber-Police (Kap. 8.1).') }}</flux:subheading>
            </div>

            <flux:input wire:model="cyber_insurance_deductible" :label="__('Selbstbehalt')" type="text" placeholder="z. B. 1.500 €" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button variant="primary" type="submit">
                {{ $exists ? __('Änderungen speichern') : __('Firmenprofil anlegen') }}
            </flux:button>
        </div>
    </form>
</section>
