<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Support\AssignmentSync;
use App\Support\Audit\AccountAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Überträgt alle AKTIVEN Zuständigkeiten von Mitarbeiter A auf Mitarbeiter B
 * (Handover, z. B. bei Austritt oder Rollenwechsel).
 *
 * Übertragen wird, was A aktuell verantwortet – zukunftsgerichtet. Historische
 * bzw. Audit-Fakten (Autorenschaft/Freigabe von Handbuch-Versionen,
 * Lesebestätigungen, empfangene Benachrichtigungen, abgeschlossene Schulungen,
 * bereits beendete Zuordnungen) bleiben bewusst bei A, damit die Nachweiskette
 * nicht verfälscht wird.
 *
 * A selbst bleibt erhalten, nur entlastet. B's bestehende (ggf. wichtigere)
 * Zuordnungen werden nicht überschrieben: B erbt eine Zuordnung nur, wenn es
 * sie nicht ohnehin schon aktiv hat – in jedem Fall wird A's Zuordnung beendet.
 */
class TransferResponsibilities
{
    /**
     * Tabellen mit einem direkten `responsible_employee_id` (aktuelle
     * Verantwortung – wird auf B umgezogen).
     *
     * @var list<string>
     */
    private const RESPONSIBLE_TABLES = [
        'business_processes',
        'fallback_processes',
        'risk_mitigations',
        'preventive_measures',
        'handbook_tests',
        'lesson_learned_action_items',
    ];

    /**
     * @return array{systems: int, tasks: int, roles: int, reports: int, responsibilities: int}
     */
    public function handle(Employee $from, Employee $to): array
    {
        if ($from->is($to)) {
            throw new InvalidArgumentException('Quelle und Ziel müssen unterschiedlich sein.');
        }

        if ($from->company_id !== $to->company_id) {
            throw new InvalidArgumentException('Beide Mitarbeiter müssen zum selben Mandanten gehören.');
        }

        $summary = DB::transaction(fn (): array => [
            'systems' => $this->transferPivot($from, $to, 'systems', ['raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note']),
            'tasks' => $this->transferPivot($from, $to, 'tasks', ['raci_role', 'is_deputy']),
            'roles' => $this->transferPivot($from, $to, 'roles', ['is_deputy']),
            'reports' => $this->transferManagedReports($from, $to),
            'responsibilities' => $this->transferResponsibleColumns($from, $to),
        ]);

        AccountAudit::record(
            action: 'employee.responsibilities_transferred',
            entityType: 'Employee',
            entityId: $from->id,
            entityLabel: $from->fullName(),
            companyId: $from->company_id,
            changes: [
                'to_id' => $to->id,
                'to_label' => $to->fullName(),
                'summary' => $summary,
            ],
        );

        return $summary;
    }

    /**
     * Verschiebt die aktiven Zuordnungen eines temporalen Pivots von A auf B.
     *
     * @param  list<string>  $attrCols
     */
    private function transferPivot(Employee $from, Employee $to, string $relationName, array $attrCols): int
    {
        $from->load($relationName);

        $count = 0;

        foreach ($from->{$relationName} as $related) {
            /** @var BelongsToMany<Model, Employee> $targetRelation */
            $targetRelation = $to->{$relationName}();

            if (! $targetRelation->whereKey($related->getKey())->exists()) {
                $attrs = [];
                foreach ($attrCols as $col) {
                    $attrs[$col] = $related->pivot->{$col} ?? null;
                }

                AssignmentSync::attach($to, $to->{$relationName}(), $related->getKey(), $attrs);
            }

            AssignmentSync::detach($from, $from->{$relationName}(), $related->getKey());

            $count++;
        }

        return $count;
    }

    /**
     * Mitarbeiterführung: "A führt X" wird zu "B führt X". Vermeidet
     * Selbstführung (B führt B) und Doppel-Links.
     */
    private function transferManagedReports(Employee $from, Employee $to): int
    {
        $rows = DB::table('employee_manager')->where('manager_id', $from->id)->get();

        $count = 0;

        foreach ($rows as $row) {
            if ($row->employee_id !== $to->id) {
                DB::table('employee_manager')->insertOrIgnore([
                    'employee_id' => $row->employee_id,
                    'manager_id' => $to->id,
                ]);
                $count++;
            }

            DB::table('employee_manager')
                ->where('manager_id', $from->id)
                ->where('employee_id', $row->employee_id)
                ->delete();
        }

        return $count;
    }

    private function transferResponsibleColumns(Employee $from, Employee $to): int
    {
        $count = 0;

        foreach (self::RESPONSIBLE_TABLES as $table) {
            $count += DB::table($table)
                ->where('responsible_employee_id', $from->id)
                ->update(['responsible_employee_id' => $to->id]);
        }

        return $count;
    }
}
