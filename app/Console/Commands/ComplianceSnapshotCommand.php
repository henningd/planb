<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\Compliance\Snapshotter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('compliance:snapshot {--company= : ID einer einzelnen Firma}')]
#[Description('Erstellt Compliance-Score-Snapshots für alle Mandanten (oder eine konkrete Firma)')]
class ComplianceSnapshotCommand extends Command
{
    public function handle(): int
    {
        $companyId = $this->option('company');

        if ($companyId) {
            $company = Company::withoutGlobalScope(CurrentCompanyScope::class)->find($companyId);
            if (! $company) {
                $this->error("Firma mit ID {$companyId} nicht gefunden.");

                return self::FAILURE;
            }

            Snapshotter::snapshot($company);

            $this->info('1 Snapshot(s) erstellt/aktualisiert.');

            return self::SUCCESS;
        }

        $count = Snapshotter::snapshotAll();

        $this->info("{$count} Snapshot(s) erstellt/aktualisiert.");

        return self::SUCCESS;
    }
}
