<?php

namespace App\Support;

use App\Models\Company;
use App\Models\HandbookShare;
use App\Models\ServiceProvider;
use App\Models\System;
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
            'employees',
            'locations',
            'emergencyLevels',
            'systems.priority',
            'systems.emergencyLevel',
            'systems.serviceProviders',
            'systems.dependencies',
            'systems.employees',
            'systems.roles.employees',
            'systems.tasks.assignees',
            'systems.tasks.providerAssignees',
            'systems.tasks.roleAssignees.employees',
            'systemPriorities',
            'scenarios.steps',
            'communicationTemplates.scenario',
            'insurancePolicies',
            'handbookVersions.changedBy',
            'handbookVersions.approvedBy',
            'emergencyResources',
            'handbookTests.responsible',
            'incidentReports.obligations',
            'incidentReports.scenarioRun.scenario',
            'scenarioRuns.scenario',
            'scenarioRuns.steps',
        ]);

        $providersQuery = ServiceProvider::with('systems')
            ->where('company_id', $company->id)
            ->orderBy('name');

        if ($share !== null) {
            $providersQuery->withoutGlobalScope(CurrentCompanyScope::class);
        }

        $systemsDetail = self::buildSystemsDetail($company);

        return [
            'company' => $company,
            'providers' => $providersQuery->get(),
            'recoveryPlan' => RecoveryOrder::compute($company->systems),
            'systemsDetail' => $systemsDetail,
            'share' => $share,
        ];
    }

    /**
     * Bereitet pro System eine kompakte Struktur für die PDF-Detail-Sicht
     * auf: System-RACI nach R/A/C/I + Aufgaben mit eigener R/A-Zuordnung.
     * Hier in PHP statt im Blade-Template, weil mehrzeilige @php-Blöcke
     * zusammen mit den CSS-@-Direktiven der Druckansicht vom Compiler
     * unzuverlässig verarbeitet werden.
     *
     * @return array<int, array{
     *     system: System,
     *     raci: array<string, list<string>>,
     *     tasks: list<array{title: string, description: ?string, due: ?string, status: string, r: string, a: string}>,
     * }>
     */
    private static function buildSystemsDetail(Company $company): array
    {
        $kinds = ['owner', 'operator', 'contact'];
        $out = [];

        foreach ($company->systems as $system) {
            $hasAnything = $system->employees->isNotEmpty()
                || $system->serviceProviders->isNotEmpty()
                || $system->roles->isNotEmpty()
                || $system->tasks->isNotEmpty();

            if (! $hasAnything) {
                continue;
            }

            $ownership = [];
            foreach ($kinds as $kind) {
                $entries = [];
                foreach ($system->employees as $e) {
                    if (($e->pivot->ownership_kind ?? null) === $kind) {
                        $suffix = ($e->pivot->is_deputy ?? false) ? ' (Vertretung)' : '';
                        $entries[] = $e->fullName().$suffix;
                    }
                }
                foreach ($system->serviceProviders as $p) {
                    if (($p->pivot->ownership_kind ?? null) === $kind) {
                        $suffix = ($p->pivot->is_deputy ?? false) ? ' (Vertretung)' : '';
                        $entries[] = 'DL: '.$p->name.$suffix;
                    }
                }
                foreach ($system->roles as $r) {
                    if (($r->pivot->ownership_kind ?? null) === $kind) {
                        $suffix = ($r->pivot->is_deputy ?? false) ? ' (Vertretung)' : '';
                        $line = 'Rolle: '.$r->name.$suffix;
                        if ($r->employees->isNotEmpty()) {
                            $line .= ' ('.$r->employees->map(fn ($emp) => $emp->fullName())->implode(', ').')';
                        }
                        $entries[] = $line;
                    }
                }
                $ownership[$kind] = $entries;
            }

            $tasks = [];
            $sortedTasks = $system->tasks
                ->sortBy(fn ($t) => [$t->completed_at !== null ? 1 : 0, $t->due_date?->getTimestamp() ?? PHP_INT_MAX])
                ->values();

            foreach ($sortedTasks as $task) {
                $rNames = [];
                $aNames = [];
                foreach ($task->assignees as $e) {
                    $code = $e->pivot->raci_role ?? null;
                    if ($code === 'R') {
                        $rNames[] = $e->fullName();
                    } elseif ($code === 'A') {
                        $aNames[] = $e->fullName();
                    }
                }
                foreach ($task->providerAssignees as $p) {
                    $code = $p->pivot->raci_role ?? null;
                    if ($code === 'R') {
                        $rNames[] = 'DL: '.$p->name;
                    } elseif ($code === 'A') {
                        $aNames[] = 'DL: '.$p->name;
                    }
                }
                foreach ($task->roleAssignees as $r) {
                    $code = $r->pivot->raci_role ?? null;
                    if ($code === 'R') {
                        $rNames[] = 'Rolle: '.$r->name;
                    } elseif ($code === 'A') {
                        $aNames[] = 'Rolle: '.$r->name;
                    }
                }

                $tasks[] = [
                    'title' => $task->title,
                    'description' => $task->description,
                    'due' => $task->due_date?->format('d.m.Y'),
                    'status' => $task->completed_at !== null
                        ? 'Erledigt am '.$task->completed_at->format('d.m.Y')
                        : 'offen',
                    'r' => implode(', ', $rNames),
                    'a' => implode(', ', $aNames),
                ];
            }

            $out[] = [
                'system' => $system,
                'ownership' => $ownership,
                'tasks' => $tasks,
            ];
        }

        return $out;
    }
}
