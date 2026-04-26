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
#[Description('Löscht Audit-Log-Einträge älter als die in den Mandanten-Settings hinterlegte Aufbewahrung (audit_retention_days). 0 = unbegrenzt.')]
class CleanupAuditLog extends Command
{
    public function handle(): int
    {
        $companies = Company::withoutGlobalScope(CurrentCompanyScope::class)->get();
        $totalDeleted = 0;

        foreach ($companies as $company) {
            $days = (int) CompanySetting::for($company)->get('audit_retention_days', 0);
            if ($days <= 0) {
                continue;
            }

            $cutoff = now()->subDays($days);

            $deleted = DB::table('audit_log_entries')
                ->where('company_id', $company->id)
                ->where('created_at', '<', $cutoff)
                ->delete();

            if ($deleted > 0) {
                $this->info("[{$company->name}] {$deleted} Einträge < {$cutoff->toDateTimeString()} gelöscht.");
                $totalDeleted += $deleted;
            }
        }

        $this->info("Fertig. Insgesamt {$totalDeleted} Einträge entfernt.");

        return self::SUCCESS;
    }
}
