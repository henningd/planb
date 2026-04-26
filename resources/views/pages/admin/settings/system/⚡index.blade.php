<?php

use App\Support\Settings\SettingsCatalog;
use App\Support\Settings\SystemSetting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Systemeinstellungen')] class extends Component {
    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    public function mount(): void
    {
        foreach (SettingsCatalog::all() as $key => $def) {
            $this->values[$key] = SystemSetting::get($key, $def['default']);
        }
    }

    public function save(): void
    {
        foreach (SettingsCatalog::all() as $key => $def) {
            SystemSetting::set($key, $this->values[$key] ?? $def['default']);
        }

        Flux::toast(variant: 'success', text: __('Systemeinstellungen gespeichert.'));
    }

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public function systemDefs(): array
    {
        return SettingsCatalog::byScope(SettingsCatalog::SYSTEM);
    }

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public function companyDefs(): array
    {
        return SettingsCatalog::byScope(SettingsCatalog::COMPANY);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Plattformweite Schalter und Standard-Werte für alle Mandanten. Einzelne Mandanten können in der Kundenliste abweichende Werte setzen.') }}
    </div>

    <div class="mb-6">
        <flux:heading size="xl">{{ __('Systemeinstellungen') }}</flux:heading>
        <flux:subheading>{{ __('Effektiver Wert pro Mandant: Mandanten-Override > Plattform-Default > Code-Default.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-8">

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Plattform-Schalter') }}</flux:heading>
                <flux:subheading>{{ __('Wirken global, unabhängig vom Mandanten.') }}</flux:subheading>
            </div>
            <div class="space-y-5 p-5">
                @foreach ($this->systemDefs() as $key => $def)
                    @include('partials.setting-field', ['key' => $key, 'def' => $def])
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Mandanten-Defaults') }}</flux:heading>
                <flux:subheading>{{ __('Vorgaben, die jeder neue / nicht überschreibende Mandant erbt.') }}</flux:subheading>
            </div>
            <div class="space-y-5 p-5">
                @foreach ($this->companyDefs() as $key => $def)
                    @include('partials.setting-field', ['key' => $key, 'def' => $def])
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            <flux:button variant="primary" type="submit" icon="check">{{ __('Speichern') }}</flux:button>
        </div>
    </form>
</section>
