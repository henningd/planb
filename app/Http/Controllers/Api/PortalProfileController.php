<?php

namespace App\Http\Controllers\Api;

use App\Enums\CrisisRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InsurancePolicy;
use App\Models\SystemTask;
use App\Scopes\CurrentCompanyScope;
use App\Support\Settings\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stub-Endpoint für die zukünftige planb-portal-Integration.
 *
 * Liefert ein kuratiertes Profil-Snapshot pro Mandant — niemals Mitarbeiterdaten,
 * keine Notfall-Pläne, kein Audit-Log. Nur Aggregat-Status, der dem Portal beim
 * Empfehlen passender Anbieter helfen soll.
 *
 * Auth: Bearer-Token im Authorization-Header. Server hashed das übermittelte
 * Token mit SHA-256 und sucht eine Company mit passendem `portal_api_token_hash`.
 */
class PortalProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['error' => 'missing_token'], 401);
        }

        $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('portal_api_token_hash', hash('sha256', $token))
            ->first();

        if (! $company) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        if (! $this->portalLinkEnabled($company)) {
            return response()->json(['error' => 'portal_link_disabled'], 403);
        }

        $company->forceFill(['portal_link_last_used_at' => now()])->save();

        return response()->json($this->buildProfile($company));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProfile(Company $company): array
    {
        $crisisRoles = [];
        foreach (CrisisRole::cases() as $role) {
            $crisisRoles[$role->value] = [
                'label' => $role->label(),
                'has_main' => $company->crisisRoleHolder($role) !== null,
                'has_deputy' => $company->crisisRoleHolder($role, true) !== null,
            ];
        }

        $insuredTypes = InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->pluck('type')
            ->map(fn ($t) => $t->value)
            ->unique()
            ->values();

        $openTaskCount = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNull('completed_at')
            ->count();

        $overdueTaskCount = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNull('completed_at')
            ->whereDate('due_date', '<', now())
            ->count();

        return [
            'stub' => true,
            'note' => 'Phase-4-Endpunkt — Vertrag steht, Inhalte können wachsen.',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'industry' => $company->industry?->value,
                'employee_count' => $company->employee_count,
                'locations_count' => $company->locations_count,
            ],
            'crisis_roles' => $crisisRoles,
            'insured_types' => $insuredTypes,
            'tasks' => [
                'open' => $openTaskCount,
                'overdue' => $overdueTaskCount,
            ],
        ];
    }

    private function portalLinkEnabled(Company $company): bool
    {
        $tenant = CompanySetting::for($company);

        return (bool) $tenant->get('portal_link_enabled', false);
    }
}
