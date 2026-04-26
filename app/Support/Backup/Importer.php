<?php

namespace App\Support\Backup;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * @return array<string, array{deleted?: int, inserted?: int, updated?: int}>
     */
    public static function import(Company $company, array $payload, array $areaKeys): array
    {
        $catalog = BackupCatalog::all();
        $selected = collect($areaKeys)
            ->filter(fn (string $k) => isset($catalog[$k]))
            ->mapWithKeys(fn (string $k) => [$k => $catalog[$k]]);

        $summary = [];

        DB::transaction(function () use ($company, $selected, $payload, &$summary) {
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

                $rows = self::prepareRowsForInsert($rows, $area, $company);

                if ($rows !== []) {
                    DB::table($area['table'])->insert($rows);
                }

                $summary[$key]['inserted'] = count($rows);

                foreach ($area['nested'] ?? [] as $nested) {
                    $nestedRows = $payload['areas']['_nested_'.$key.'_'.$nested['table']] ?? [];
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
