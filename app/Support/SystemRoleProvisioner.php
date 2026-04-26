<?php

namespace App\Support;

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Role;
use App\Scopes\CurrentCompanyScope;

/**
 * Stellt für jede Firma die fünf System-Rollen (eine pro CrisisRole)
 * sicher. Die Rollen tragen `system_key` = Enum-Value, sind dadurch
 * eindeutig identifizierbar und werden in der UI gegen Löschen geschützt.
 */
class SystemRoleProvisioner
{
    public static function ensureFor(Company $company): void
    {
        foreach (CrisisRole::cases() as $i => $role) {
            Role::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                ['company_id' => $company->id, 'system_key' => $role->value],
                [
                    'name' => $role->label(),
                    'description' => self::descriptionFor($role),
                    'sort' => $i,
                ],
            );
        }
    }

    public static function descriptionFor(CrisisRole $role): string
    {
        return match ($role) {
            CrisisRole::EmergencyOfficer => 'Koordiniert die Notfallreaktion, ruft Krisenstab zusammen, dokumentiert Lage.',
            CrisisRole::ItLead => 'Verantwortlich für IT-seitige Lagebewertung, Eindämmung, Wiederanlauf.',
            CrisisRole::DataProtectionOfficer => 'Bewertet Datenschutz-Implikationen, kümmert sich um Art.-33-DSGVO-Meldungen.',
            CrisisRole::CommunicationsLead => 'Steuert die interne und externe Krisenkommunikation.',
            CrisisRole::Management => 'Trifft Geschäfts-/Budget-Entscheidungen, gibt nach außen frei.',
        };
    }
}
