<?php

use App\Enums\ContractCoverage;
use App\Models\Contract;
use App\Models\Location;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Support\Duration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Vertrag')] class extends Component {
    public ?Contract $contract = null;

    public string $title = '';

    public string $service_provider_id = '';

    public string $contract_number = '';

    public string $coverage = '';

    public string $service_hours = '';

    public ?int $response_time_minutes = null;

    public ?int $resolution_time_minutes = null;

    public ?string $availability_percent = null;

    public string $emergency_hotline = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $notes = '';

    /** @var array<int, string> */
    public array $system_ids = [];

    /** @var array<int, string> */
    public array $location_ids = [];

    public function mount(?Contract $contract = null): void
    {
        abort_unless(Auth::user()->currentCompany(), 403);

        if ($contract && $contract->exists) {
            $contract->load(['systems', 'locations']);

            $this->contract = $contract;
            $this->title = $contract->title;
            $this->service_provider_id = (string) $contract->service_provider_id;
            $this->contract_number = (string) $contract->contract_number;
            $this->coverage = $contract->coverage?->value ?? '';
            $this->service_hours = (string) $contract->service_hours;
            $this->response_time_minutes = $contract->response_time_minutes;
            $this->resolution_time_minutes = $contract->resolution_time_minutes;
            $this->availability_percent = $contract->availability_percent !== null ? (string) $contract->availability_percent : null;
            $this->emergency_hotline = (string) $contract->emergency_hotline;
            $this->emergency_contact_name = (string) $contract->emergency_contact_name;
            $this->emergency_contact_phone = (string) $contract->emergency_contact_phone;
            $this->start_date = $contract->start_date?->toDateString();
            $this->end_date = $contract->end_date?->toDateString();
            $this->notes = (string) $contract->notes;
            $this->system_ids = $contract->systems->pluck('id')->all();
            $this->location_ids = $contract->locations->pluck('id')->all();
        }
    }

    #[Computed]
    public function providers()
    {
        return ServiceProvider::orderBy('name')->get();
    }

    #[Computed]
    public function systems()
    {
        return System::orderBy('name')->get();
    }

    #[Computed]
    public function locations()
    {
        return Location::orderBy('name')->get();
    }

    public function save(): void
    {
        abort_unless(Auth::user()->currentCompany(), 403);

        $validDurations = array_keys(Duration::OPTIONS);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'service_provider_id' => ['required', 'uuid', 'exists:service_providers,id'],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'coverage' => ['nullable', 'in:'.collect(ContractCoverage::cases())->pluck('value')->implode(',')],
            'service_hours' => ['nullable', 'string', 'max:255'],
            'response_time_minutes' => ['nullable', 'integer', 'in:'.implode(',', $validDurations)],
            'resolution_time_minutes' => ['nullable', 'integer', 'in:'.implode(',', $validDurations)],
            'availability_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'emergency_hotline' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'system_ids' => ['array'],
            'system_ids.*' => ['uuid', 'exists:systems,id'],
            'location_ids' => ['array'],
            'location_ids.*' => ['uuid', 'exists:locations,id'],
        ]);

        $data = [
            'service_provider_id' => $validated['service_provider_id'],
            'title' => $validated['title'],
            'contract_number' => $validated['contract_number'] ?: null,
            'coverage' => $validated['coverage'] ?: null,
            'service_hours' => $validated['service_hours'] ?: null,
            'response_time_minutes' => $validated['response_time_minutes'],
            'resolution_time_minutes' => $validated['resolution_time_minutes'],
            'availability_percent' => $validated['availability_percent'] !== null && $validated['availability_percent'] !== ''
                ? $validated['availability_percent']
                : null,
            'emergency_hotline' => $validated['emergency_hotline'] ?: null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?: null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?: null,
            'start_date' => $validated['start_date'] ?: null,
            'end_date' => $validated['end_date'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        $contract = $this->contract
            ? tap($this->contract)->update($data)
            : Contract::create($data);

        $contract->systems()->sync($validated['system_ids'] ?? []);
        $contract->locations()->sync($validated['location_ids'] ?? []);

        Flux::toast(variant: 'success', text: __('Vertrag gespeichert.'));

        $this->redirectRoute('contracts.show', ['contract' => $contract->id], navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="mb-6">
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('contracts.index')" wire:navigate>
            {{ __('Zurück') }}
        </flux:button>
        <flux:heading size="xl" class="mt-2">
            {{ $this->contract ? __('Vertrag bearbeiten') : __('Neuen Vertrag erfassen') }}
        </flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Basis') }}</flux:heading>
            <div class="mt-4 space-y-4">
                <flux:input wire:model="title" :label="__('Vertragsbezeichnung')" :placeholder="__('z. B. Wartungsvertrag Klimaanlage')" required />

                <flux:select wire:model="service_provider_id" :label="__('Dienstleister')" required>
                    <flux:select.option value="">{{ __('— bitte wählen —') }}</flux:select.option>
                    @foreach ($this->providers as $provider)
                        <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->providers->isEmpty())
                    <flux:text class="text-sm text-amber-600 dark:text-amber-400">
                        {{ __('Noch keine Dienstleister erfasst — bitte zuerst einen Dienstleister anlegen.') }}
                    </flux:text>
                @endif

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model="contract_number" :label="__('Vertragsnummer')" />
                    <flux:input wire:model="start_date" :label="__('Laufzeit von')" type="date" />
                    <flux:input wire:model="end_date" :label="__('Laufzeit bis')" type="date" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('SLA / Servicevereinbarung') }}</flux:heading>
            <flux:subheading class="mb-4">
                {{ __('Was ist vertraglich zugesichert — wie schnell muss reagiert und wiederhergestellt werden?') }}
            </flux:subheading>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="coverage" :label="__('Abdeckung / Servicezeiten')">
                    <flux:select.option value="">{{ __('— offen —') }}</flux:select.option>
                    @foreach (ContractCoverage::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="service_hours" :label="__('Servicezeiten (Detail)')" :placeholder="__('z. B. Mo–Fr 8–17')" />

                <flux:select wire:model="response_time_minutes" :label="__('Reaktionszeit')">
                    <flux:select.option value="">{{ __('— offen —') }}</flux:select.option>
                    @foreach (Duration::options() as $opt)
                        <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="resolution_time_minutes" :label="__('Wiederherstellungszeit')">
                    <flux:select.option value="">{{ __('— offen —') }}</flux:select.option>
                    @foreach (Duration::options() as $opt)
                        <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="availability_percent" :label="__('Verfügbarkeit (%)')" type="number" step="0.01" min="0" max="100" :placeholder="__('z. B. 99.90')" />
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Störungs-/Notfallkontakt') }}</flux:heading>
            <flux:subheading class="mb-4">
                {{ __('Diese Nummer gilt im Störungsfall — auch wenn sie von der allgemeinen Dienstleister-Hotline abweicht.') }}
            </flux:subheading>

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="emergency_hotline" :label="__('Störungs-Hotline')" />
                <flux:input wire:model="emergency_contact_name" :label="__('Ansprechpartner')" />
                <flux:input wire:model="emergency_contact_phone" :label="__('Telefon Ansprechpartner')" />
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Verknüpfte Systeme & Standorte') }}</flux:heading>
            <flux:subheading class="mb-4">
                {{ __('Welche Systeme und Standorte deckt dieser Vertrag ab? So ist im Notfall sofort ersichtlich, welcher Vertrag greift.') }}
            </flux:subheading>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <flux:label>{{ __('Systeme') }}</flux:label>
                    @if ($this->systems->isEmpty())
                        <flux:text class="mt-2 text-sm text-zinc-500">{{ __('Noch keine Systeme erfasst.') }}</flux:text>
                    @else
                        <div class="mt-2 space-y-1">
                            @foreach ($this->systems as $system)
                                <flux:checkbox wire:model="system_ids" value="{{ $system->id }}" :label="$system->name" />
                            @endforeach
                        </div>
                    @endif
                </div>
                <div>
                    <flux:label>{{ __('Standorte') }}</flux:label>
                    @if ($this->locations->isEmpty())
                        <flux:text class="mt-2 text-sm text-zinc-500">{{ __('Noch keine Standorte erfasst.') }}</flux:text>
                    @else
                        <div class="mt-2 space-y-1">
                            @foreach ($this->locations as $location)
                                <flux:checkbox wire:model="location_ids" value="{{ $location->id }}" :label="$location->name" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Notizen') }}</flux:heading>
            <flux:textarea wire:model="notes" class="mt-4" rows="3" :placeholder="__('Besonderheiten, Eskalationsweg, Pönalen …')" />
        </div>

        <div class="flex items-center justify-end gap-2">
            <flux:button type="button" variant="filled" :href="route('contracts.index')" wire:navigate>
                {{ __('Abbrechen') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Vertrag speichern') }}
            </flux:button>
        </div>
    </form>
</section>
