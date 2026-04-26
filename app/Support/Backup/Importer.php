<?php

namespace App\Support\Backup;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Replace-Mode-Import: Für jeden gewählten Bereich werden ALLE Zeilen
 * dieses Mandanten gelöscht und durch die JSON-Inhalte ersetzt. Andere
 * Bereiche und andere Mandanten sind nicht betroffen.
 *
 * Caveat: cascadeOnDelete-Beziehungen (z. B. role_employee, system_*-
 * Pivots) werden beim DELETE mitgeräumt — Pivots gehören (noch) nicht
 * zum Backup, müssen also nach dem Import neu vergeben werden.
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

                $parentIds = DB::table($area['table'])
                    ->where('company_id', $company->id)
                    ->pluck('id');

                foreach ($area['nested'] ?? [] as $nested) {
                    if ($parentIds->isNotEmpty()) {
                        DB::table($nested['table'])
                            ->whereIn($nested['fk'], $parentIds)
                            ->delete();
                    }
                }

                $deleted = DB::table($area['table'])
                    ->where('company_id', $company->id)
                    ->delete();

                $summary[$key] = ['deleted' => $deleted];
            }

            // 2. INSERT in aufsteigender order — Eltern zuerst.
            foreach ($selected->sortBy('order') as $key => $area) {
                $rows = $payload['areas'][$key] ?? [];

                if ($area['mode'] === 'update_single') {
                    if ($rows !== []) {
                        $row = $rows[0];
                        // ID + team_id darf nicht aus dem Backup übernommen werden — sonst
                        // klauen wir uns selbst die Verbindung zum aktiven Team.
                        unset($row['id'], $row['team_id'], $row['created_at'], $row['updated_at'], $row['deleted_at']);
                        if ($row !== []) {
                            DB::table($area['table'])->where('id', $company->id)->update($row);
                        }
                        $summary[$key] = ['updated' => 1];
                    }

                    continue;
                }

                // company_id sicherheitshalber überschreiben — JSON könnte aus einem
                // anderen Mandanten kommen.
                $rows = array_map(function (array $row) use ($company): array {
                    $row['company_id'] = $company->id;

                    return $row;
                }, $rows);

                if ($rows !== []) {
                    DB::table($area['table'])->insert($rows);
                }

                $summary[$key]['inserted'] = count($rows);

                foreach ($area['nested'] ?? [] as $nested) {
                    $nestedRows = $payload['areas']['_nested_'.$key.'_'.$nested['table']] ?? [];
                    if ($nestedRows !== []) {
                        DB::table($nested['table'])->insert($nestedRows);
                    }
                }
            }
        });

        return $summary;
    }
}
