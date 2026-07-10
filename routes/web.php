<?php

use App\Http\Controllers\AuditLogExportController;
use App\Http\Controllers\AuthActivityExportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CrisisLogExportController;
use App\Http\Controllers\DrillReportPdfController;
use App\Http\Controllers\EmergencyCardController;
use App\Http\Controllers\HandbookExportController;
use App\Http\Controllers\HandbookVersionPdfController;
use App\Http\Controllers\LeadConfirmationController;
use App\Http\Controllers\PreferenceController;
use App\Http\Middleware\EnforceBillingState;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Middleware\SetTeamUrlDefaults;
use App\Models\Company;
use App\Models\HandbookShare;
use App\Models\Location;
use App\Models\Role;
use App\Models\Scenario;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;
use App\Support\AuditReportData;
use App\Support\CurrentCompany;
use App\Support\HandbookData;
use App\Support\Manual\ManualCatalog;
use App\Support\Manual\ManualRenderer;
use App\Support\Marketing\FeatureCatalog;
use App\Support\Marketing\GuideCatalog;
use App\Support\Marketing\MarketingUrls;
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

Route::get('/ratgeber', function () {
    return view('guides-index', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'canRegister' => Features::enabled(Features::registration())
            && SystemSetting::get('registration_enabled', true),
    ]);
})->name('guides.index');

// Zielgruppen-Seite: PlanB für Kommunen, Behörden und Eigenbetriebe.
Route::get('/kommunen', function () {
    return view('kommunen', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'canRegister' => Features::enabled(Features::registration())
            && SystemSetting::get('registration_enabled', true),
    ]);
})->name('kommunen.show');

Route::get('/nis2-quick-check', function () {
    return view('nis2-quick-check', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
    ]);
})->name('nis2-quick-check');

Route::get('/nis2-quick-check/bestaetigen/{lead}', LeadConfirmationController::class)
    ->middleware('signed')
    ->name('nis2-quick-check.confirm');

Route::get('/{slug}', function (string $slug) {
    $guide = GuideCatalog::find($slug);
    abort_unless($guide !== null, 404);

    return view('guide', [
        'guide' => $guide,
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'canRegister' => Features::enabled(Features::registration())
            && SystemSetting::get('registration_enabled', true),
    ]);
})->where('slug', implode('|', GuideCatalog::slugs()))->name('guides.show');

// IndexNow-Schlüsseldatei zur Domain-Verifizierung (nur bei gesetztem Key).
if (($indexNowKey = (string) config('services.indexnow.key')) !== '') {
    Route::get('/'.$indexNowKey.'.txt', fn () => response($indexNowKey, 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]))->name('indexnow.key');
}

Route::get('/sitemap.xml', function () {
    $urls = MarketingUrls::all();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    foreach ($urls as $url) {
        $lastmod = isset($url['lastmod']) ? '<lastmod>'.$url['lastmod'].'</lastmod>' : '';
        $xml .= '  <url><loc>'.e($url['loc']).'</loc>'.$lastmod.'<priority>'.$url['priority'].'</priority></url>'."\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

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

Route::get('/auftragsverarbeitung', function () {
    $content = (string) SystemSetting::get('platform_av_contract');

    return view('legal-page-markdown', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Auftragsverarbeitung (AVV)'),
        'content' => $content,
        'html' => ManualRenderer::toHtml($content),
        'emptyHint' => __('Hier erscheint der Vertrag zur Auftragsverarbeitung nach Art. 28 DSGVO.'),
        'settingKey' => 'platform_av_contract',
    ]);
})->name('legal.av_contract');

Route::get('/tom', function () {
    $content = (string) SystemSetting::get('platform_tom');

    return view('legal-page-markdown', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Technische und organisatorische Maßnahmen'),
        'content' => $content,
        'html' => ManualRenderer::toHtml($content),
        'emptyHint' => __('Hier erscheinen die TOM nach Art. 32 DSGVO als Anlage zum AVV.'),
        'settingKey' => 'platform_tom',
    ]);
})->name('legal.tom');

Route::get('/subprocessors', function () {
    $content = (string) SystemSetting::get('platform_subprocessors');

    return view('legal-page-markdown', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Subprocessors / Unterauftragsverarbeiter'),
        'content' => $content,
        'html' => ManualRenderer::toHtml($content),
        'emptyHint' => __('Hier erscheint die Liste der eingesetzten Unterauftragsverarbeiter.'),
        'settingKey' => 'platform_subprocessors',
    ]);
})->name('legal.subprocessors');

Route::get('/barrierefreiheit', function () {
    $content = (string) SystemSetting::get('platform_accessibility');

    return view('legal-page-markdown', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Erklärung zur Barrierefreiheit'),
        'content' => $content,
        'html' => ManualRenderer::toHtml($content),
        'emptyHint' => __('Hier erscheint die Erklärung zur digitalen Barrierefreiheit nach BITV 2.0 / BFSG.'),
        'settingKey' => 'platform_accessibility',
    ]);
})->name('legal.accessibility');

Route::get('/.well-known/security.txt', function () {
    $contact = (string) SystemSetting::get('platform_security_contact');
    $expires = now()->addYear()->format('Y-m-d\TH:i:s\Z');

    $body = "Contact: mailto:{$contact}\n"
        ."Expires: {$expires}\n"
        ."Preferred-Languages: de, en\n"
        .'Canonical: '.url('/.well-known/security.txt')."\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
})->name('legal.security_txt');

Route::get('/status', function () {
    $state = (string) SystemSetting::get('platform_status_state');
    $content = (string) SystemSetting::get('platform_status_incidents');

    /** @var array<string, array{label: string, banner: string, dot: string}> $states */
    $states = [
        'operational' => [
            'label' => __('Alle Systeme funktionieren'),
            'banner' => 'bg-emerald-50 border-emerald-300 text-emerald-900',
            'dot' => 'bg-emerald-500',
        ],
        'degraded' => [
            'label' => __('Eingeschränkt'),
            'banner' => 'bg-amber-50 border-amber-300 text-amber-900',
            'dot' => 'bg-amber-500',
        ],
        'outage' => [
            'label' => __('Störung'),
            'banner' => 'bg-rose-50 border-rose-300 text-rose-900',
            'dot' => 'bg-rose-500',
        ],
        'maintenance' => [
            'label' => __('Wartungsfenster'),
            'banner' => 'bg-sky-50 border-sky-300 text-sky-900',
            'dot' => 'bg-sky-500',
        ],
    ];

    $current = $states[$state] ?? $states['operational'];

    return view('status-page', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'heading' => __('Plattform-Status'),
        'state' => $state,
        'stateLabel' => $current['label'],
        'bannerClasses' => $current['banner'],
        'dotClasses' => $current['dot'],
        'content' => $content,
        'html' => ManualRenderer::toHtml($content),
        'emptyHint' => __('Hier erscheint die Historie der Incidents — Datum, Titel, Status, Beschreibung.'),
        'settingKey' => 'platform_status_incidents',
    ]);
})->name('legal.status');

Route::get('/preise', function () {
    return view('pricing', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'canRegister' => Features::enabled(Features::registration())
            && SystemSetting::get('registration_enabled', true),
    ]);
})->name('pricing.show');

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
    $query = trim((string) request()->query('q', ''));

    return view('manual.index', [
        'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        'grouped' => ManualCatalog::grouped(),
        'searchQuery' => $query,
        'searchResults' => $query !== '' ? ManualCatalog::search($query) : null,
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
    ->middleware(['auth', 'verified', EnsureTeamMembership::class, EnforceBillingState::class])
    ->group(function () {
        Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
        Route::livewire('onboarding', 'pages::onboarding.index')->name('onboarding.index');

        Route::livewire('company', 'pages::company.edit')->name('company.edit');
        Route::livewire('locations', 'pages::locations.index')->name('locations.index');
        Route::livewire('locations/{location}/detail', 'pages::locations.detail')->name('locations.detail');
        if (config('features.departments')) {
            Route::livewire('departments', 'pages::departments.index')->name('departments.index');
        }
        Route::livewire('emergency-levels', 'pages::emergency-levels.index')->name('emergency-levels.index');
        Route::livewire('systems', 'pages::systems.index')->name('systems.index');
        Route::livewire('systems/create', 'pages::systems.edit')->name('systems.create');
        Route::livewire('systems/recovery', 'pages::systems.recovery')->name('systems.recovery');
        Route::livewire('systems/cost-calculator', 'pages::systems.cost-calculator')->name('systems.cost-calculator');
        Route::livewire('systems/{system}', 'pages::systems.show')
            ->where('system', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('systems.show');
        Route::livewire('systems/{system}/edit', 'pages::systems.edit')
            ->where('system', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('systems.edit');
        Route::livewire('service-providers', 'pages::service-providers.index')->name('service-providers.index');
        Route::livewire('service-providers/{provider}', 'pages::service-providers.show')
            ->where('provider', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('service-providers.show');
        if (config('features.contracts')) {
            Route::livewire('contracts', 'pages::contracts.index')->name('contracts.index');
            Route::livewire('contracts/create', 'pages::contracts.edit')->name('contracts.create');
            Route::livewire('contracts/{contract}/edit', 'pages::contracts.edit')
                ->where('contract', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                ->name('contracts.edit');
            Route::livewire('contracts/{contract}', 'pages::contracts.show')
                ->where('contract', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                ->name('contracts.show');
        }
        Route::livewire('emergency-resources', 'pages::emergency-resources.index')->name('emergency-resources.index');
        Route::livewire('notfallressourcen-kategorien', 'pages::emergency-resource-categories.index')->name('emergency-resource-categories.index');
        Route::livewire('fallback-processes', 'pages::fallback-processes.index')->name('fallback-processes.index');
        if (config('features.preventive_measures')) {
            Route::livewire('praevention', 'pages::preventive-measures.index')->name('preventive-measures.index');
        }
        if (config('features.bia')) {
            Route::livewire('geschaeftsprozesse', 'pages::business-processes.index')->name('business-processes.index');
        }
        if (config('features.maturity')) {
            Route::livewire('reifegrad', 'pages::maturity.index')->name('maturity.index');
        }
        if (config('features.supply_chain_risk')) {
            Route::livewire('lieferketten-risiko', 'pages::supplier-risk.index')->name('supplier-risk.index');
        }
        if (config('features.bcm_policy')) {
            Route::livewire('bcm-leitlinie', 'pages::bcm-policy.index')->name('bcm-policy.index');
        }
        if (config('features.management_review')) {
            Route::livewire('management-review', 'pages::management-reviews.index')->name('management-reviews.index');
        }
        if (config('features.open_items')) {
            Route::livewire('offene-punkte', 'pages::open-items.index')->name('open-items.index');
        }
        if (config('features.ai_governance')) {
            Route::livewire('ki-systeme', 'pages::ai-systems.index')->name('ai-systems.index');
            Route::livewire('ki-systeme/klassifizierung', 'pages::ai-systems.classify')->name('ai-systems.classify');
            Route::livewire('ki-systeme/{aiSystem}', 'pages::ai-systems.show')->name('ai-systems.show');
        }
        if (config('features.authority_contacts')) {
            Route::livewire('authority-contacts', 'pages::authority-contacts.index')->name('authority-contacts.index');
        }
        if (config('features.training_records')) {
            Route::livewire('schulungen', 'pages::training-records.index')->name('training-records.index');
        }
        if (config('features.dependencies')) {
            Route::livewire('dependencies', 'pages::dependencies.index')->name('dependencies.index');
        }
        Route::livewire('tasks-inbox', 'pages::tasks-inbox.index')->name('tasks-inbox.index');
        Route::livewire('recovery-gantt', 'pages::recovery-gantt.index')->name('recovery-gantt.index');
        if (config('features.incident_mode')) {
            Route::livewire('incident-mode', 'pages::incident-mode.index')->name('incident-mode.index');
        }
        Route::livewire('employees', 'pages::employees.index')->name('employees.index');
        Route::livewire('employees/create', 'pages::employees.edit')->name('employees.create');
        Route::livewire('employees/{employee}', 'pages::employees.show')
            ->where('employee', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('employees.show');
        Route::livewire('employees/{employee}/edit', 'pages::employees.edit')
            ->where('employee', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('employees.edit');
        Route::middleware('feature:roles')->group(function () {
            Route::livewire('roles', 'pages::roles.index')->name('roles.index');
            Route::livewire('roles/{role}', 'pages::roles.show')
                ->where('role', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                ->name('roles.show');
        });

        Route::livewire('scenarios', 'pages::scenarios.index')->name('scenarios.index');
        Route::livewire('scenarios/{scenario}/detail', 'pages::scenarios.detail')->name('scenarios.detail');
        Route::livewire('scenarios/{scenario}', 'pages::scenarios.show')->name('scenarios.show');
        Route::livewire('scenario-runs', 'pages::scenario-runs.index')->name('scenario-runs.index');
        Route::livewire('scenario-runs/{run}', 'pages::scenario-runs.show')->name('scenario-runs.show');
        Route::get('scenario-runs/{run}/protokoll.pdf', [CrisisLogExportController::class, 'pdf'])->name('scenario-runs.protocol.pdf');

        // Übungsberichte: Auswertung abgeschlossener Drill-Läufe inkl. PDF-Nachweis.
        Route::livewire('uebungsberichte', 'pages::drill-reports.index')->name('drill-reports.index');
        Route::livewire('uebungsberichte/{run}', 'pages::drill-reports.show')
            ->where('run', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('drill-reports.show');
        Route::get('uebungsberichte/{run}/bericht.pdf', DrillReportPdfController::class)
            ->where('run', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('drill-reports.pdf');

        Route::get('notfallkarte.pdf', [EmergencyCardController::class, 'pdf'])->name('emergency-card.pdf');

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
            Route::middleware([EnsureTeamMembership::class.':consultant'])->group(function () {
                Route::livewire('risks', 'pages::risks.index')->name('risks.index');
                Route::livewire('risks/create', 'pages::risks.create')->name('risks.create');
                Route::livewire('risks/{risk}', 'pages::risks.show')
                    ->where('risk', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                    ->name('risks.show');
            });
        }

        // Berater-Ebene: sensible, aber inhaltliche Handbuch-Bereiche, die ein
        // Berater pflegen darf (nicht jedoch Governance wie Audit/Einstellungen).
        Route::middleware([EnsureTeamMembership::class.':consultant'])->group(function () {
            Route::livewire('insurance-policies', 'pages::insurance-policies.index')->name('insurance-policies.index');
            Route::livewire('insurance-policies/{policy}', 'pages::insurance-policies.show')
                ->where('policy', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
                ->name('insurance-policies.show');
            Route::livewire('communication-templates', 'pages::communication-templates.index')->name('communication-templates.index');
        });

        Route::middleware([EnsureTeamMembership::class.':admin'])->group(function () {
            Route::livewire('audit-log', 'pages::audit-log.index')->name('audit-log.index');
            Route::livewire('login-activity', 'pages::login-activity.index')->name('login-activity.index');
            Route::get('handbook-export/login-activity.csv', [AuthActivityExportController::class, 'csv'])
                ->name('login-activity.export.csv');
            Route::get('handbook-export/login-activity.pdf', [AuthActivityExportController::class, 'pdf'])
                ->name('login-activity.export.pdf');
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
            Route::get('handbook-export/ernstfall-handbuch.pdf', [HandbookExportController::class, 'ernstfall'])->name('handbook-export.ernstfall');
            Route::get('handbook-export/audit-bericht.pdf', [HandbookExportController::class, 'audit'])->name('handbook-export.audit');
            Route::get('handbook-export/vollstaendig.pdf', [HandbookExportController::class, 'full'])->name('handbook-export.full');
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

        Route::middleware('feature:roles')->get('roles/export', function () {
            $company = CurrentCompany::resolve();
            abort_unless($company, 404);

            $roles = Role::with([
                'employees',
                'systems' => fn ($q) => $q->orderBy('systems.name'),
                'systemTasks' => fn ($q) => $q->orderBy('system_tasks.title'),
            ])
                ->orderBy('sort')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'name' => $role->name,
                    'description' => $role->description,
                    'sort' => $role->sort,
                    'system_key' => $role->system_key,
                    'is_system_role' => $role->isSystem(),
                    'employees' => $role->employees->map(fn ($e) => [
                        'first_name' => $e->first_name,
                        'last_name' => $e->last_name,
                        'email' => $e->email,
                        'is_deputy' => (bool) ($e->pivot->is_deputy ?? false),
                        'assigned_at' => $e->pivot->assigned_at,
                    ])->values()->all(),
                    'systems' => $role->systems->map(fn ($s) => [
                        'name' => $s->name,
                        'raci_role' => $s->pivot->raci_role,
                        'note' => $s->pivot->note,
                        'sort' => $s->pivot->sort,
                    ])->values()->all(),
                    'system_tasks' => $role->systemTasks->map(fn ($t) => [
                        'title' => $t->title,
                        'system_id' => $t->system_id,
                        'raci_role' => $t->pivot->raci_role,
                        'sort' => $t->pivot->sort,
                    ])->values()->all(),
                ])
                ->values()
                ->all();

            $payload = [
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
                'company' => $company->name,
                'roles' => $roles,
            ];

            $filename = 'planb-rollen-'.$company->team->slug.'-'.now()->format('Y-m-d').'.json';

            return response()->streamDownload(
                fn () => print (json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
                $filename,
                ['Content-Type' => 'application/json'],
            );
        })->name('roles.export');

        Route::get('handbook/print', function () {
            $company = CurrentCompany::resolve();
            abort_unless($company, 404);

            return view('handbook-print', HandbookData::forCompany($company));
        })->name('handbook.print');

        if (config('features.bia')) {
            Route::get('audit-bericht', function () {
                $company = CurrentCompany::resolve();
                abort_unless($company, 404);

                return view('audit-report', AuditReportData::forCompany($company));
            })->name('audit-report.print');
        }

        Route::get('systems/{system}/sticker', function (string $currentTeam, string $system) {
            $systemModel = System::with(['priority', 'serviceProviders', 'dependencies'])
                ->findOrFail($system);

            return view('system-sticker', [
                'system' => $systemModel,
                'url' => route('systems.show', ['current_team' => $currentTeam, 'system' => $systemModel->id]),
            ]);
        })->name('systems.sticker');

        // Druckbarer Notfallaushang je Standort (optional mit festem Szenario).
        // Der QR traegt das Offline-Payload der Notfall-App (Brief 4b):
        // {"planb":"aushang","location":...,"scenario":...,"url":<Fallback>}
        Route::get('locations/{location}/aushang', function (string $currentTeam, string $location) {
            $locationModel = Location::findOrFail($location);

            $scenario = null;
            if (($scenarioId = (string) request()->query('scenario')) !== '') {
                $scenario = Scenario::findOrFail($scenarioId);
            }

            $share = HandbookShare::query()
                ->whereNull('revoked_at')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest('created_at')
                ->first();
            $fallbackUrl = $share !== null ? route('handbook.shared', ['token' => $share->token]) : null;

            $payload = array_filter([
                'planb' => 'aushang',
                'location' => $locationModel->id,
                'scenario' => $scenario?->id,
                'url' => $fallbackUrl,
            ], fn ($v) => $v !== null);

            return view('notfallaushang', [
                'location' => $locationModel,
                'scenario' => $scenario,
                'payloadJson' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'fallbackUrl' => $fallbackUrl,
            ]);
        })->name('locations.aushang');
    });

Route::prefix('admin')
    ->middleware(['auth', 'verified', SetTeamUrlDefaults::class, EnsureSuperAdmin::class])
    ->name('admin.')
    ->group(function () {
        Route::livewire('/', 'pages::admin.index')->name('index');
        Route::livewire('companies', 'pages::admin.companies.index')->name('companies.index');
        Route::livewire('leads', 'pages::admin.leads.index')->name('leads.index');
        Route::livewire('scenarios', 'pages::admin.scenarios.index')->name('scenarios.index');
        Route::livewire('scenarios/{globalScenario}', 'pages::admin.scenarios.show')->name('scenarios.show');
        Route::livewire('demo', 'pages::admin.demo.index')->name('demo.index');
        Route::livewire('industry-templates', 'pages::admin.industry-templates.index')->name('industry-templates.index');
        Route::livewire('data-protection-authorities', 'pages::admin.data-protection-authorities.index')->name('data-protection-authorities.index');
        Route::livewire('data-protection-authorities/{authority}', 'pages::admin.data-protection-authorities.show')
            ->where('authority', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('data-protection-authorities.show');
        Route::livewire('settings/system', 'pages::admin.settings.system.index')->name('settings.system.index');
    });

Route::middleware(['auth'])->group(function () {
    Route::patch('preferences/sidebar-group', [PreferenceController::class, 'updateSidebarGroup'])
        ->name('preferences.sidebar-group');

    // Fester Einstiegspunkt /dashboard: Fortify leitet nach der
    // E-Mail-Verifizierung auf fortify.home (/dashboard); diese App ist aber
    // team-spezifisch (/{current_team}/dashboard). Hier leiten wir auf das
    // Dashboard des aktuellen Teams weiter, statt 404 zu liefern.
    Route::get('dashboard', function () {
        $user = request()->user();
        $team = $user?->currentTeam ?? $user?->personalTeam();

        if ($team === null) {
            abort(403);
        }

        return redirect()->route('dashboard', array_filter([
            'current_team' => $team->slug,
            'verified' => request()->boolean('verified') ? 1 : null,
        ]));
    })->name('dashboard.home');
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
