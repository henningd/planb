<?php

namespace App\Support\Incident;

use App\Enums\CrisisRole;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\IncidentReport;
use App\Models\IncidentReportObligation;
use App\Models\ScenarioRun;
use App\Models\System;
use App\Models\SystemTask;
use App\Support\Settings\CompanySetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Liefert ein {@see CockpitData}-Snapshot für das Krisen-Cockpit eines
 * Mandanten. Erkennt aktive ScenarioRuns und stellt für jede der fünf
 * Cockpit-Sektionen die nötigen Daten zusammen.
 */
class Cockpit
{
    public static function for(Company $company): CockpitData
    {
        $activeRun = self::activeRun($company);

        return new CockpitData(
            company: $company,
            activeRun: $activeRun,
            crisisStaff: self::crisisStaff($company),
            recoveryOrder: self::recoveryOrder($company, $activeRun),
            steps: $activeRun ? $activeRun->steps()->orderBy('sort')->get() : collect(),
            communicationTemplates: self::communicationTemplates($company, $activeRun),
            obligations: self::obligations($company, $activeRun),
        );
    }

    /**
     * Ist der Modus für diese Firma aktiv (Setting + globaler Feature-Flag)?
     */
    public static function isEnabledFor(Company $company): bool
    {
        if (! config('features.incident_mode')) {
            return false;
        }

        return (bool) CompanySetting::for($company)->get('incident_mode_enabled', true);
    }

    private static function activeRun(Company $company): ?ScenarioRun
    {
        return ScenarioRun::query()
            ->where('company_id', $company->id)
            ->whereNull('ended_at')
            ->whereNull('aborted_at')
            ->with('scenario')
            ->orderByDesc('started_at')
            ->first();
    }

    /**
     * Hauptperson + Vertretung pro CrisisRole-Enum.
     *
     * @return list<array{role: CrisisRole, role_label: string, main: ?Employee, deputies: Collection<int, Employee>}>
     */
    private static function crisisStaff(Company $company): array
    {
        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->whereNotNull('crisis_role')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $byRole = $employees->groupBy(fn (Employee $employee) => $employee->crisis_role?->value);

        $result = [];
        foreach (CrisisRole::cases() as $role) {
            /** @var Collection<int, Employee> $forRole */
            $forRole = $byRole->get($role->value, collect());

            $result[] = [
                'role' => $role,
                'role_label' => $role->label(),
                'main' => $forRole->firstWhere('is_crisis_deputy', false),
                'deputies' => $forRole->where('is_crisis_deputy', true)->values(),
            ];
        }

        return $result;
    }

    /**
     * Topologisch sortierte Wiederanlauf-Reihenfolge:
     * primär nach Notfall-Level (sort asc), sekundär nach Dependency-Depth.
     *
     * @return list<array{system: System, level_name: ?string, level_sort: ?int, rto_minutes: ?int, deadline_at: ?Carbon, depth: int, open_tasks: int, total_tasks: int}>
     */
    private static function recoveryOrder(Company $company, ?ScenarioRun $activeRun): array
    {
        $systems = System::query()
            ->where('company_id', $company->id)
            ->with(['emergencyLevel', 'dependencies:id', 'dependents:id'])
            ->get();

        if ($systems->isEmpty()) {
            return [];
        }

        $depths = self::computeDependencyDepths($systems);

        $taskCounts = SystemTask::query()
            ->whereIn('system_id', $systems->pluck('id'))
            ->selectRaw('system_id, count(*) as total, sum(case when completed_at is null then 1 else 0 end) as open')
            ->groupBy('system_id')
            ->get()
            ->keyBy('system_id');

        $startedAt = $activeRun?->started_at;

        $items = $systems->map(function (System $system) use ($depths, $taskCounts, $startedAt) {
            $counts = $taskCounts->get($system->id);

            return [
                'system' => $system,
                'level_name' => $system->emergencyLevel?->name,
                'level_sort' => $system->emergencyLevel?->sort,
                'rto_minutes' => $system->rto_minutes,
                'deadline_at' => ($startedAt && $system->rto_minutes !== null)
                    ? $startedAt->copy()->addMinutes((int) $system->rto_minutes)
                    : null,
                'depth' => $depths[$system->id] ?? 0,
                'open_tasks' => (int) ($counts->open ?? 0),
                'total_tasks' => (int) ($counts->total ?? 0),
            ];
        });

        return $items
            ->sort(function (array $a, array $b) {
                $sortA = $a['level_sort'] ?? PHP_INT_MAX;
                $sortB = $b['level_sort'] ?? PHP_INT_MAX;
                if ($sortA !== $sortB) {
                    return $sortA <=> $sortB;
                }

                if ($a['depth'] !== $b['depth']) {
                    return $b['depth'] <=> $a['depth'];
                }

                $rtoA = $a['rto_minutes'] ?? PHP_INT_MAX;
                $rtoB = $b['rto_minutes'] ?? PHP_INT_MAX;
                if ($rtoA !== $rtoB) {
                    return $rtoA <=> $rtoB;
                }

                return strcasecmp($a['system']->name, $b['system']->name);
            })
            ->values()
            ->all();
    }

    /**
     * Berechnet pro System die Dependency-Tiefe. Tiefe = längster Pfad
     * zu einem Blatt entlang der `dependents`-Kante (also wie viele
     * Systeme indirekt von diesem hier abhängen). Zyklen ergeben Tiefe 0.
     *
     * @param  Collection<int, System>  $systems  preloaded with `dependents:id`.
     * @return array<string, int>
     */
    private static function computeDependencyDepths(Collection $systems): array
    {
        $byId = $systems->keyBy('id');
        $memo = [];
        $visiting = [];

        $depthOf = function (string $id) use (&$depthOf, &$memo, &$visiting, $byId): int {
            if (array_key_exists($id, $memo)) {
                return $memo[$id];
            }
            if (isset($visiting[$id])) {
                return 0;
            }
            $system = $byId->get($id);
            if ($system === null) {
                return 0;
            }

            $visiting[$id] = true;
            $max = 0;
            foreach ($system->dependents as $dependent) {
                if (! $byId->has($dependent->id)) {
                    continue;
                }
                $max = max($max, 1 + $depthOf($dependent->id));
            }
            unset($visiting[$id]);

            return $memo[$id] = $max;
        };

        $result = [];
        foreach ($systems as $system) {
            $result[$system->id] = $depthOf($system->id);
        }

        return $result;
    }

    /**
     * Kommunikationsvorlagen, mit Szenario-spezifischen zuerst (falls aktiver Run).
     *
     * @return Collection<int, CommunicationTemplate>
     */
    private static function communicationTemplates(Company $company, ?ScenarioRun $activeRun): Collection
    {
        $templates = CommunicationTemplate::query()
            ->where('company_id', $company->id)
            ->with('scenario')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        $scenarioId = $activeRun?->scenario_id;
        if ($scenarioId === null) {
            return $templates;
        }

        return $templates
            ->sortBy(fn (CommunicationTemplate $template) => $template->scenario_id === $scenarioId ? 0 : 1)
            ->values();
    }

    /**
     * Aktive Meldepflichten aus IncidentReports, die dem aktiven Lauf zugeordnet sind.
     *
     * @return list<array{report: IncidentReport, obligation: IncidentReportObligation, deadline_at: ?Carbon, reported: bool, label: string}>
     */
    private static function obligations(Company $company, ?ScenarioRun $run): array
    {
        if ($run === null) {
            return [];
        }

        $reports = IncidentReport::query()
            ->where('company_id', $company->id)
            ->where('scenario_run_id', $run->id)
            ->with('obligations')
            ->get();

        $items = [];
        foreach ($reports as $report) {
            foreach ($report->obligations as $obligation) {
                $items[] = [
                    'report' => $report,
                    'obligation' => $obligation,
                    'deadline_at' => self::deadlineFor($report, $obligation->obligation),
                    'reported' => $obligation->reported_at !== null,
                    'label' => self::obligationLabel($obligation),
                ];
            }
        }

        return $items;
    }

    private static function deadlineFor(IncidentReport $report, mixed $obligationEnum): ?Carbon
    {
        if ($report->occurred_at === null || $obligationEnum === null) {
            return null;
        }

        if (is_object($obligationEnum) && method_exists($obligationEnum, 'hoursDeadline')) {
            $hours = $obligationEnum->hoursDeadline();
        } elseif (is_object($obligationEnum) && method_exists($obligationEnum, 'deadlineHours')) {
            $hours = $obligationEnum->deadlineHours();
        } else {
            return null;
        }

        if ($hours === null) {
            return null;
        }

        return $report->occurred_at->copy()->addHours((int) $hours);
    }

    private static function obligationLabel(IncidentReportObligation $obligation): string
    {
        $value = $obligation->obligation;

        if (is_object($value) && method_exists($value, 'label')) {
            return (string) $value->label();
        }

        if (is_object($value) && property_exists($value, 'value')) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
