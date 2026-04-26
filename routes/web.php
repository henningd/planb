<?php

use App\Http\Controllers\HandbookVersionPdfController;
use App\Http\Controllers\PreferenceController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Middleware\SetTeamUrlDefaults;
use App\Models\Company;
use App\Models\HandbookShare;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;
use App\Support\CurrentCompany;
use App\Support\HandbookData;
use App\Support\Settings\SystemSetting;
use App\Support\SystemImport;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome', [
        'canRegister' => Features::enabled(Features::registration())
            && SystemSetting::get('registration_enabled', true),
    ]);
})->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

        Route::livewire('company', 'pages::company.edit')->name('company.edit');
        Route::livewire('locations', 'pages::locations.index')->name('locations.index');
        Route::livewire('emergency-levels', 'pages::emergency-levels.index')->name('emergency-levels.index');
        Route::livewire('systems', 'pages::systems.index')->name('systems.index');
        Route::livewire('systems/create', 'pages::systems.edit')->name('systems.create');
        Route::livewire('systems/recovery', 'pages::systems.recovery')->name('systems.recovery');
        Route::livewire('systems/{system}', 'pages::systems.show')
            ->where('system', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('systems.show');
        Route::livewire('systems/{system}/edit', 'pages::systems.edit')
            ->where('system', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('systems.edit');
        Route::livewire('service-providers', 'pages::service-providers.index')->name('service-providers.index');
        Route::livewire('emergency-resources', 'pages::emergency-resources.index')->name('emergency-resources.index');
        Route::livewire('employees', 'pages::employees.index')->name('employees.index');
        Route::livewire('roles', 'pages::roles.index')->name('roles.index');

        Route::livewire('scenarios', 'pages::scenarios.index')->name('scenarios.index');
        Route::livewire('scenarios/{scenario}', 'pages::scenarios.show')->name('scenarios.show');
        Route::livewire('scenario-runs', 'pages::scenario-runs.index')->name('scenario-runs.index');
        Route::livewire('scenario-runs/{run}', 'pages::scenario-runs.show')->name('scenario-runs.show');

        Route::livewire('incidents', 'pages::incidents.index')->name('incidents.index');
        Route::livewire('incidents/{report}', 'pages::incidents.show')->name('incidents.show');

        Route::middleware([EnsureTeamMembership::class.':admin'])->group(function () {
            Route::livewire('insurance-policies', 'pages::insurance-policies.index')->name('insurance-policies.index');
            Route::livewire('communication-templates', 'pages::communication-templates.index')->name('communication-templates.index');
            Route::livewire('audit-log', 'pages::audit-log.index')->name('audit-log.index');
            Route::livewire('handbook-shares', 'pages::handbook-shares.index')->name('handbook-shares.index');
            Route::livewire('system-settings', 'pages::system-settings.index')->name('system-settings.index');
            Route::livewire('handbook-versions', 'pages::handbook-versions.index')->name('handbook-versions.index');
            Route::get('handbook-versions/{version}/pdf', HandbookVersionPdfController::class)
                ->where('version', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                ->name('handbook-versions.pdf');
            Route::livewire('handbook-tests', 'pages::handbook-tests.index')->name('handbook-tests.index');
        });

        Route::get('systems/export', function () {
            $company = CurrentCompany::resolve();
            abort_unless($company, 404);

            $systems = System::with('priority')
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->map(fn (System $s) => [
                    'name' => $s->name,
                    'description' => $s->description,
                    'category' => $s->category->value,
                    'priority' => $s->priority?->name,
                    'rto_minutes' => $s->rto_minutes,
                    'rpo_minutes' => $s->rpo_minutes,
                    'downtime_cost_per_hour' => $s->downtime_cost_per_hour,
                ])
                ->values()
                ->all();

            $payload = [
                'version' => SystemImport::CURRENT_VERSION,
                'exported_at' => now()->toIso8601String(),
                'company' => $company->name,
                'systems' => $systems,
            ];

            $filename = 'planb-systeme-'.$company->team->slug.'-'.now()->format('Y-m-d').'.json';

            return response()->streamDownload(
                fn () => print (json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
                $filename,
                ['Content-Type' => 'application/json'],
            );
        })->name('systems.export');

        Route::get('handbook/print', function () {
            $company = CurrentCompany::resolve();
            abort_unless($company, 404);

            return view('handbook-print', HandbookData::forCompany($company));
        })->name('handbook.print');

        Route::get('systems/{system}/sticker', function (string $currentTeam, string $system) {
            $systemModel = System::with(['priority', 'serviceProviders', 'dependencies'])
                ->findOrFail($system);

            return view('system-sticker', [
                'system' => $systemModel,
                'url' => route('systems.sticker', ['current_team' => $currentTeam, 'system' => $systemModel->id]),
            ]);
        })->name('systems.sticker');
    });

Route::prefix('admin')
    ->middleware(['auth', 'verified', SetTeamUrlDefaults::class, EnsureSuperAdmin::class])
    ->name('admin.')
    ->group(function () {
        Route::livewire('/', 'pages::admin.index')->name('index');
        Route::livewire('companies', 'pages::admin.companies.index')->name('companies.index');
        Route::livewire('scenarios', 'pages::admin.scenarios.index')->name('scenarios.index');
        Route::livewire('scenarios/{globalScenario}', 'pages::admin.scenarios.show')->name('scenarios.show');
        Route::livewire('demo', 'pages::admin.demo.index')->name('demo.index');
        Route::livewire('settings/system', 'pages::admin.settings.system.index')->name('settings.system.index');
    });

Route::middleware(['auth'])->group(function () {
    Route::patch('preferences/sidebar-group', [PreferenceController::class, 'updateSidebarGroup'])
        ->name('preferences.sidebar-group');
});

Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');

Route::get('shared-handbook/{token}', function (string $token) {
    $share = HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('token', $token)
        ->first();

    if (! $share) {
        abort(404);
    }

    if ($share->revoked_at !== null) {
        return response()->view('handbook-share-inactive', [
            'reason' => 'revoked',
        ], 410);
    }

    if ($share->expires_at->isPast()) {
        return response()->view('handbook-share-inactive', [
            'reason' => 'expired',
        ], 410);
    }

    $company = Company::findOrFail($share->company_id);

    $share->forceFill([
        'last_accessed_at' => now(),
        'access_count' => $share->access_count + 1,
    ])->save();

    return view('handbook-print', HandbookData::forCompany($company, $share));
})->name('handbook.shared');

require __DIR__.'/settings.php';
