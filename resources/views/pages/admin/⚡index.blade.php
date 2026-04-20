<?php

use App\Models\Company;
use App\Models\GlobalScenario;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Übersicht')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        return [
            'users' => User::count(),
            'companies' => Company::withoutGlobalScope(CurrentCompanyScope::class)->count(),
            'globalScenarios' => GlobalScenario::count(),
            'activeGlobalScenarios' => GlobalScenario::where('is_active', true)->count(),
        ];
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Sie sehen und bearbeiten hier Daten über alle Mandanten hinweg.') }}
    </div>

    <div class="mb-6">
        <flux:heading size="xl">{{ __('Administration') }}</flux:heading>
        <flux:subheading>{{ __('Kunden verwalten und globale Szenario-Bibliothek pflegen.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Nutzer') }}</flux:text>
            <div class="mt-2 text-3xl font-semibold">{{ $this->stats['users'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kunden') }}</flux:text>
            <div class="mt-2 text-3xl font-semibold">{{ $this->stats['companies'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Globale Szenarien') }}</flux:text>
            <div class="mt-2 text-3xl font-semibold">{{ $this->stats['globalScenarios'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('davon aktiv') }}</flux:text>
            <div class="mt-2 text-3xl font-semibold">{{ $this->stats['activeGlobalScenarios'] }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <a href="{{ route('admin.companies.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 no-underline hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base">{{ __('Kundenverwaltung') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Alle angelegten Firmen einsehen, Basisdaten ändern, bei Bedarf entfernen.') }}
            </flux:text>
        </a>
        <a href="{{ route('admin.scenarios.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 no-underline hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base">{{ __('Globale Szenario-Bibliothek') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Szenarien und Schritte pflegen, die allen neuen Firmen als Vorlage kopiert werden.') }}
            </flux:text>
        </a>
    </div>
</section>
