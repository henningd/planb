<?php

namespace App\Support\Compliance;

use App\Enums\ComplianceCategory;
use App\Enums\CrisisRole;
use App\Enums\RaciRole;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\HandbookTest;
use App\Models\Location;
use App\Models\Role;
use App\Models\ScenarioRun;
use App\Models\System;
use App\Models\SystemTask;
use Illuminate\Support\Carbon;

/**
 * Definiert alle Compliance-Checks für den Mandanten-Score.
 *
 * Inspiriert an BSI 200-4 (Notfallmanagement) und NIS2-Anforderungen
 * (Krisenorganisation, Asset-Inventar, Tests, Dokumentation).
 */
class Catalog
{
    /**
     * @return list<Check>
     */
    public static function all(): array
    {
        return [
            self::systemRolesCoverage(),
            self::systemRolesDeputies(),
            self::locations(),
            self::systemsClassified(),
            self::systemsRaci(),
            self::systemTasks(),
            self::handbookPublished(),
            self::handbookTests(),
            self::scenarioExercises(),
            self::emergencyResources(),
            self::communicationTemplates(),
        ];
    }

    private static function systemRolesCoverage(): Check
    {
        return new Check(
            key: 'roles.system.coverage',
            label: 'Pflichtrollen besetzt',
            description: 'Für jede der fünf System-Rollen (Notfallbeauftragte/r, IT, DSB, Kommunikation, Geschäftsführung) ist mindestens eine Hauptperson zugeordnet.',
            category: ComplianceCategory::Organisation,
            weight: 10,
            evaluator: function (Company $company): Result {
                $missing = [];
                foreach (CrisisRole::cases() as $crisis) {
                    $role = Role::query()
                        ->where('company_id', $company->id)
                        ->where('system_key', $crisis->value)
                        ->first();
                    if (! $role) {
                        $missing[] = $crisis->label().' (Rolle fehlt)';

                        continue;
                    }
                    $hasMain = $role->employees()
                        ->wherePivot('is_deputy', false)
                        ->exists();
                    if (! $hasMain) {
                        $missing[] = $crisis->label();
                    }
                }
                $total = count(CrisisRole::cases());
                $covered = $total - count($missing);
                $action = ['label' => 'Rollen verwalten', 'route' => 'roles.index'];

                if ($covered === $total) {
                    return Result::pass('Alle fünf Pflichtrollen sind mit einer Hauptperson besetzt.', action: $action);
                }
                if ($covered === 0) {
                    return Result::fail('Keine der fünf Pflichtrollen ist besetzt.', $missing, $action);
                }

                return Result::partial(
                    (int) round($covered / $total * 100),
                    "{$covered} von {$total} Pflichtrollen besetzt.",
                    $missing,
                    $action,
                );
            },
        );
    }

    private static function systemRolesDeputies(): Check
    {
        return new Check(
            key: 'roles.system.deputies',
            label: 'Pflichtrollen mit Vertretung',
            description: 'Jede System-Rolle hat mindestens eine Vertretung, damit Ausfälle der Hauptperson abgefedert sind.',
            category: ComplianceCategory::Organisation,
            weight: 6,
            evaluator: function (Company $company): Result {
                $missing = [];
                foreach (CrisisRole::cases() as $crisis) {
                    $role = Role::query()
                        ->where('company_id', $company->id)
                        ->where('system_key', $crisis->value)
                        ->first();
                    if (! $role) {
                        continue;
                    }
                    $hasDeputy = $role->employees()
                        ->wherePivot('is_deputy', true)
                        ->exists();
                    if (! $hasDeputy) {
                        $missing[] = $crisis->label();
                    }
                }
                $total = count(CrisisRole::cases());
                $covered = $total - count($missing);
                $action = ['label' => 'Vertretungen pflegen', 'route' => 'roles.index'];

                if ($covered === $total) {
                    return Result::pass('Alle Pflichtrollen haben eine Vertretung.', action: $action);
                }
                if ($covered === 0) {
                    return Result::fail('Keine Vertretungen für die Pflichtrollen hinterlegt.', $missing, $action);
                }

                return Result::partial(
                    (int) round($covered / $total * 100),
                    "{$covered} von {$total} Pflichtrollen haben eine Vertretung.",
                    $missing,
                    $action,
                );
            },
        );
    }

    private static function locations(): Check
    {
        return new Check(
            key: 'company.locations',
            label: 'Standorte erfasst',
            description: 'Mindestens ein Standort ist gepflegt – Voraussetzung für Vor-Ort-Maßnahmen und Wiederanlauf.',
            category: ComplianceCategory::Organisation,
            weight: 3,
            evaluator: function (Company $company): Result {
                $count = Location::query()->where('company_id', $company->id)->count();
                $action = ['label' => 'Standorte verwalten', 'route' => 'locations.index'];

                if ($count === 0) {
                    return Result::fail('Es ist kein Standort gepflegt.', action: $action);
                }
                $hasHq = Location::query()
                    ->where('company_id', $company->id)
                    ->where('is_headquarters', true)
                    ->exists();
                if (! $hasHq) {
                    return Result::partial(60, "{$count} Standort(e) gepflegt, aber kein Hauptsitz markiert.", action: $action);
                }

                return Result::pass("{$count} Standort(e) inklusive Hauptsitz gepflegt.", action: $action);
            },
        );
    }

    private static function systemsClassified(): Check
    {
        return new Check(
            key: 'systems.classified',
            label: 'Systeme erfasst & klassifiziert',
            description: 'Alle erfassten Systeme sind einem Notfall-Level zugeordnet, damit Priorisierung im Ernstfall klar ist.',
            category: ComplianceCategory::Systeme,
            weight: 8,
            evaluator: function (Company $company): Result {
                $total = System::query()->where('company_id', $company->id)->count();
                $action = ['label' => 'Systeme öffnen', 'route' => 'systems.index'];

                if ($total === 0) {
                    return Result::fail('Es sind keine Systeme erfasst.', action: $action);
                }
                $unclassified = System::query()
                    ->where('company_id', $company->id)
                    ->whereNull('emergency_level_id')
                    ->pluck('name')
                    ->all();
                $count = count($unclassified);
                if ($count === 0) {
                    return Result::pass("Alle {$total} Systeme sind klassifiziert.", action: $action);
                }
                $score = (int) round(($total - $count) / $total * 100);

                return Result::partial(
                    $score,
                    "{$count} von {$total} Systemen ohne Notfall-Level.",
                    array_slice($unclassified, 0, 5),
                    $action,
                );
            },
        );
    }

    private static function systemsRaci(): Check
    {
        return new Check(
            key: 'systems.raci',
            label: 'Systeme mit Verantwortlichen (RACI)',
            description: 'Jedes System hat mindestens eine Verantwortliche (A) und eine Durchführende (R) Person oder Rolle.',
            category: ComplianceCategory::Systeme,
            weight: 8,
            evaluator: function (Company $company): Result {
                $systems = System::query()
                    ->where('company_id', $company->id)
                    ->with(['employees', 'roles'])
                    ->get();
                $total = $systems->count();
                $action = ['label' => 'Systeme öffnen', 'route' => 'systems.index'];

                if ($total === 0) {
                    return Result::notApplicable('Keine Systeme – RACI nicht bewertbar.');
                }
                $missing = [];
                foreach ($systems as $system) {
                    $employeeRoles = $system->employees->pluck('pivot.raci_role')->all();
                    $roleRoles = $system->roles->pluck('pivot.raci_role')->all();
                    $allRoles = array_unique(array_merge($employeeRoles, $roleRoles));
                    $hasA = in_array(RaciRole::Accountable->value, $allRoles, true);
                    $hasR = in_array(RaciRole::Responsible->value, $allRoles, true);
                    if (! $hasA || ! $hasR) {
                        $miss = [];
                        if (! $hasA) {
                            $miss[] = 'Verantwortlich';
                        }
                        if (! $hasR) {
                            $miss[] = 'Durchführend';
                        }
                        $missing[] = $system->name.' ('.implode(' + ', $miss).' fehlt)';
                    }
                }
                $count = count($missing);
                if ($count === 0) {
                    return Result::pass("Alle {$total} Systeme haben Verantwortlich und Durchführend.", action: $action);
                }
                $score = (int) round(($total - $count) / $total * 100);

                return Result::partial(
                    $score,
                    "{$count} von {$total} Systemen ohne vollständige RACI-Besetzung.",
                    array_slice($missing, 0, 5),
                    $action,
                );
            },
        );
    }

    private static function systemTasks(): Check
    {
        return new Check(
            key: 'systems.tasks',
            label: 'System-Aufgaben mit RACI',
            description: 'Jedes System hat mindestens eine Wiederanlauf-Aufgabe und keine Aufgabe ist ohne RACI-Zuordnung.',
            category: ComplianceCategory::Systeme,
            weight: 5,
            evaluator: function (Company $company): Result {
                $systems = System::query()
                    ->where('company_id', $company->id)
                    ->withCount('tasks')
                    ->get();
                $total = $systems->count();
                $action = ['label' => 'Systeme öffnen', 'route' => 'systems.index'];

                if ($total === 0) {
                    return Result::notApplicable('Keine Systeme – Aufgaben nicht bewertbar.');
                }
                $withoutTasks = $systems->where('tasks_count', 0)->pluck('name')->all();

                $tasks = SystemTask::query()
                    ->where('company_id', $company->id)
                    ->with(['assignees', 'roleAssignees', 'providerAssignees', 'system:id,name'])
                    ->get();
                $totalTasks = $tasks->count();
                $unassignedTasks = [];
                foreach ($tasks as $task) {
                    $hasAny = $task->assignees->isNotEmpty()
                        || $task->roleAssignees->isNotEmpty()
                        || $task->providerAssignees->isNotEmpty();
                    if (! $hasAny) {
                        $unassignedTasks[] = ($task->system?->name ?? 'System').': '.$task->title;
                    }
                }

                $details = [];
                if ($withoutTasks) {
                    $details[] = 'Ohne Aufgaben: '.implode(', ', array_slice($withoutTasks, 0, 5));
                }
                if ($unassignedTasks) {
                    $details[] = 'Aufgaben ohne Zuordnung: '.implode(' • ', array_slice($unassignedTasks, 0, 5));
                }

                $okSystems = $total - count($withoutTasks);
                $okTasks = max(0, $totalTasks - count($unassignedTasks));
                $totalUnits = $total + $totalTasks;
                if ($totalUnits === 0) {
                    return Result::fail('Keine Aufgaben hinterlegt.', $details, $action);
                }
                $okUnits = $okSystems + $okTasks;
                if ($okUnits === $totalUnits && empty($withoutTasks)) {
                    return Result::pass("{$totalTasks} Aufgaben in {$total} Systemen, alle zugeordnet.", action: $action);
                }
                if ($okUnits === 0) {
                    return Result::fail('Keine Aufgaben mit Zuordnung.', $details, $action);
                }

                return Result::partial(
                    (int) round($okUnits / $totalUnits * 100),
                    "{$okSystems}/{$total} Systeme mit Aufgaben, {$okTasks}/{$totalTasks} Aufgaben mit Zuordnung.",
                    $details,
                    $action,
                );
            },
        );
    }

    private static function handbookPublished(): Check
    {
        return new Check(
            key: 'handbook.published',
            label: 'Notfallhandbuch freigegeben & aktuell',
            description: 'Es existiert eine freigegebene Handbuch-Version, die nicht älter als zwölf Monate ist.',
            category: ComplianceCategory::Dokumentation,
            weight: 10,
            evaluator: function (Company $company): Result {
                $current = $company->currentHandbookVersion();
                $action = ['label' => 'Handbuch-Versionen öffnen', 'route' => 'handbook-versions.index'];

                if (! $current) {
                    return Result::fail('Es ist keine Handbuch-Version freigegeben.', action: $action);
                }
                $approvedAt = $current->approved_at instanceof Carbon
                    ? $current->approved_at
                    : Carbon::parse($current->approved_at);
                $months = (int) floor($approvedAt->diffInMonths(now()));
                $details = ['Aktuelle Version: '.$current->version.' (freigegeben '.$approvedAt->isoFormat('DD.MM.YYYY').')'];
                if ($months <= 12) {
                    return Result::pass("Aktuelle Version vor {$months} Monat(en) freigegeben.", $details, $action);
                }
                if ($months <= 18) {
                    return Result::partial(60, "Aktuelle Version ist {$months} Monate alt – Auffrischung empfohlen.", $details, $action);
                }

                return Result::partial(20, "Aktuelle Version ist {$months} Monate alt – dringend aktualisieren.", $details, $action);
            },
        );
    }

    private static function handbookTests(): Check
    {
        return new Check(
            key: 'tests.executed',
            label: 'Notfall-Tests durchgeführt',
            description: 'Es sind Notfall-Tests geplant, durchgeführt und keiner ist überfällig.',
            category: ComplianceCategory::Tests,
            weight: 10,
            evaluator: function (Company $company): Result {
                $tests = HandbookTest::query()->where('company_id', $company->id)->get();
                $action = ['label' => 'Tests verwalten', 'route' => 'handbook-tests.index'];

                if ($tests->isEmpty()) {
                    return Result::fail('Es sind keine Notfall-Tests geplant.', action: $action);
                }
                $never = $tests->whereNull('last_executed_at');
                $overdue = $tests->filter(fn ($t) => $t->isOverdue());
                $total = $tests->count();
                $bad = $never->count() + $overdue->reject(fn ($t) => $never->contains($t))->count();
                $good = $total - $bad;
                $details = [];
                if ($never->isNotEmpty()) {
                    $details[] = 'Nie durchgeführt: '.$never->pluck('name')->take(5)->implode(', ');
                }
                $reallyOverdue = $overdue->reject(fn ($t) => $never->contains($t));
                if ($reallyOverdue->isNotEmpty()) {
                    $details[] = 'Überfällig: '.$reallyOverdue->pluck('name')->take(5)->implode(', ');
                }
                if ($bad === 0) {
                    return Result::pass("Alle {$total} Tests sind aktuell.", action: $action);
                }
                if ($good === 0) {
                    return Result::fail("Alle {$total} Tests fehlen oder sind überfällig.", $details, $action);
                }

                return Result::partial(
                    (int) round($good / $total * 100),
                    "{$good} von {$total} Tests aktuell.",
                    $details,
                    $action,
                );
            },
        );
    }

    private static function scenarioExercises(): Check
    {
        return new Check(
            key: 'tests.scenarios',
            label: 'Szenario-Übungen durchgeführt',
            description: 'In den letzten zwölf Monaten wurde mindestens ein Szenario abgeschlossen geübt.',
            category: ComplianceCategory::Tests,
            weight: 6,
            evaluator: function (Company $company): Result {
                $cutoff = now()->subMonths(12);
                $action = ['label' => 'Szenario-Läufe öffnen', 'route' => 'scenario-runs.index'];

                $recent = ScenarioRun::query()
                    ->where('company_id', $company->id)
                    ->whereNotNull('ended_at')
                    ->where('ended_at', '>=', $cutoff)
                    ->count();
                $allCompleted = ScenarioRun::query()
                    ->where('company_id', $company->id)
                    ->whereNotNull('ended_at')
                    ->count();

                if ($recent > 0) {
                    return Result::pass("{$recent} Szenario-Übung(en) in den letzten 12 Monaten.", action: $action);
                }
                if ($allCompleted > 0) {
                    return Result::partial(40, 'Letzte Szenario-Übung ist älter als 12 Monate.', action: $action);
                }

                return Result::fail('Es wurde noch nie ein Szenario komplett geübt.', action: $action);
            },
        );
    }

    private static function emergencyResources(): Check
    {
        return new Check(
            key: 'resources.current',
            label: 'Notfall-Ressourcen aktuell',
            description: 'Notfall-Ressourcen wie Notebooks, Schlüssel oder USVs sind erfasst und ihre Prüfungen sind nicht überfällig.',
            category: ComplianceCategory::Dokumentation,
            weight: 4,
            evaluator: function (Company $company): Result {
                $resources = EmergencyResource::query()->where('company_id', $company->id)->get();
                $action = ['label' => 'Ressourcen verwalten', 'route' => 'emergency-resources.index'];

                if ($resources->isEmpty()) {
                    return Result::fail('Keine Notfall-Ressourcen erfasst.', action: $action);
                }
                $overdue = $resources->filter(fn ($r) => $r->isOverdue());
                $total = $resources->count();
                $count = $overdue->count();
                if ($count === 0) {
                    return Result::pass("{$total} Ressource(n) erfasst, alle Prüfungen aktuell.", action: $action);
                }

                return Result::partial(
                    (int) round(($total - $count) / $total * 100),
                    "{$count} von {$total} Ressourcen mit überfälliger Prüfung.",
                    $overdue->pluck('name')->take(5)->all(),
                    $action,
                );
            },
        );
    }

    private static function communicationTemplates(): Check
    {
        return new Check(
            key: 'communication.templates',
            label: 'Kommunikationsvorlagen vorhanden',
            description: 'Mindestens drei vorbereitete Krisen-Kommunikationsvorlagen (z. B. an Kunden, Mitarbeitende, Behörden) sind hinterlegt.',
            category: ComplianceCategory::Dokumentation,
            weight: 4,
            evaluator: function (Company $company): Result {
                $count = CommunicationTemplate::query()->where('company_id', $company->id)->count();
                $action = ['label' => 'Vorlagen öffnen', 'route' => 'communication-templates.index'];

                if ($count === 0) {
                    return Result::fail('Keine Kommunikationsvorlagen hinterlegt.', action: $action);
                }
                if ($count >= 3) {
                    return Result::pass("{$count} Vorlage(n) hinterlegt.", action: $action);
                }

                return Result::partial(
                    (int) round($count / 3 * 100),
                    "{$count} von empfohlenen 3 Vorlagen vorhanden.",
                    action: $action,
                );
            },
        );
    }
}
