<?php

namespace App\Support\Tenant;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Setzt einen Mandanten „auf Anfang" zurück: löscht ALLE Mandanten-Daten und
 * legt die Firma mit unverändertem Profil neu an — App-Benutzer und Team
 * bleiben unberührt.
 *
 * Umsetzung bewusst über `forceDelete()` der Firma: alle mandantenbezogenen
 * Tabellen hängen per `ON DELETE CASCADE` an `companies`, sodass die Datenbank
 * sämtliche Datensätze (inkl. id-loser Pivots) in einem Rutsch entfernt — ohne
 * fehleranfällige Tabellen-Aufzählung. Da `Company` SoftDeletes nutzt, muss es
 * ein Hard-Delete sein, damit die Cascade greift. Das anschließende `create()`
 * löst den `CompanyObserver` aus, der frische Default-Stammdaten (Notfall-Level,
 * System-Prioritäten, globale Szenarien, Krisenrollen) seedet — wie bei einer
 * neuen Firma. Ein nicht vorhandener Onboarding-Status startet den Assistenten
 * automatisch von vorn.
 */
class CompanyReset
{
    /**
     * Profil-Felder, die beim Zurücksetzen erhalten bleiben. Operative Felder
     * (Review-/Reminder-Zeitpunkte, Portal-/API-Token) werden bewusst NICHT
     * übernommen und damit ebenfalls zurückgesetzt.
     *
     * @var list<string>
     */
    private const KEEP_PROFILE_FIELDS = [
        'team_id',
        'name',
        'display_name',
        'logo_path',
        'primary_color',
        'industry',
        'legal_form',
        'kritis_relevant',
        'nis2_classification',
        'valid_from',
        'cyber_insurance_deductible',
        'budget_it_lead',
        'budget_emergency_officer',
        'budget_management',
        'data_protection_authority_name',
        'data_protection_authority_phone',
        'data_protection_authority_website',
        'employee_count',
        'review_cycle_months',
    ];

    public static function run(Company $company): Company
    {
        // Aus einem frischen DB-Read kopieren, damit auch DB-Defaults geladen
        // sind; Null-Werte herausfiltern, damit NOT-NULL-Spalten erneut auf
        // ihren Default fallen statt mit null zu kollidieren.
        $profile = collect(($company->fresh() ?? $company)->only(self::KEEP_PROFILE_FIELDS))
            ->reject(fn ($value) => $value === null)
            ->all();

        return DB::transaction(function () use ($company, $profile): Company {
            $company->forceDelete();

            return Company::create($profile);
        });
    }
}
