<?php

use App\Enums\Industry;
use App\Enums\KritisRelevance;
use App\Enums\LegalForm;
use App\Enums\Nis2Classification;
use App\Models\Company;
use App\Models\DataProtectionAuthority;
use App\Models\Location;
use App\Support\DataProtectionAuthorities;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
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
     * Hauptstandort des Mandanten (HQ) oder erster Standort als Fallback.
     */
    #[Computed]
    public function headquartersLocation(): ?Location
    {
        return Location::query()
            ->orderByDesc('is_headquarters')
            ->orderBy('sort')
            ->orderBy('name')
            ->first();
    }

    /**
     * Anhand der HQ-PLZ vorgeschlagene Aufsichtsbehörde, oder `null`.
     */
    #[Computed]
    public function suggestedAuthority(): ?DataProtectionAuthority
    {
        $plz = $this->headquartersLocation?->postal_code;

        return DataProtectionAuthorities::resolveByPostalCode($plz);
    }

    /**
     * Übernimmt Name, Telefon und Website der vorgeschlagenen Behörde
     * in die Eingabefelder. Manuelle Korrektur danach jederzeit möglich.
     */
    public function applySuggestedAuthority(): void
    {
        $authority = $this->suggestedAuthority;
        if ($authority === null) {
            Flux::toast(variant: 'warning', text: __('Kein Vorschlag verfügbar — bitte PLZ am Hauptstandort prüfen.'));

            return;
        }

        $this->data_protection_authority_name = $authority->name;
        $this->data_protection_authority_phone = (string) $authority->phone;
        $this->data_protection_authority_website = (string) $authority->website;

        Flux::toast(variant: 'success', text: __(':name übernommen.', ['name' => $authority->short_name ?? $authority->name]));
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

            @php
                $hq = $this->headquartersLocation;
                $hqPlz = $hq?->postal_code;
                $suggestion = $this->suggestedAuthority;
                $currentName = trim((string) $data_protection_authority_name);
                $matchesSuggestion = $suggestion && $currentName !== '' && mb_strtolower($currentName) === mb_strtolower($suggestion->name);
            @endphp

            @if ($suggestion && ! $matchesSuggestion)
                <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm dark:border-sky-800 dark:bg-sky-950" data-test="dpa-suggestion">
                    <div class="flex items-start gap-3">
                        <flux:icon.information-circle class="mt-0.5 h-5 w-5 shrink-0 text-sky-600 dark:text-sky-400" />
                        <div class="flex-1">
                            <div class="font-medium text-sky-900 dark:text-sky-100">
                                {{ __('Vorschlag für PLZ :plz: :name', ['plz' => $hqPlz, 'name' => $suggestion->short_name ?? $suggestion->name]) }}
                            </div>
                            <div class="mt-1 text-xs text-sky-800 dark:text-sky-200">
                                {{ $suggestion->name }}
                                @if ($suggestion->city)· {{ $suggestion->city }}@endif
                                @if ($suggestion->phone)· {{ $suggestion->phone }}@endif
                            </div>
                            @if ($suggestion->notes)
                                <div class="mt-1 text-xs text-sky-700 dark:text-sky-300">
                                    {{ $suggestion->notes }}
                                </div>
                            @endif
                        </div>
                        <flux:button size="xs" variant="primary" wire:click="applySuggestedAuthority" type="button" icon="arrow-down-tray">
                            {{ __('Übernehmen') }}
                        </flux:button>
                    </div>
                </div>
            @elseif ($suggestion && $matchesSuggestion)
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" data-test="dpa-suggestion-match">
                    <div class="flex items-center gap-2">
                        <flux:icon.check-circle class="h-4 w-4" />
                        {{ __('Zuordnung passt zur PLZ :plz des Hauptstandorts.', ['plz' => $hqPlz]) }}
                    </div>
                </div>
            @elseif ($hqPlz)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    <div class="flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="h-4 w-4" />
                        {{ __('Für PLZ :plz konnte keine Aufsichtsbehörde automatisch zugeordnet werden — bitte manuell pflegen.', ['plz' => $hqPlz]) }}
                    </div>
                </div>
            @elseif ($hq && ! $hqPlz)
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    {{ __('Hinterlegen Sie die PLZ am Hauptstandort, dann schlagen wir die zuständige Aufsichtsbehörde automatisch vor.') }}
                </div>
            @endif

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
