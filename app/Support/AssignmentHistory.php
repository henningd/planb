<?php

namespace App\Support;

use App\Models\System;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Setzt aus den temporalen Pivot-Tabellen eine vereinheitlichte
 * Zuordnungs-Historie zusammen — quer über Mitarbeiter, Rollen und
 * Dienstleister, sowohl auf System- als auch auf Aufgaben-Ebene.
 *
 * Jede Zeile beschreibt einen "Stint" (Zeitraum, in dem eine Zuordnung
 * gültig war) inkl. RACI, Notiz und ausführendem User. Damit lassen sich
 * Punkt-in-Zeit-Anfragen für Audits beantworten:
 *   "Wer war am 17.03.2026 R-Verantwortlicher für System X?"
 */
class AssignmentHistory
{
    /**
     * Liefert die komplette Historie für ein System inkl. seiner Aufgaben.
     *
     * Reihenfolge: aktive Zuordnungen zuerst, danach beendete jeweils nach
     * Zuweisungs-Datum absteigend.
     *
     * @return Collection<int, array{
     *   scope: string,
     *   scope_label: string,
     *   kind: string,
     *   kind_label: string,
     *   target_id: string,
     *   target_label: string,
     *   raci_role: ?string,
     *   note: ?string,
     *   assigned_at: ?CarbonInterface,
     *   assigned_by: ?string,
     *   removed_at: ?CarbonInterface,
     *   removed_by: ?string,
     * }>
     */
    public static function forSystem(System $system): Collection
    {
        $taskIdToTitle = DB::table('system_tasks')
            ->where('system_id', $system->id)
            ->pluck('title', 'id');

        $rows = collect();

        $rows = $rows->merge(self::pivotRows(
            table: 'employee_system',
            parentColumn: 'system_id',
            parentValue: $system->id,
            relatedTable: 'employees',
            relatedColumn: 'employee_id',
            scope: 'system',
            scopeLabel: $system->name,
            kind: 'employee',
            kindLabel: __('Mitarbeiter'),
            targetLabelExpr: "trim(employees.first_name || ' ' || employees.last_name)",
            withNote: true,
        ));

        $rows = $rows->merge(self::pivotRows(
            table: 'role_system',
            parentColumn: 'system_id',
            parentValue: $system->id,
            relatedTable: 'roles',
            relatedColumn: 'role_id',
            scope: 'system',
            scopeLabel: $system->name,
            kind: 'role',
            kindLabel: __('Rolle'),
            targetLabelExpr: 'roles.name',
            withNote: true,
        ));

        $rows = $rows->merge(self::pivotRows(
            table: 'service_provider_system',
            parentColumn: 'system_id',
            parentValue: $system->id,
            relatedTable: 'service_providers',
            relatedColumn: 'service_provider_id',
            scope: 'system',
            scopeLabel: $system->name,
            kind: 'provider',
            kindLabel: __('Dienstleister'),
            targetLabelExpr: 'service_providers.name',
            withNote: true,
        ));

        if ($taskIdToTitle->isNotEmpty()) {
            foreach ($taskIdToTitle as $taskId => $taskTitle) {
                $rows = $rows->merge(self::pivotRows(
                    table: 'system_task_employee',
                    parentColumn: 'system_task_id',
                    parentValue: (string) $taskId,
                    relatedTable: 'employees',
                    relatedColumn: 'employee_id',
                    scope: 'task',
                    scopeLabel: $taskTitle,
                    kind: 'employee',
                    kindLabel: __('Mitarbeiter'),
                    targetLabelExpr: "trim(employees.first_name || ' ' || employees.last_name)",
                    withNote: false,
                ));

                $rows = $rows->merge(self::pivotRows(
                    table: 'role_system_task',
                    parentColumn: 'system_task_id',
                    parentValue: (string) $taskId,
                    relatedTable: 'roles',
                    relatedColumn: 'role_id',
                    scope: 'task',
                    scopeLabel: $taskTitle,
                    kind: 'role',
                    kindLabel: __('Rolle'),
                    targetLabelExpr: 'roles.name',
                    withNote: false,
                ));

                $rows = $rows->merge(self::pivotRows(
                    table: 'service_provider_system_task',
                    parentColumn: 'system_task_id',
                    parentValue: (string) $taskId,
                    relatedTable: 'service_providers',
                    relatedColumn: 'service_provider_id',
                    scope: 'task',
                    scopeLabel: $taskTitle,
                    kind: 'provider',
                    kindLabel: __('Dienstleister'),
                    targetLabelExpr: 'service_providers.name',
                    withNote: false,
                ));
            }
        }

        return $rows->sort(function (array $a, array $b) {
            $aActive = $a['removed_at'] === null;
            $bActive = $b['removed_at'] === null;
            if ($aActive !== $bActive) {
                return $aActive ? -1 : 1;
            }

            return ($b['assigned_at']?->getTimestamp() ?? 0) <=> ($a['assigned_at']?->getTimestamp() ?? 0);
        })->values();
    }

    /**
     * Filtert eine Historie auf einen Stichtag — nur "Stints", die am
     * angegebenen Zeitpunkt aktiv waren (assigned_at <= moment <
     * removed_at oder removed_at = null).
     *
     * @param  Collection<int, array<string, mixed>>  $history
     * @return Collection<int, array<string, mixed>>
     */
    public static function atMoment(Collection $history, CarbonInterface $moment): Collection
    {
        return $history->filter(function (array $row) use ($moment) {
            if ($row['assigned_at'] === null || $row['assigned_at']->greaterThan($moment)) {
                return false;
            }
            if ($row['removed_at'] !== null && $row['removed_at']->lessThanOrEqualTo($moment)) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function pivotRows(
        string $table,
        string $parentColumn,
        string $parentValue,
        string $relatedTable,
        string $relatedColumn,
        string $scope,
        string $scopeLabel,
        string $kind,
        string $kindLabel,
        string $targetLabelExpr,
        bool $withNote,
    ): Collection {
        $query = DB::table($table)
            ->where("$table.$parentColumn", $parentValue)
            ->leftJoin($relatedTable, "$relatedTable.id", '=', "$table.$relatedColumn")
            ->leftJoin('users as ua', 'ua.id', '=', "$table.assigned_by_user_id")
            ->leftJoin('users as ur', 'ur.id', '=', "$table.removed_by_user_id");

        $select = [
            "$table.$relatedColumn as target_id",
            DB::raw("($targetLabelExpr) as target_label"),
            "$table.raci_role as raci_role",
            "$table.assigned_at as assigned_at",
            "$table.removed_at as removed_at",
            'ua.name as assigned_by',
            'ur.name as removed_by',
        ];

        if ($withNote) {
            $select[] = "$table.note as note";
        }

        return $query->select($select)->get()->map(function ($row) use ($scope, $scopeLabel, $kind, $kindLabel, $withNote) {
            return [
                'scope' => $scope,
                'scope_label' => $scopeLabel,
                'kind' => $kind,
                'kind_label' => $kindLabel,
                'target_id' => $row->target_id,
                'target_label' => $row->target_label ?? '—',
                'raci_role' => $row->raci_role,
                'note' => $withNote ? ($row->note ?? null) : null,
                'assigned_at' => $row->assigned_at ? Carbon::parse($row->assigned_at) : null,
                'assigned_by' => $row->assigned_by,
                'removed_at' => $row->removed_at ? Carbon::parse($row->removed_at) : null,
                'removed_by' => $row->removed_by,
            ];
        });
    }
}
