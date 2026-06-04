<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\Settings\CompanySetting;
use App\Support\Settings\SettingsCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:cleanup-audit-log')]
#[Description('Löscht Aktivitäts- und Anmelde-Protokoll-Einträge älter als die pro Mandant hinterlegte Aufbewahrung (audit_retention_days, Standard 30, maximal 360 Tage).')]
class CleanupAuditLog extends Command
{
    /**
     * Catalog key that is the single source of truth for the retention
     * default and the hard cap. See {@see SettingsCatalog::all()}.
     */
    private const RETENTION_KEY = 'audit_retention_days';

    /**
     * Rows deleted per statement to avoid long table locks and binlog bloat
     * on large audit tables.
     */
    private const DELETE_CHUNK = 2000;

    public function handle(): int
    {
        $definition = SettingsCatalog::definition(self::RETENTION_KEY);
        $defaultDays = (int) ($definition['default'] ?? 30);
        $maxDays = (int) ($definition['max'] ?? 360);

        $totalDeleted = 0;

        Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->cursor()
            ->each(function (Company $company) use ($defaultDays, $maxDays, &$totalDeleted): void {
                $days = (int) CompanySetting::for($company)->get(self::RETENTION_KEY, $defaultDays);
                if ($days <= 0) {
                    $days = $defaultDays;
                }
                $days = min($maxDays, $days);

                $cutoff = now()->subDays($days);

                $deleted = 0;
                foreach (['audit_log_entries', 'auth_activity_log'] as $table) {
                    $deleted += $this->deleteInChunks($table, $company->id, $cutoff->toDateTimeString());
                }

                if ($deleted > 0) {
                    $this->info("[{$company->name}] {$deleted} Einträge < {$cutoff->toDateTimeString()} gelöscht.");
                    $totalDeleted += $deleted;
                }
            });

        $this->info("Fertig. Insgesamt {$totalDeleted} Einträge entfernt.");

        return self::SUCCESS;
    }

    /**
     * Delete matching rows in bounded batches and return the total removed.
     */
    private function deleteInChunks(string $table, string $companyId, string $cutoff): int
    {
        $deleted = 0;

        do {
            $batch = DB::table($table)
                ->where('company_id', $companyId)
                ->where('created_at', '<', $cutoff)
                ->limit(self::DELETE_CHUNK)
                ->delete();

            $deleted += $batch;
        } while ($batch > 0);

        return $deleted;
    }
}
