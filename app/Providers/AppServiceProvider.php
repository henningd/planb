<?php

namespace App\Providers;

use App\Support\Settings\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->applyPlatformOverrides();
    }

    /**
     * Setzt config('app.name') zur Laufzeit auf den Wert aus den
     * Systemeinstellungen, sofern dort ein Override hinterlegt ist.
     * Greift nicht während der Migration (Tabelle existiert noch nicht).
     */
    protected function applyPlatformOverrides(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $name = SystemSetting::get('platform_name', '');
        if (filled($name)) {
            config(['app.name' => $name]);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // In production, destruktive DB-Kommandos (migrate:fresh, wipe, etc.)
        // sind standardmäßig gesperrt. Während der Stabilisierungsphase kann
        // die Sperre per ENV `ALLOW_DESTRUCTIVE_DB=true` gezielt aufgehoben
        // werden.
        DB::prohibitDestructiveCommands(
            app()->isProduction() && ! env('ALLOW_DESTRUCTIVE_DB', false),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
