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
     * Liste der unterstützten Länder mit Flagge, Vorwahl und Anzeigename.
     * Reihenfolge: DACH zuerst, dann häufige EU-Nachbarn, dann weitere Länder.
     *
     * @return array<string, array{name: string, flag: string, dial: string}>
     */
    #[Computed]
    public function countries(): array
    {
        return [
            'DE' => ['name' => 'Deutschland', 'flag' => '🇩🇪', 'dial' => '+49'],
            'AT' => ['name' => 'Österreich', 'flag' => '🇦🇹', 'dial' => '+43'],
            'CH' => ['name' => 'Schweiz', 'flag' => '🇨🇭', 'dial' => '+41'],
            'FR' => ['name' => 'Frankreich', 'flag' => '🇫🇷', 'dial' => '+33'],
            'NL' => ['name' => 'Niederlande', 'flag' => '🇳🇱', 'dial' => '+31'],
            'BE' => ['name' => 'Belgien', 'flag' => '🇧🇪', 'dial' => '+32'],
            'LU' => ['name' => 'Luxemburg', 'flag' => '🇱🇺', 'dial' => '+352'],
            'IT' => ['name' => 'Italien', 'flag' => '🇮🇹', 'dial' => '+39'],
            'ES' => ['name' => 'Spanien', 'flag' => '🇪🇸', 'dial' => '+34'],
            'PT' => ['name' => 'Portugal', 'flag' => '🇵🇹', 'dial' => '+351'],
            'DK' => ['name' => 'Dänemark', 'flag' => '🇩🇰', 'dial' => '+45'],
            'SE' => ['name' => 'Schweden', 'flag' => '🇸🇪', 'dial' => '+46'],
            'NO' => ['name' => 'Norwegen', 'flag' => '🇳🇴', 'dial' => '+47'],
            'FI' => ['name' => 'Finnland', 'flag' => '🇫🇮', 'dial' => '+358'],
            'PL' => ['name' => 'Polen', 'flag' => '🇵🇱', 'dial' => '+48'],
            'CZ' => ['name' => 'Tschechien', 'flag' => '🇨🇿', 'dial' => '+420'],
            'SK' => ['name' => 'Slowakei', 'flag' => '🇸🇰', 'dial' => '+421'],
            'HU' => ['name' => 'Ungarn', 'flag' => '🇭🇺', 'dial' => '+36'],
            'GB' => ['name' => 'Vereinigtes Königreich', 'flag' => '🇬🇧', 'dial' => '+44'],
            'IE' => ['name' => 'Irland', 'flag' => '🇮🇪', 'dial' => '+353'],
            'US' => ['name' => 'USA / Kanada', 'flag' => '🇺🇸', 'dial' => '+1'],
            'TR' => ['name' => 'Türkei', 'flag' => '🇹🇷', 'dial' => '+90'],
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
        <flux:select wire:model.live="country" class="w-44 shrink-0">
            @foreach ($this->countries as $code => $info)
                <flux:select.option value="{{ $code }}">{{ $info['flag'] }} {{ $info['dial'] }}</flux:select.option>
            @endforeach
        </flux:select>

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
