<?php

namespace App\Support;

use App\Models\Company;
use App\Models\HandbookShare;
use App\Models\ServiceProvider;
use App\Scopes\CurrentCompanyScope;

/**
 * Collects the data needed to render the printable handbook view, so it can
 * be used both by the internal print route and the read-only share route.
 */
class HandbookData
{
    /**
     * @return array<string, mixed>
     */
    public static function forCompany(Company $company, ?HandbookShare $share = null): array
    {
        $company->loadMissing([
            'contacts' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('name'),
            'emergencyLevels',
            'systems.priority',
            'systems.serviceProviders',
            'systems.dependencies',
            'systemPriorities',
            'scenarios.steps',
            'communicationTemplates.scenario',
            'insurancePolicies',
        ]);

        $providersQuery = ServiceProvider::with('systems')
            ->where('company_id', $company->id)
            ->orderBy('name');

        if ($share !== null) {
            $providersQuery->withoutGlobalScope(CurrentCompanyScope::class);
        }

        return [
            'company' => $company,
            'providers' => $providersQuery->get(),
            'recoveryPlan' => RecoveryOrder::compute($company->systems),
            'share' => $share,
        ];
    }
}
