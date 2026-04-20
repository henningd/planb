<?php

use App\Enums\Industry;
use App\Models\Company;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Firma')] class extends Component {
    public string $name = '';

    public string $industry = '';

    public ?int $employee_count = null;

    public ?int $locations_count = null;

    public bool $exists = false;

    public function mount(): void
    {
        $company = Auth::user()->currentCompany();

        if ($company) {
            $this->exists = true;
            $this->name = $company->name;
            $this->industry = $company->industry->value;
            $this->employee_count = $company->employee_count;
            $this->locations_count = $company->locations_count;

            return;
        }

        $this->name = Auth::user()->currentTeam->name;
        $this->industry = Industry::Sonstiges->value;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'in:'.collect(Industry::cases())->pluck('value')->implode(',')],
            'employee_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'locations_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $team = Auth::user()->currentTeam;

        Company::updateOrCreate(
            ['team_id' => $team->id],
            $validated,
        );

        $this->exists = true;

        Flux::toast(variant: 'success', text: __('Firmenprofil gespeichert.'));
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public function industryOptions(): array
    {
        return Industry::options();
    }
}; ?>

<section class="w-full">
    <div class="mb-8">
        <flux:heading size="xl">{{ __('Firmenprofil') }}</flux:heading>
        <flux:subheading>
            {{ __('Basisdaten Ihres Unternehmens. Diese Angaben bilden die Grundlage für das Notfallhandbuch.') }}
        </flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input
            wire:model="name"
            :label="__('Firmenname')"
            type="text"
            required
            autofocus
        />

        <flux:select wire:model="industry" :label="__('Branche')" required>
            @foreach ($this->industryOptions() as $option)
                <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input
                wire:model="employee_count"
                :label="__('Anzahl Mitarbeitende')"
                type="number"
                min="0"
                placeholder="z. B. 24"
            />

            <flux:input
                wire:model="locations_count"
                :label="__('Anzahl Standorte')"
                type="number"
                min="0"
                placeholder="z. B. 2"
            />
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
            <flux:button variant="primary" type="submit">
                {{ $exists ? __('Änderungen speichern') : __('Firmenprofil anlegen') }}
            </flux:button>
        </div>
    </form>
</section>
