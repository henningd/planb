<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\Settings\CompanySetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:cleanup-audit-log')]
#[Description('Löscht Aktivitäts- und Anmelde-Protokoll-Einträge älter als die pro Mandant hinterlegte Aufbewahrung (audit_retention_days, Standard 30, maximal 360 Tage).')]
class CleanupAuditLog extends Command
{
    /**
     * Hard cap on retention, regardless of the configured value.
     */
    private const MAX_RETENTION_DAYS = 360;

    private const DEFAULT_RETENTION_DAYS = 30;

    public function handle(): int
    {
        $companies = Company::withoutGlobalScope(CurrentCompanyScope::class)->get();
        $totalDeleted = 0;

        foreach ($companies as $company) {
            $days = (int) CompanySetting::for($company)->get('audit_retention_days', self::DEFAULT_RETENTION_DAYS);
            if ($days <= 0) {
                $days = self::DEFAULT_RETENTION_DAYS;
            }
            $days = min(self::MAX_RETENTION_DAYS, $days);

            $cutoff = now()->subDays($days);

            $deleted = 0;
            foreach (['audit_log_entries', 'auth_activity_log'] as $table) {
                $deleted += DB::table($table)
                    ->where('company_id', $company->id)
                    ->where('created_at', '<', $cutoff)
                    ->delete();
            }

            if ($deleted > 0) {
                $this->info("[{$company->name}] {$deleted} Einträge < {$cutoff->toDateTimeString()} gelöscht.");
                $totalDeleted += $deleted;
            }
        }

        $this->info("Fertig. Insgesamt {$totalDeleted} Einträge entfernt.");

        return self::SUCCESS;
    }
}
