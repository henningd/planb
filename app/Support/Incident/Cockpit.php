<?php

namespace App\Support\Incident;

use App\Models\Company;
use App\Models\ScenarioRun;
use App\Support\Settings\CompanySetting;
use Illuminate\Support\Collection;

/**
 * Liefert ein {@see CockpitData}-Snapshot für das Krisen-Cockpit eines
 * Mandanten. Erkennt aktive ScenarioRuns und stellt für jede der fünf
 * Cockpit-Sektionen die nötigen Daten zusammen.
 *
 * Voll-Implementierung der Sektions-Aggregatoren liegt im
 * Detail-Worktree-Agenten — diese Klasse ist der Einsprungpunkt mit
 * stabilem Vertrag.
 */
class Cockpit
{
    public static function for(Company $company): CockpitData
    {
        $activeRun = self::activeRun($company);

        return new CockpitData(
            company: $company,
            activeRun: $activeRun,
            crisisStaff: self::crisisStaff($company),
            recoveryOrder: self::recoveryOrder($company),
            steps: $activeRun ? $activeRun->steps()->orderBy('sort')->get() : collect(),
            communicationTemplates: self::communicationTemplates($company),
            obligations: self::obligations($company, $activeRun),
        );
    }

    /**
     * Ist der Modus für diese Firma aktiv (Setting + globaler Feature-Flag)?
     */
    public static function isEnabledFor(Company $company): bool
    {
        if (! config('features.incident_mode')) {
            return false;
        }

        return (bool) CompanySetting::for($company)->get('incident_mode_enabled', true);
    }

    private static function activeRun(Company $company): ?ScenarioRun
    {
        return ScenarioRun::query()
            ->where('company_id', $company->id)
            ->whereNull('ended_at')
            ->whereNull('aborted_at')
            ->with('scenario')
            ->orderByDesc('started_at')
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function crisisStaff(Company $company): array
    {
        // Stub — wird vom Service-Agent gefüllt.
        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function recoveryOrder(Company $company): array
    {
        // Stub — wird vom Service-Agent gefüllt.
        return [];
    }

    /**
     * @return Collection<int, \App\Models\CommunicationTemplate>
     */
    private static function communicationTemplates(Company $company): Collection
    {
        // Stub — wird vom Service-Agent gefüllt.
        return collect();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function obligations(Company $company, ?ScenarioRun $run): array
    {
        // Stub — wird vom Service-Agent gefüllt.
        return [];
    }
}
