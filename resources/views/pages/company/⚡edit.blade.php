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

    /** Auswahl-Modus für die Behörde: 'list' = aus Liste gewählt, 'custom' = Freitext. */
    public string $authority_mode = 'list';

    /** ID der gewählten Behörde (nur relevant wenn $authority_mode === 'list'). */
    public ?string $selected_authority_id = null;

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

            // Erkennen, ob der bestehende Name auf eine seedseitig hinterlegte Behörde
            // matcht — dann „list"-Modus mit Vorauswahl, sonst „custom".
            $matched = trim($this->data_protection_authority_name) !== ''
                ? DataProtectionAuthority::query()
                    ->where('name', $this->data_protection_authority_name)
                    ->first()
                : null;

            if ($matched) {
                $this->authority_mode = 'list';
                $this->selected_authority_id = $matched->id;
            } elseif (trim($this->data_protection_authority_name) !== '') {
                $this->authority_mode = 'custom';
            } else {
                $this->authority_mode = 'list';
                $this->selected_authority_id = null;
            }

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
     * Alle plattformweit gepflegten Behörden (sortiert für die Auswahl-Cards).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, DataProtectionAuthority>
     */
    #[Computed]
    public function authorities(): \Illuminate\Database\Eloquent\Collection
    {
        return DataProtectionAuthority::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    /**
     * Wechselt auf eine Behörde aus der Liste und füllt Name/Telefon/Website
     * aus deren Stammdaten.
     */
    public function selectAuthority(string $id): void
    {
        $authority = DataProtectionAuthority::find($id);
        if ($authority === null) {
            return;
        }

        $this->authority_mode = 'list';
        $this->selected_authority_id = $authority->id;
        $this->data_protection_authority_name = $authority->name;
        $this->data_protection_authority_phone = (string) $authority->phone;
        $this->data_protection_authority_website = (string) $authority->website;
    }

    /**
     * Wechselt in den Freitext-Modus für eine eigene, nicht in der Liste
     * geführte Aufsichtsbehörde.
     */
    public function selectCustom(): void
    {
        $this->authority_mode = 'custom';
        $this->selected_authority_id = null;
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

        $this->selectAuthority($authority->id);

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

            <flux:field>
                <flux:label>{{ __('Zuständige Aufsichtsbehörde') }}</flux:label>
                <flux:description>
                    {{ __('Karte anklicken zum Auswählen. Falls keine passt, „Benutzerdefiniert" wählen und manuell eintragen.') }}
                </flux:description>

                @if ($this->authorities->isEmpty())
                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        {{ __('Noch keine Behörden hinterlegt — der Superadmin kann sie unter Admin → Datenschutz-Aufsichtsbehörden pflegen.') }}
                    </div>
                @else
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->authorities as $authority)
                            @php
                                $isSelected = $authority_mode === 'list' && $selected_authority_id === $authority->id;
                                $isSuggested = $suggestion && $suggestion->id === $authority->id;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectAuthority('{{ $authority->id }}')"
                                wire:key="dpa-card-{{ $authority->id }}"
                                class="group relative flex cursor-pointer flex-col gap-1 rounded-lg border p-4 text-left transition
                                    {{ $isSelected
                                        ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-500 dark:border-teal-500 dark:bg-teal-950/40'
                                        : 'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500' }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-zinc-900 dark:text-white">
                                            {{ $authority->short_name ?? $authority->name }}
                                        </div>
                                        @if ($authority->state)
                                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $authority->state }}</div>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        @if ($isSuggested)
                                            <flux:badge color="sky" size="sm">{{ __('Vorschlag') }}</flux:badge>
                                        @endif
                                        @if ($isSelected)
                                            <flux:icon.check-circle class="h-5 w-5 text-teal-600 dark:text-teal-400" />
                                        @endif
                                    </div>
                                </div>

                                @if ($authority->short_name && $authority->short_name !== $authority->name)
                                    <div class="line-clamp-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $authority->name }}</div>
                                @endif

                                <div class="mt-1 space-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($authority->city)
                                        <div class="flex items-center gap-1">
                                            <flux:icon.map-pin class="h-3.5 w-3.5" />
                                            {{ $authority->city }}
                                        </div>
                                    @endif
                                    @if ($authority->phone)
                                        <div class="flex items-center gap-1">
                                            <flux:icon.phone class="h-3.5 w-3.5" />
                                            {{ $authority->phone }}
                                        </div>
                                    @endif
                                </div>
                            </button>
                        @endforeach

                        @php($isCustom = $authority_mode === 'custom')
                        <button
                            type="button"
                            wire:click="selectCustom"
                            class="group relative flex cursor-pointer flex-col items-start justify-center gap-1 rounded-lg border border-dashed p-4 text-left transition
                                {{ $isCustom
                                    ? 'border-zinc-500 bg-zinc-50 ring-2 ring-zinc-400 dark:border-zinc-400 dark:bg-zinc-950/40'
                                    : 'border-zinc-300 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500' }}"
                        >
                            <div class="flex w-full items-start justify-between gap-2">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ __('Benutzerdefiniert') }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Eigene Behörde manuell eintragen') }}
                                    </div>
                                </div>
                                @if ($isCustom)
                                    <flux:icon.check-circle class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                                @else
                                    <flux:icon.pencil-square class="h-5 w-5 text-zinc-400 dark:text-zinc-500" />
                                @endif
                            </div>
                        </button>
                    </div>
                @endif
            </flux:field>

            @if ($authority_mode === 'custom')
                <div class="grid gap-6 sm:grid-cols-3">
                    <flux:input wire:model="data_protection_authority_name" :label="__('Behörde / Name')" type="text" placeholder="z. B. LfDI Baden-Württemberg" />
                    <flux:input wire:model="data_protection_authority_phone" :label="__('Telefon')" type="text" />
                    <flux:input wire:model="data_protection_authority_website" :label="__('Website')" type="text" placeholder="https://…" />
                </div>
            @elseif ($selected_authority_id)
                @php($selectedAuthority = $this->authorities->firstWhere('id', $selected_authority_id))
                @if ($selectedAuthority)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-xs dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <div class="font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('Telefon') }}</div>
                                <div class="mt-1 text-zinc-800 dark:text-zinc-200">{{ $selectedAuthority->phone ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('E-Mail') }}</div>
                                <div class="mt-1 break-all text-zinc-800 dark:text-zinc-200">{{ $selectedAuthority->email ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('Website') }}</div>
                                <div class="mt-1 break-all text-zinc-800 dark:text-zinc-200">
                                    @if ($selectedAuthority->website)
                                        <a href="{{ $selectedAuthority->website }}" target="_blank" rel="noopener" class="text-sky-600 hover:underline dark:text-sky-400">{{ $selectedAuthority->website }}</a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if ($selectedAuthority->breach_notification_url)
                            <div class="mt-3 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                                <span class="font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('Datenpannen-Meldeformular') }}:</span>
                                <a href="{{ $selectedAuthority->breach_notification_url }}" target="_blank" rel="noopener" class="ml-1 break-all text-sky-600 hover:underline dark:text-sky-400">{{ $selectedAuthority->breach_notification_url }}</a>
                            </div>
                        @endif
                        @if ($selectedAuthority->notes)
                            <div class="mt-3 border-t border-zinc-200 pt-2 text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">
                                {{ $selectedAuthority->notes }}
                            </div>
                        @endif
                    </div>
                @endif
            @endif
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
