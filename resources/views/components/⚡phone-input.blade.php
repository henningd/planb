<?php

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

new class extends Component {
    #[Modelable]
    public ?string $value = null;

    public string $country = 'DE';

    public string $national = '';

    public string $label = '';

    public string $placeholder = '';

    public bool $required = false;

    public string $defaultCountry = 'DE';

    public function mount(string $label = '', string $placeholder = '', bool $required = false, string $defaultCountry = 'DE'): void
    {
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->required = $required;
        $this->defaultCountry = $defaultCountry;
        $this->country = $defaultCountry;

        $this->parseValue();
    }

    public function setCountry(string $code): void
    {
        if (! array_key_exists($code, $this->countries)) {
            return;
        }

        $this->country = $code;
    }

    public function updatedCountry(): void
    {
        $this->recompose();
    }

    public function updatedNational(): void
    {
        $this->recompose();
    }

    /**
     * Wird der Wert von außen gesetzt (z. B. nach Auswahl einer
     * Aufsichtsbehörde-Karte), Country/National-State neu aus dem Wert
     * ableiten — aber nur wenn der externe Wert nicht zu unseren aktuellen
     * Eingaben passt (vermeidet Endlosschleifen mit recompose()).
     */
    public function updatedValue(): void
    {
        try {
            $util = PhoneNumberUtil::getInstance();
            $reconstructed = blank($this->national)
                ? null
                : $util->format($util->parse($this->national, $this->country), PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            $reconstructed = $this->national ?: null;
        }

        if ($reconstructed !== $this->value) {
            $this->parseValue();
        }
    }

    /**
     * Liste der unterstützten Länder mit ISO-Code, Anzeigename und Vorwahl.
     * Reihenfolge: DACH zuerst, dann häufige EU-Nachbarn, dann weitere Länder.
     *
     * @return array<string, array{name: string, dial: string}>
     */
    #[Computed]
    public function countries(): array
    {
        return [
            'DE' => ['name' => 'Deutschland', 'dial' => '+49'],
            'AT' => ['name' => 'Österreich', 'dial' => '+43'],
            'CH' => ['name' => 'Schweiz', 'dial' => '+41'],
            'FR' => ['name' => 'Frankreich', 'dial' => '+33'],
            'NL' => ['name' => 'Niederlande', 'dial' => '+31'],
            'BE' => ['name' => 'Belgien', 'dial' => '+32'],
            'LU' => ['name' => 'Luxemburg', 'dial' => '+352'],
            'IT' => ['name' => 'Italien', 'dial' => '+39'],
            'ES' => ['name' => 'Spanien', 'dial' => '+34'],
            'PT' => ['name' => 'Portugal', 'dial' => '+351'],
            'DK' => ['name' => 'Dänemark', 'dial' => '+45'],
            'SE' => ['name' => 'Schweden', 'dial' => '+46'],
            'NO' => ['name' => 'Norwegen', 'dial' => '+47'],
            'FI' => ['name' => 'Finnland', 'dial' => '+358'],
            'PL' => ['name' => 'Polen', 'dial' => '+48'],
            'CZ' => ['name' => 'Tschechien', 'dial' => '+420'],
            'SK' => ['name' => 'Slowakei', 'dial' => '+421'],
            'HU' => ['name' => 'Ungarn', 'dial' => '+36'],
            'GB' => ['name' => 'Vereinigtes Königreich', 'dial' => '+44'],
            'IE' => ['name' => 'Irland', 'dial' => '+353'],
            'US' => ['name' => 'USA / Kanada', 'dial' => '+1'],
            'TR' => ['name' => 'Türkei', 'dial' => '+90'],
        ];
    }

    protected function parseValue(): void
    {
        if (blank($this->value)) {
            $this->national = '';

            return;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $parsed = $util->parse((string) $this->value, $this->country);

            $region = $util->getRegionCodeForNumber($parsed);
            if ($region && array_key_exists($region, $this->countries)) {
                $this->country = $region;
            }

            $this->national = $util->format($parsed, PhoneNumberFormat::NATIONAL);
        } catch (NumberParseException) {
            $this->national = (string) $this->value;
        }
    }

    protected function recompose(): void
    {
        if (blank($this->national)) {
            $this->value = null;

            return;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $parsed = $util->parse($this->national, $this->country);
            $this->value = $util->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            $this->value = $this->national;
        }
    }
}; ?>

<flux:field>
    @if ($label !== '')
        <flux:label :required="$required">{{ $label }}</flux:label>
    @endif

    <div class="flex gap-2">
        <flux:dropdown>
            <flux:button type="button" variant="filled" class="!w-28 shrink-0 justify-between">
                <span class="flex items-center gap-2 truncate">
                    <span class="fi fi-{{ strtolower($country) }} shrink-0 rounded-sm shadow-sm" style="width: 1.25rem; height: 0.9375rem; background-size: cover; background-position: center;"></span>
                    <span class="truncate text-sm">{{ $this->countries[$country]['dial'] ?? '' }}</span>
                </span>
                <flux:icon.chevron-down class="-mr-1 size-4 shrink-0 text-zinc-400" />
            </flux:button>

            <flux:menu class="max-h-80 overflow-y-auto">
                @foreach ($this->countries as $code => $info)
                    <flux:menu.item
                        wire:click="setCountry('{{ $code }}')"
                        wire:key="phone-country-{{ $code }}"
                    >
                        <span class="flex items-center gap-3">
                            <span class="fi fi-{{ strtolower($code) }} shrink-0 rounded-sm shadow-sm" style="width: 1.25rem; height: 0.9375rem; background-size: cover; background-position: center;"></span>
                            <span class="flex-1">{{ $info['name'] }}</span>
                            <span class="text-zinc-500">{{ $info['dial'] }}</span>
                        </span>
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        <flux:input
            wire:model.live.debounce.400ms="national"
            type="tel"
            class="flex-1"
            inputmode="tel"
            autocomplete="tel-national"
            :placeholder="$placeholder !== '' ? $placeholder : __('z. B. 30 1234567')"
        />
    </div>

    <flux:error name="value" />
</flux:field>
