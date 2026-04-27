<?php

namespace App\Support\Incident;

use App\Models\Company;
use App\Models\ScenarioRun;
use Illuminate\Support\Collection;

/**
 * Schnappschuss aller für das Krisen-Cockpit benötigten Daten.
 *
 * Wird vom Cockpit-Service zusammengestellt; ist `activeRun` null,
 * läuft gerade kein Notfall — die View zeigt dann eine
 * „nichts zu tun"-Ansicht.
 *
 * @phpstan-type StaffMember array{role: \App\Enums\CrisisRole, role_label: string, main: ?\App\Models\Employee, deputies: Collection<int, \App\Models\Employee>}
 * @phpstan-type RecoveryItem array{system: \App\Models\System, level_name: ?string, level_sort: ?int, rto_minutes: ?int, deadline_at: ?\Illuminate\Support\Carbon, depth: int, open_tasks: int, total_tasks: int}
 * @phpstan-type ObligationItem array{report: \App\Models\IncidentReport, obligation: \App\Models\IncidentReportObligation, deadline_at: ?\Illuminate\Support\Carbon, reported: bool, label: string}
 */
class CockpitData
{
    /**
     * @param  list<StaffMember>  $crisisStaff
     * @param  list<RecoveryItem>  $recoveryOrder
     * @param  Collection<int, \App\Models\ScenarioRunStep>  $steps
     * @param  Collection<int, \App\Models\CommunicationTemplate>  $communicationTemplates
     * @param  list<ObligationItem>  $obligations
     */
    public function __construct(
        public readonly Company $company,
        public readonly ?ScenarioRun $activeRun,
        public readonly array $crisisStaff,
        public readonly array $recoveryOrder,
        public readonly Collection $steps,
        public readonly Collection $communicationTemplates,
        public readonly array $obligations,
    ) {}

    public function hasActiveRun(): bool
    {
        return $this->activeRun !== null;
    }
}
