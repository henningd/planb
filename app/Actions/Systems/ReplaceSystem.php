<?php

namespace App\Actions\Systems;

use App\Models\System;
use App\Support\AssignmentSync;
use App\Support\Audit\AccountAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ersetzt System A durch System B (z. B. beim Anbieterwechsel): Alle
 * Beziehungen VON und ZU System A werden auf System B übertragen.
 *
 * Übertragen werden:
 *  - Verantwortliche (Mitarbeiter, Dienstleister, Rollen) – temporal &
 *    revisionssicher über AssignmentSync.
 *  - Abhängigkeiten in BEIDE Richtungen (A hängt von X ab / Y hängt von A ab).
 *  - Verknüpfungen zu Geschäftsprozessen, Fallback-Prozessen und Risiken.
 *  - Die zu A gehörenden Aufgaben und präventiven Maßnahmen (samt deren
 *    Zuweisungen) werden auf B umgehängt.
 *
 * A bleibt erhalten, nur entkoppelt. B's bestehende (ggf. wichtigere)
 * Zuordnungen werden nicht überschrieben. Self-Bezüge (B↔B) und Doppel-
 * verknüpfungen werden vermieden. Historische Monitoring-Alarme bleiben bei A.
 */
class ReplaceSystem
{
    /**
     * @return array{providers: int, employees: int, roles: int, dependencies: int, processes: int, risks: int, tasks: int, measures: int}
     */
    public function handle(System $from, System $to): array
    {
        if ($from->is($to)) {
            throw new InvalidArgumentException('Quelle und Ziel müssen unterschiedlich sein.');
        }

        if ($from->company_id !== $to->company_id) {
            throw new InvalidArgumentException('Beide Systeme müssen zum selben Mandanten gehören.');
        }

        $attrCols = ['raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note'];

        $summary = DB::transaction(fn (): array => [
            'providers' => $this->transferPivot($from, $to, 'serviceProviders', $attrCols),
            'employees' => $this->transferPivot($from, $to, 'employees', $attrCols),
            'roles' => $this->transferPivot($from, $to, 'roles', $attrCols),
            'dependencies' => $this->transferDependencies($from, $to),
            'processes' => $this->repointPivot('business_process_system', 'business_process_id', $from, $to)
                + $this->repointPivot('fallback_process_system', 'fallback_process_id', $from, $to),
            'risks' => $this->repointPivot('risk_system', 'risk_id', $from, $to),
            'tasks' => DB::table('system_tasks')->where('system_id', $from->id)->update(['system_id' => $to->id]),
            'measures' => DB::table('preventive_measures')->where('system_id', $from->id)->update(['system_id' => $to->id]),
        ]);

        AccountAudit::record(
            action: 'system.replaced',
            entityType: 'System',
            entityId: $from->id,
            entityLabel: $from->name,
            companyId: $from->company_id,
            changes: [
                'to_id' => $to->id,
                'to_label' => $to->name,
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
    private function transferPivot(System $from, System $to, string $relationName, array $attrCols): int
    {
        $from->load($relationName);

        $count = 0;

        foreach ($from->{$relationName} as $related) {
            /** @var BelongsToMany<Model, System> $targetRelation */
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
     * Hängt die Abhängigkeiten in beide Richtungen um:
     *  - "A hängt von X ab"  → "B hängt von X ab"   (system_id = A → B)
     *  - "Y hängt von A ab"  → "Y hängt von B ab"   (depends_on_system_id = A → B)
     */
    private function transferDependencies(System $from, System $to): int
    {
        return $this->repointSelfDependency($from, $to, 'system_id', 'depends_on_system_id')
            + $this->repointSelfDependency($from, $to, 'depends_on_system_id', 'system_id');
    }

    private function repointSelfDependency(System $from, System $to, string $moveCol, string $otherCol): int
    {
        $rows = DB::table('system_dependencies')->where($moveCol, $from->id)->get();

        $count = 0;

        foreach ($rows as $row) {
            $otherId = $row->{$otherCol};

            // Self-Bezug (B hängt von B ab) vermeiden; sonst Beziehung auf B
            // anlegen (insertOrIgnore dedupliziert gegen den Composite-PK).
            if ($otherId !== $to->id) {
                DB::table('system_dependencies')->insertOrIgnore([
                    $moveCol => $to->id,
                    $otherCol => $otherId,
                    'sort' => $row->sort ?? 0,
                    'note' => $row->note ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }

            DB::table('system_dependencies')
                ->where($moveCol, $from->id)
                ->where($otherCol, $otherId)
                ->delete();
        }

        return $count;
    }

    /**
     * Hängt einen nicht-temporalen Pivot (Prozess/Risiko ↔ System) von A auf B
     * um. Zeilen, die bei B bereits existieren würden, werden bei A entfernt
     * (Dedup gegen den Composite-PK).
     */
    private function repointPivot(string $table, string $otherKey, System $from, System $to): int
    {
        DB::table($table)
            ->where('system_id', $from->id)
            ->whereIn($otherKey, fn ($q) => $q->select($otherKey)->from($table)->where('system_id', $to->id))
            ->delete();

        return DB::table($table)
            ->where('system_id', $from->id)
            ->update(['system_id' => $to->id]);
    }
}
