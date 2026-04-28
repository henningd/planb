<?php

namespace App\Support\Backup;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Replace-Mode-Import: Für jeden gewählten Bereich werden ALLE Zeilen
 * dieses Mandanten gelöscht und durch die JSON-Inhalte ersetzt. Andere
 * Bereiche und andere Mandanten sind nicht betroffen.
 *
 * Pivots/Sub-Entitäten ohne eigene company_id werden über `company_via`
 * gefiltert (Parent-Tabelle). User-IDs (audited fields wie
 * assigned_by_user_id) werden beim Insert weggelassen, weil Users nicht
 * Teil des Backups sind.
 */
class Importer
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $areaKeys
     * @param  bool  $regenerateIds  Wenn true: alle IDs werden auf neue UUIDs
     *                               gemappt und referenzierende FK-Spalten
     *                               (siehe Catalog `id_remap`) entsprechend
     *                               umgeschrieben. Pflicht beim Apply von
     *                               Templates auf Firmen, in deren DB die
     *                               Source-IDs noch existieren würden.
     * @return array<string, array{deleted?: int, inserted?: int, updated?: int}>
     */
    public static function import(Company $company, array $payload, array $areaKeys, bool $regenerateIds = false): array
    {
        $catalog = BackupCatalog::all();
        $selected = collect($areaKeys)
            ->filter(fn (string $k) => isset($catalog[$k]))
            ->mapWithKeys(fn (string $k) => [$k => $catalog[$k]]);

        $summary = [];
        // Pro Catalog-Key: [old_id => new_id]. Wird beim Apply mit
        // regenerateIds aufgebaut und für FK-Remap genutzt.
        $idMap = [];

        DB::transaction(function () use ($company, $selected, $payload, $regenerateIds, &$summary, &$idMap) {
            // 1. DELETE in absteigender order — abhängige Bereiche zuerst.
            foreach ($selected->sortByDesc('order') as $key => $area) {
                if ($area['mode'] === 'update_single') {
                    continue;
                }

                $parentIds = self::companyRowIds($area, $company);

                foreach ($area['nested'] ?? [] as $nested) {
                    if ($parentIds->isNotEmpty()) {
                        DB::table($nested['table'])
                            ->whereIn($nested['fk'], $parentIds)
                            ->delete();
                    }
                }

                $deleted = isset($area['company_via'])
                    ? self::deleteByParent($area, $company)
                    : DB::table($area['table'])->where('company_id', $company->id)->delete();

                $summary[$key] = ['deleted' => $deleted];
            }

            // 2. INSERT in aufsteigender order — Eltern zuerst.
            foreach ($selected->sortBy('order') as $key => $area) {
                $rows = $payload['areas'][$key] ?? [];

                if ($area['mode'] === 'update_single') {
                    if ($rows !== []) {
                        $row = $rows[0];
                        unset($row['id'], $row['team_id'], $row['created_at'], $row['updated_at'], $row['deleted_at']);
                        if ($row !== []) {
                            DB::table($area['table'])->where('id', $company->id)->update($row);
                        }
                        $summary[$key] = ['updated' => 1];
                    }

                    continue;
                }

                if ($regenerateIds) {
                    $rows = self::regenerateRowIds($rows, $key, $idMap);
                    $rows = self::remapForeignKeys($rows, $area['id_remap'] ?? [], $idMap);
                }

                // Sonderfall „employees": das Legacy-Feld manager_id wird aus
                // den Insert-Reihen rausgenommen und nach dem Insert als
                // Pivot-Eintrag (employee_manager) angelegt. Damit bleiben
                // Industry-Template-Payloads, die noch mit dem alten Spalten-
                // Schema arbeiten, weiter kompatibel.
                //
                // Genauso für das Legacy-Feld department (String): wir
                // legen pro Firma eine Department-Zeile an (oder finden eine
                // bestehende) und mappen die ID auf department_id.
                $managerLinks = [];
                if ($key === 'employees') {
                    $deptIdByName = [];
                    foreach ($rows as &$row) {
                        if (array_key_exists('manager_id', $row)) {
                            $managerId = $row['manager_id'];
                            unset($row['manager_id']);
                            if ($managerId !== null && $managerId !== '') {
                                if ($regenerateIds && isset($idMap['employees'][$managerId])) {
                                    $managerId = $idMap['employees'][$managerId];
                                }
                                if (isset($row['id'])) {
                                    $managerLinks[] = [
                                        'employee_id' => $row['id'],
                                        'manager_id' => $managerId,
                                    ];
                                }
                            }
                        }

                        if (array_key_exists('department', $row)) {
                            $deptName = $row['department'] !== null ? trim((string) $row['department']) : '';
                            unset($row['department']);

                            if ($deptName === '') {
                                $row['department_id'] = $row['department_id'] ?? null;
                            } else {
                                if (! isset($deptIdByName[$deptName])) {
                                    $existingId = DB::table('departments')
                                        ->where('company_id', $company->id)
                                        ->where('name', $deptName)
                                        ->value('id');

                                    if ($existingId === null) {
                                        $existingId = (string) Str::uuid();
                                        DB::table('departments')->insert([
                                            'id' => $existingId,
                                            'company_id' => $company->id,
                                            'name' => $deptName,
                                            'description' => null,
                                            'sort' => 0,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                                    $deptIdByName[$deptName] = $existingId;
                                }
                                $row['department_id'] = $deptIdByName[$deptName];
                            }
                        }
                    }
                    unset($row);
                }

                $rows = self::prepareRowsForInsert($rows, $area, $company);

                if ($rows !== []) {
                    DB::table($area['table'])->insert($rows);
                }

                if ($managerLinks !== []) {
                    DB::table('employee_manager')->insertOrIgnore($managerLinks);
                }

                $summary[$key]['inserted'] = count($rows);

                foreach ($area['nested'] ?? [] as $nested) {
                    $nestedRows = $payload['areas']['_nested_'.$key.'_'.$nested['table']] ?? [];

                    if ($regenerateIds) {
                        // Nested-Rows: Parent-FK auf neue Parent-ID umbiegen,
                        // dann eigene ID neu generieren. Eigene IDs wandern
                        // unter dem Pseudo-Key '_nested_<area>_<table>' in den
                        // Map, falls weitere Tabellen darauf referenzieren
                        // (aktuell nicht, aber für die Zukunft konsistent).
                        $nestedRows = self::remapForeignKeys($nestedRows, [$nested['fk'] => $key], $idMap);
                        $nestedKey = '_nested_'.$key.'_'.$nested['table'];
                        $nestedRows = self::regenerateRowIds($nestedRows, $nestedKey, $idMap);
                    }

                    // Nested-Tabellen haben keine eigene company_id — wir
                    // markieren das mit company_via, damit prepareRowsForInsert
                    // die Forcierung überspringt.
                    $nestedArea = array_merge($nested, ['company_via' => true]);
                    $nestedRows = self::prepareRowsForInsert($nestedRows, $nestedArea, $company);
                    if ($nestedRows !== []) {
                        DB::table($nested['table'])->insert($nestedRows);
                    }
                }
            }
        });

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $area
     * @return Collection<int, mixed>
     */
    private static function companyRowIds(array $area, Company $company): Collection
    {
        if (isset($area['company_via'])) {
            return DB::table($area['table'])
                ->whereIn(
                    $area['company_via']['fk'],
                    DB::table($area['company_via']['parent_table'])
                        ->where('company_id', $company->id)
                        ->pluck('id'),
                )
                ->pluck('id');
        }

        return DB::table($area['table'])
            ->where('company_id', $company->id)
            ->pluck('id');
    }

    /**
     * @param  array<string, mixed>  $area
     */
    private static function deleteByParent(array $area, Company $company): int
    {
        $cv = $area['company_via'];
        $parentIds = DB::table($cv['parent_table'])
            ->where('company_id', $company->id)
            ->pluck('id');

        if ($parentIds->isEmpty()) {
            return 0;
        }

        return DB::table($area['table'])
            ->whereIn($cv['fk'], $parentIds)
            ->delete();
    }

    /**
     * Generiert für jede Zeile eine neue UUID und schreibt das Mapping
     * old→new in $idMap[$key]. Ist kein 'id' in der Zeile, wird einer
     * angelegt (für Pivots ohne id-Spalte greift das nicht).
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, array<string|int, string>>  $idMap
     * @return list<array<string, mixed>>
     */
    private static function regenerateRowIds(array $rows, string $key, array &$idMap): array
    {
        $idMap[$key] = $idMap[$key] ?? [];

        foreach ($rows as &$row) {
            if (! array_key_exists('id', $row)) {
                continue;
            }
            $oldId = $row['id'];
            $newId = (string) Str::uuid();
            $idMap[$key][$oldId] = $newId;
            $row['id'] = $newId;
        }
        unset($row);

        return $rows;
    }

    /**
     * Schreibt FK-Spalten auf die neuen IDs des verlinkten Bereichs um.
     * Wenn der alte Wert nicht im Map des verlinkten Bereichs liegt
     * (z. B. NULL oder Reference auf etwas außerhalb des Backups),
     * bleibt der Wert unverändert — wird im Worst Case beim Insert vom
     * FK-Constraint abgewiesen.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string>  $remap  fkColumn => catalogKey
     * @param  array<string, array<string|int, string>>  $idMap
     * @return list<array<string, mixed>>
     */
    private static function remapForeignKeys(array $rows, array $remap, array $idMap): array
    {
        if ($remap === []) {
            return $rows;
        }

        foreach ($rows as &$row) {
            foreach ($remap as $fkColumn => $targetKey) {
                if (! isset($row[$fkColumn])) {
                    continue;
                }
                $oldFk = $row[$fkColumn];
                if (isset($idMap[$targetKey][$oldFk])) {
                    $row[$fkColumn] = $idMap[$targetKey][$oldFk];
                }
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Bereitet die Roh-Zeilen für den Insert vor: company_id wird
     * forciert (wo passend), `strip_on_insert`-Felder werden entfernt.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $area
     * @return list<array<string, mixed>>
     */
    private static function prepareRowsForInsert(array $rows, array $area, Company $company): array
    {
        $strip = $area['strip_on_insert'] ?? [];
        $forceCompanyId = ! isset($area['company_via']);

        return array_map(function (array $row) use ($strip, $forceCompanyId, $company): array {
            foreach ($strip as $col) {
                unset($row[$col]);
            }
            if ($forceCompanyId) {
                $row['company_id'] = $company->id;
            }

            return $row;
        }, $rows);
    }
}
