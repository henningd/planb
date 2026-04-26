<?php

namespace App\Support;

use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\HandbookTest;
use App\Models\HandbookVersion;
use App\Models\IncidentReport;
use App\Models\IncidentReportObligation;
use App\Models\ScenarioRun;
use App\Scopes\CurrentCompanyScope;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Sammelt offene "Was muss ich heute tun?"-Aktionen für das Dashboard
 * über alle handbuchrelevanten Bereiche eines Mandanten:
 *  - fällige / überfällige Tests (HandbookTest)
 *  - fällige / überfällige Ressourcen-Checks (EmergencyResource)
 *  - aktive Lagen (ScenarioRun)
 *  - offene Meldepflichten (IncidentReportObligation)
 *  - noch nicht freigegebene Handbuch-Versionen (HandbookVersion)
 *
 * Das Ergebnis ist nach Dringlichkeit sortiert:
 *   overdue → today → soon → active.
 */
class DashboardActions
{
    /**
     * Zeitfenster (in Tagen), in dem Fälligkeiten als "demnächst" gelten.
     */
    private const SOON_WINDOW_DAYS = 14;

    /**
     * @return list<array{
     *   type: string,
     *   label: string,
     *   subtitle: string,
     *   severity: string,
     *   route: string,
     *   route_params: array<string, mixed>,
     * }>
     */
    public static function for(Company $company): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $horizon = $today->addDays(self::SOON_WINDOW_DAYS)->endOfDay();

        $items = [];

        foreach (self::collectTests($company, $today, $horizon) as $row) {
            $items[] = $row;
        }
        foreach (self::collectResources($company, $today, $horizon) as $row) {
            $items[] = $row;
        }
        foreach (self::collectScenarioRuns($company) as $row) {
            $items[] = $row;
        }
        foreach (self::collectIncidentObligations($company) as $row) {
            $items[] = $row;
        }
        foreach (self::collectHandbookVersions($company) as $row) {
            $items[] = $row;
        }

        usort($items, fn (array $a, array $b) => self::severityRank($a['severity']) <=> self::severityRank($b['severity']));

        return array_values($items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function collectTests(Company $company, CarbonInterface $today, CarbonInterface $horizon): array
    {
        $tests = HandbookTest::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', $horizon->toDateString())
            ->orderBy('next_due_at')
            ->get();

        $rows = [];
        foreach ($tests as $test) {
            $rows[] = [
                'type' => 'test',
                'label' => __('Test: :name', ['name' => $test->name ?: $test->type->label()]),
                'subtitle' => self::dueSubtitle($test->next_due_at, $today),
                'severity' => self::severityForDate($test->next_due_at, $today),
                'route' => 'handbook-tests.index',
                'route_params' => [],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function collectResources(Company $company, CarbonInterface $today, CarbonInterface $horizon): array
    {
        $resources = EmergencyResource::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('next_check_at')
            ->where('next_check_at', '<=', $horizon->toDateString())
            ->orderBy('next_check_at')
            ->get();

        $rows = [];
        foreach ($resources as $resource) {
            $rows[] = [
                'type' => 'resource',
                'label' => __('Ressource prüfen: :name', ['name' => $resource->name ?: $resource->type->label()]),
                'subtitle' => self::dueSubtitle($resource->next_check_at, $today),
                'severity' => self::severityForDate($resource->next_check_at, $today),
                'route' => 'emergency-resources.index',
                'route_params' => [],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function collectScenarioRuns(Company $company): array
    {
        $runs = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNull('ended_at')
            ->whereNull('aborted_at')
            ->orderByDesc('started_at')
            ->get();

        $rows = [];
        foreach ($runs as $run) {
            $rows[] = [
                'type' => 'scenario_run',
                'label' => __('Aktive Lage: :title', ['title' => $run->title]),
                'subtitle' => __('seit :since', ['since' => $run->started_at?->diffForHumans() ?? '—']),
                'severity' => 'active',
                'route' => 'scenario-runs.show',
                'route_params' => ['run' => $run->id],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function collectIncidentObligations(Company $company): array
    {
        $reportIds = IncidentReport::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->pluck('id');

        if ($reportIds->isEmpty()) {
            return [];
        }

        $obligations = IncidentReportObligation::query()
            ->whereIn('incident_report_id', $reportIds)
            ->whereNull('reported_at')
            ->with('incidentReport')
            ->get();

        $rows = [];
        foreach ($obligations as $obligation) {
            $report = $obligation->incidentReport;
            if ($report === null) {
                continue;
            }

            $rows[] = [
                'type' => 'incident_obligation',
                'label' => __('Meldepflicht offen: :name', ['name' => $obligation->obligation->label()]),
                'subtitle' => __('Vorfall: :title', ['title' => $report->title]),
                'severity' => 'overdue',
                'route' => 'incidents.show',
                'route_params' => ['report' => $report->id],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function collectHandbookVersions(Company $company): array
    {
        $versions = HandbookVersion::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNull('approved_at')
            ->orderByDesc('changed_at')
            ->get();

        $rows = [];
        foreach ($versions as $version) {
            $rows[] = [
                'type' => 'handbook_version',
                'label' => __('Freigabe offen: Version :version', ['version' => $version->version]),
                'subtitle' => $version->change_reason
                    ? (string) $version->change_reason
                    : __('Geändert am :date', ['date' => $version->changed_at?->format('d.m.Y') ?? '—']),
                'severity' => 'soon',
                'route' => 'handbook-versions.index',
                'route_params' => [],
            ];
        }

        return $rows;
    }

    private static function severityForDate(CarbonInterface $date, CarbonInterface $today): string
    {
        $dateStart = $date->copy()->startOfDay();

        if ($dateStart->lessThan($today)) {
            return 'overdue';
        }
        if ($dateStart->equalTo($today)) {
            return 'today';
        }

        return 'soon';
    }

    private static function dueSubtitle(CarbonInterface $date, CarbonInterface $today): string
    {
        $formatted = $date->format('d.m.Y');
        $dateStart = $date->copy()->startOfDay();

        if ($dateStart->lessThan($today)) {
            return __('überfällig seit :date', ['date' => $formatted]);
        }
        if ($dateStart->equalTo($today)) {
            return __('heute fällig');
        }

        return __('fällig :date', ['date' => $formatted]);
    }

    private static function severityRank(string $severity): int
    {
        return match ($severity) {
            'overdue' => 0,
            'today' => 1,
            'soon' => 2,
            'active' => 3,
            default => 99,
        };
    }
}
