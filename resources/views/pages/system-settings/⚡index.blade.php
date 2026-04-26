<?php

use App\Support\Settings\CompanySetting;
use App\Support\Settings\SettingsCatalog;
use App\Support\Settings\SystemSetting;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Systemeinstellungen')] class extends Component {
    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * @var array<string, bool>
     */
    public array $overrides = [];

    public function mount(): void
    {
        $company = Auth::user()->currentCompany();
        if ($company === null) {
            return;
        }

        $tenant = CompanySetting::for($company);
        foreach (SettingsCatalog::byScope(SettingsCatalog::COMPANY) as $key => $def) {
            $this->overrides[$key] = $tenant->isOverridden($key);
            $this->values[$key] = $this->overrides[$key]
                ? $tenant->get($key)
                : SystemSetting::get($key, $def['default']);
        }
    }

    public function save(): void
    {
        $company = Auth::user()->currentCompany();
        if ($company === null) {
            Flux::toast(variant: 'warning', text: __('Kein Mandant aktiv.'));

            return;
        }

        $tenant = CompanySetting::for($company);
        foreach (SettingsCatalog::byScope(SettingsCatalog::COMPANY) as $key => $def) {
            if (! empty($this->overrides[$key])) {
                $tenant->set($key, $this->values[$key] ?? $def['default']);
            } else {
                $tenant->unset($key);
            }
        }

        Flux::toast(variant: 'success', text: __('Mandanten-Einstellungen gespeichert.'));
    }

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public function defs(): array
    {
        return SettingsCatalog::byScope(SettingsCatalog::COMPANY);
    }

    public function platformDefault(string $key): mixed
    {
        $def = SettingsCatalog::definition($key);

        return SystemSetting::get($key, $def['default'] ?? null);
    }

    public function formatValue(string $key, mixed $value): string
    {
        $def = SettingsCatalog::definition($key);
        if ($def === null) {
            return (string) $value;
        }

        return match ($def['type']) {
            'bool' => $value ? __('aktiv') : __('aus'),
            'enum' => $def['enum'][(string) $value] ?? (string) $value,
            default => (string) $value,
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Systemeinstellungen') }}</flux:heading>
        <flux:subheading>
            {{ __('Mandanten-spezifische Schalter. Jede Einstellung verwendet standardmäßig den Plattform-Default; aktivieren Sie „Eigener Wert", um sie für Ihren Mandanten zu überschreiben.') }}
        </flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-5">
        @foreach ($this->defs() as $key => $def)
            @php
                $platform = $this->platformDefault($key);
                $platformDisplay = $this->formatValue($key, $platform);
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <div class="min-w-0 flex-1">
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-100">
                            {{ __($def['label']) }}
                        </flux:text>
                        @if (! empty($def['description']))
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __($def['description']) }}</flux:text>
                        @endif
                        <flux:text class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                            {{ __('Plattform-Default') }}: <span class="font-mono">{{ $platformDisplay }}</span>
                        </flux:text>
                    </div>
                    <div class="shrink-0">
                        <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="overrides.{{ $key }}" class="rounded border-zinc-300 dark:border-zinc-600">
                            {{ __('Eigener Wert') }}
                        </label>
                    </div>
                </div>
                @if (! empty($overrides[$key]))
                    <div class="px-5 py-4">
                        @include('partials.setting-field', ['key' => $key, 'def' => $def])
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            <flux:button variant="primary" type="submit" icon="check">{{ __('Speichern') }}</flux:button>
        </div>
    </form>
</section>
