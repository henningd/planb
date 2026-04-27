<?php

namespace App\Support\Compliance;

use App\Models\Company;
use App\Models\ComplianceScoreSnapshot;
use App\Scopes\CurrentCompanyScope;

class Snapshotter
{
    /**
     * Erstellt oder aktualisiert den heutigen Snapshot für die übergebene Firma.
     */
    public static function snapshot(Company $company): ComplianceScoreSnapshot
    {
        $report = Evaluator::for($company);

        $breakdown = [];
        foreach ($report->items as $entry) {
            $breakdown[] = [
                'key' => $entry['check']->key,
                'status' => $entry['result']->status->value,
                'score' => $entry['result']->score,
            ];
        }

        return ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)
            ->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'snapshot_date' => today(),
                ],
                [
                    'score' => $report->score(),
                    'breakdown' => $breakdown,
                ],
            );
    }

    /**
     * Iteriert über alle Mandanten und persistiert je einen Snapshot für heute.
     *
     * @return int Anzahl verarbeiteter Companies
     */
    public static function snapshotAll(): int
    {
        $companies = Company::withoutGlobalScopes()->get();

        foreach ($companies as $company) {
            self::snapshot($company);
        }

        return $companies->count();
    }
}
