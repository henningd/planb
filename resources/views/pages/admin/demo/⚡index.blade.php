<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\System;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Settings\SystemSetting;
use Database\Seeders\DemoDataSeeder;
use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin · Demo-Firma')] class extends Component {
    /**
     * Demo-Logins: max ist Owner + Super-Admin der Musterfirma,
     * maxigreis ist Team-Admin (zweiter Sitz, sieht admin-gegatete Bereiche).
     *
     * @var array<string, string>
     */
    public array $demoLogins = [
        'max@mustermann.de' => 'password',
        'maxigreis@icloud.com' => 'passworD321!1',
    ];

    public string $demoEmail = 'max@mustermann.de';

    #[Computed]
    public function demoState(): array
    {
        $user = User::where('email', $this->demoEmail)->first();
        $secondary = User::where('email', 'maxigreis@icloud.com')->first();
        $team = $user?->ownedTeams()->first();
        $company = $team
            ? Company::withoutGlobalScope(CurrentCompanyScope::class)
                ->where('team_id', $team->id)
                ->first()
            : null;

        $employeeCount = $company
            ? Employee::withoutGlobalScope(CurrentCompanyScope::class)
                ->where('company_id', $company->id)
                ->count()
            : 0;

        $systemCount = $company
            ? System::withoutGlobalScope(CurrentCompanyScope::class)
                ->where('company_id', $company->id)
                ->count()
            : 0;

        return [
            'user' => $user,
            'secondary' => $secondary,
            'team' => $team,
            'company' => $company,
            'employee_count' => $employeeCount,
            'system_count' => $systemCount,
        ];
    }

    public function confirmWipe(): void
    {
        Flux::modal('demo-wipe')->show();
    }

    public function wipe(): void
    {
        if (SystemSetting::get('demo_locked', false)) {
            Flux::modal('demo-wipe')->close();
            Flux::toast(variant: 'danger', text: __('Die Demo-Funktion ist plattformweit gesperrt.'));

            return;
        }

        DB::transaction(function () {
            $primary = User::where('email', $this->demoEmail)->first();
            $secondary = User::where('email', 'maxigreis@icloud.com')->first();

            $primary?->ownedTeams()->get()->each(fn (Team $team) => $team->forceDelete());
            $primary?->forceDelete();
            $secondary?->forceDelete();
        });

        unset($this->demoState);
        Flux::modal('demo-wipe')->close();
        Flux::toast(variant: 'success', text: __('Demo-Daten gelöscht.'));
    }

    public function seed(): void
    {
        if (SystemSetting::get('demo_locked', false)) {
            Flux::toast(variant: 'danger', text: __('Die Demo-Funktion ist plattformweit gesperrt.'));

            return;
        }

        Artisan::call('db:seed', [
            '--class' => DemoDataSeeder::class,
            '--force' => true,
        ]);

        // Passwörter immer auf den dokumentierten Demo-Wert zurücksetzen,
        // weil DemoDataSeeder::firstOrCreate vorhandene User unangetastet lässt.
        foreach ($this->demoLogins as $email => $password) {
            $u = User::where('email', $email)->first();
            if ($u) {
                $u->forceFill(['password' => Hash::make($password)])->save();
            }
        }

        unset($this->demoState);
        Flux::toast(variant: 'success', text: __('Demo-Daten angelegt.'));
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Diese Aktionen ändern Daten unwiderruflich.') }}
    </div>

    <div class="mb-6">
        <flux:heading size="xl">{{ __('Demo-Firma') }}</flux:heading>
        <flux:subheading>
            {{ __('Demo-Daten für') }} <code>{{ $demoEmail }}</code> {{ __('löschen oder neu anlegen.') }}
        </flux:subheading>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="base">{{ __('Aktueller Stand') }}</flux:heading>
        @if ($this->demoState['user'])
            <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Owner / Super-Admin') }}</flux:text>
                    <div>{{ $this->demoState['user']->name }} ({{ $this->demoState['user']->email }})</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Team-Admin') }}</flux:text>
                    <div>
                        @if ($this->demoState['secondary'])
                            {{ $this->demoState['secondary']->name }} ({{ $this->demoState['secondary']->email }})
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Team') }}</flux:text>
                    <div>{{ $this->demoState['team']?->name ?? '—' }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Firma') }}</flux:text>
                    <div>{{ $this->demoState['company']?->name ?? '—' }}</div>
                </div>
                <div class="sm:col-span-2">
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Bestand') }}</flux:text>
                    <div>{{ $this->demoState['employee_count'] }} {{ __('Mitarbeiter') }} · {{ $this->demoState['system_count'] }} {{ __('Systeme') }}</div>
                </div>
            </div>
        @else
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Es existiert aktuell keine Demo-Firma.') }}
            </flux:text>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base">{{ __('Demo-Daten anlegen') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Legt zwei Demo-Logins an – max@mustermann.de (Passwort: password, Owner/Super-Admin) und maxigreis@icloud.com (Passwort: passworD321!1, Team-Admin) – sowie die Musterfirma GmbH mit Stammdaten, Systemen, Szenarien, Übungen und Notfallressourcen. Idempotent – Passwörter werden bei jedem Lauf zurückgesetzt.') }}
            </flux:text>
            <div class="mt-4">
                <flux:button type="button" variant="primary" icon="sparkles" wire:click="seed">
                    {{ __('Demo-Daten anlegen / aktualisieren') }}
                </flux:button>
            </div>
        </div>

        <div class="rounded-xl border border-rose-200 bg-rose-50/30 p-5 dark:border-rose-900 dark:bg-rose-950/30">
            <flux:heading size="base">{{ __('Demo-Daten löschen') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Löscht beide Demo-Nutzer (max@mustermann.de + maxigreis@icloud.com) und alle dazugehörigen Daten (Team, Firma, Mitarbeiter, Systeme, Szenarien, Übungen, Audit-Log) unwiderruflich. Andere Mandanten bleiben unberührt.') }}
            </flux:text>
            <div class="mt-4">
                <flux:button
                    type="button"
                    variant="danger"
                    icon="trash"
                    wire:click="confirmWipe"
                    :disabled="! $this->demoState['user']"
                >
                    {{ __('Demo-Daten löschen') }}
                </flux:button>
            </div>
        </div>
    </div>

    <flux:modal name="demo-wipe" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Demo-Daten wirklich löschen?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Entfernt beide Demo-Nutzer (max@mustermann.de + maxigreis@icloud.com) samt Team, Firma und allen Detaildaten endgültig (Hard Delete). Andere Mandanten sind nicht betroffen.') }}
                </flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="danger" wire:click="wipe">{{ __('Endgültig löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
