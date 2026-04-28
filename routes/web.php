<?php

use App\Http\Controllers\AuditLogExportController;
use App\Http\Controllers\BackupController;
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
use App\Support\Manual\ManualCatalog;
use App\Support\Manual\ManualRenderer;
use App\Support\Marketing\FeatureCatalog;
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

Route::get('/impressum', function () {
    return view('legal-page', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Impressum'),
        'content' => (string) SystemSetting::get('platform_imprint'),
        'emptyHint' => __('Hier erscheinen die Pflichtangaben nach §5 TMG (Anbieter, Anschrift, Vertretungsberechtigte, Kontakt, Registereintrag, USt-IdNr).'),
        'settingKey' => 'platform_imprint',
    ]);
})->name('legal.imprint');

Route::get('/datenschutz', function () {
    return view('legal-page', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Datenschutzerklärung'),
        'content' => (string) SystemSetting::get('platform_privacy'),
        'emptyHint' => __('Hier erscheint die DSGVO-Datenschutzerklärung — welche Daten werden verarbeitet, auf welcher Rechtsgrundlage, wie lange, an wen weitergegeben.'),
        'settingKey' => 'platform_privacy',
    ]);
})->name('legal.privacy');

Route::get('/agb', function () {
    return view('legal-page', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Allgemeine Geschäftsbedingungen'),
        'content' => (string) SystemSetting::get('platform_terms'),
        'emptyHint' => __('Hier erscheinen die AGB des Plattform-Betreibers.'),
        'settingKey' => 'platform_terms',
    ]);
})->name('legal.terms');

Route::get('/funktionen/{slug}', function (string $slug) {
    $feature = FeatureCatalog::find($slug);
    abort_unless($feature !== null, 404);

    return view('feature-detail', [
        'feature' => $feature,
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'canRegister' => Features::enabled(Features::registration())
            && SystemSetting::get('registration_enabled', true),
    ]);
})->where('slug', '[a-z0-9-]+')->name('feature.show');

Route::get('/handbuch', function () {
    return view('manual.index', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'grouped' => ManualCatalog::grouped(),
    ]);
})->name('manual.index');

Route::get('/handbuch/{slug}', function (string $slug) {
    $entry = ManualCatalog::find($slug);
    abort_unless($entry !== null, 404);

    $markdown = ManualCatalog::content($slug);
    abort_unless($markdown !== null, 404);

    $all = ManualCatalog::all();
    $idx = array_search($slug, array_column($all, 'slug'), true);

    return view('manual.show', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'grouped' => ManualCatalog::grouped(),
        'entry' => $entry,
        'currentSlug' => $slug,
        'html' => ManualRenderer::toHtml($markdown),
        'previous' => $idx > 0 ? $all[$idx - 1] : null,
        'next' => $idx < count($all) - 1 ? $all[$idx + 1] : null,
    ]);
})->where('slug', '[a-z0-9-]+')->name('manual.show');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
        Route::livewire('onboarding', 'pages::onboarding.index')->name('onboarding.index');

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
        if (config('features.dependencies')) {
            Route::livewire('dependencies', 'pages::dependencies.index')->name('dependencies.index');
        }
        Route::livewire('tasks-inbox', 'pages::tasks-inbox.index')->name('tasks-inbox.index');
        Route::livewire('recovery-gantt', 'pages::recovery-gantt.index')->name('recovery-gantt.index');
        if (config('features.incident_mode')) {
            Route::livewire('incident-mode', 'pages::incident-mode.index')->name('incident-mode.index');
        }
        Route::livewire('employees', 'pages::employees.index')->name('employees.index');
        Route::livewire('roles', 'pages::roles.index')->name('roles.index');

        Route::livewire('scenarios', 'pages::scenarios.index')->name('scenarios.index');
        Route::livewire('scenarios/{scenario}', 'pages::scenarios.show')->name('scenarios.show');
        Route::livewire('scenario-runs', 'pages::scenario-runs.index')->name('scenario-runs.index');
        Route::livewire('scenario-runs/{run}', 'pages::scenario-runs.show')->name('scenario-runs.show');

        Route::livewire('incidents', 'pages::incidents.index')->name('incidents.index');
        Route::livewire('incidents/{report}', 'pages::incidents.show')->name('incidents.show');

        if (config('features.lessons_learned')) {
            Route::livewire('lessons-learned', 'pages::lessons-learned.index')->name('lessons-learned.index');
            Route::livewire('lessons-learned/create', 'pages::lessons-learned.create')->name('lessons-learned.create');
            Route::livewire('lessons-learned/{lesson}', 'pages::lessons-learned.show')
                ->where('lesson', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                ->name('lessons-learned.show');
        }

        if (config('features.risk_register')) {
            Route::middleware([EnsureTeamMembership::class.':admin'])->group(function () {
                Route::livewire('risks', 'pages::risks.index')->name('risks.index');
                Route::livewire('risks/create', 'pages::risks.create')->name('risks.create');
                Route::livewire('risks/{risk}', 'pages::risks.show')
                    ->where('risk', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                    ->name('risks.show');
            });
        }

        Route::middleware([EnsureTeamMembership::class.':admin'])->group(function () {
            Route::livewire('insurance-policies', 'pages::insurance-policies.index')->name('insurance-policies.index');
            Route::livewire('communication-templates', 'pages::communication-templates.index')->name('communication-templates.index');
            Route::livewire('audit-log', 'pages::audit-log.index')->name('audit-log.index');
            Route::get('handbook-export/audit-log.csv', [AuditLogExportController::class, 'csv'])
                ->name('audit-log.export.csv');
            Route::get('handbook-export/audit-log.pdf', [AuditLogExportController::class, 'pdf'])
                ->name('audit-log.export.pdf');
            Route::livewire('handbook-shares', 'pages::handbook-shares.index')->name('handbook-shares.index');
            Route::livewire('system-settings', 'pages::system-settings.index')->name('system-settings.index');
            Route::livewire('branding', 'pages::branding.index')->name('branding.index');
            Route::get('system-settings/backup', [BackupController::class, 'download'])->name('system-settings.backup.download');
            Route::get('system-settings/archive', [BackupController::class, 'archive'])->name('system-settings.archive.download');
            Route::livewire('handbook-versions', 'pages::handbook-versions.index')->name('handbook-versions.index');
            Route::get('handbook-versions/{version}/pdf', HandbookVersionPdfController::class)
                ->where('version', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                ->name('handbook-versions.pdf');
            Route::livewire('handbook-tests', 'pages::handbook-tests.index')->name('handbook-tests.index');
            if (config('features.compliance')) {
                Route::livewire('compliance', 'pages::compliance.index')->name('compliance.index');
            }
            if (config('features.monitoring_api')) {
                Route::livewire('api-tokens', 'pages::api-tokens.index')->name('api-tokens.index');
            }
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
                'url' => route('systems.show', ['current_team' => $currentTeam, 'system' => $systemModel->id]),
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
        Route::livewire('industry-templates', 'pages::admin.industry-templates.index')->name('industry-templates.index');
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
