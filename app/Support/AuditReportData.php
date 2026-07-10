<?php

namespace App\Support;

use App\Models\BusinessProcess;
use App\Models\Company;
use App\Models\InsurancePolicy;
use App\Models\OpenItem;
use App\Models\PreventiveMeasure;
use App\Models\Risk;
use Illuminate\Support\Carbon;

/**
 * Sammelt die Daten für den Audit-/Governance-Bericht: prozesszentrisch die
 * vollständige BIA je Geschäftsprozess samt verknüpfter Risiken, Maßnahmen und
 * Offener Punkte — plus einen Anhang mit (noch) nicht zugeordneten Einträgen,
 * damit im Bericht nichts verloren geht.
 */
class AuditReportData
{
    /**
     * @return array<string, mixed>
     */
    public static function forCompany(Company $company, ?Carbon $generatedAt = null): array
    {
        $processes = BusinessProcess::with([
            'systems',
            'responsible',
            'responsibleRole',
            'risks.owner',
            'preventiveMeasures.responsible',
            'preventiveMeasures.responsibleRole',
            'openItems.responsible',
            'openItems.responsibleRole',
        ])
            ->where('company_id', $company->id)
            ->orderByRaw("CASE criticality WHEN 'existenzkritisch' THEN 0 WHEN 'hoch' THEN 1 WHEN 'mittel' THEN 2 ELSE 3 END")
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return [
            'company' => $company,
            'processes' => $processes,
            'unlinkedRisks' => Risk::where('company_id', $company->id)->whereNull('business_process_id')->orderBy('title')->get(),
            'unlinkedMeasures' => PreventiveMeasure::with(['responsible', 'responsibleRole'])->where('company_id', $company->id)->whereNull('business_process_id')->orderBy('title')->get(),
            'unlinkedOpenItems' => OpenItem::with(['responsible', 'responsibleRole'])->where('company_id', $company->id)->whereNull('business_process_id')->orderBy('title')->get(),
            'insurancePolicies' => InsurancePolicy::with(['responsibleRole', 'scenarios'])->where('company_id', $company->id)->orderBy('type')->orderBy('insurer')->get(),
            'generatedAt' => $generatedAt ?? now(),
        ];
    }
}
