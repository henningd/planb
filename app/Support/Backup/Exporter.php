<?php

namespace App\Support\Backup;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sammelt die rohen DB-Zeilen der gewählten Bereiche für genau eine Firma.
 * Output ist die JSON-Struktur, die der Importer 1:1 wieder einlesen kann.
 */
class Exporter
{
    /**
     * @param  list<string>  $areaKeys
     * @return array<string, mixed>
     */
    public static function export(Company $company, array $areaKeys): array
    {
        $catalog = BackupCatalog::all();
        $areas = [];

        foreach ($areaKeys as $key) {
            $area = $catalog[$key] ?? null;
            if ($area === null) {
                continue;
            }

            if ($area['mode'] === 'update_single') {
                $row = DB::table($area['table'])->where('id', $company->id)->first();
                $areas[$key] = $row !== null ? [(array) $row] : [];

                continue;
            }

            $rows = self::fetchRowsForCompany($area, $company)
                ->map(fn ($r) => (array) $r)
                ->all();

            $areas[$key] = $rows;

            foreach ($area['nested'] ?? [] as $nested) {
                $parentIds = array_column($rows, 'id');
                $nestedRows = $parentIds === []
                    ? []
                    : DB::table($nested['table'])
                        ->whereIn($nested['fk'], $parentIds)
                        ->get()
                        ->map(fn ($r) => (array) $r)
                        ->all();
                $areas['_nested_'.$key.'_'.$nested['table']] = $nestedRows;
            }
        }

        return [
            'version' => 2,
            'exported_at' => now()->toIso8601String(),
            'company_id' => $company->id,
            'company_name' => $company->name,
            'areas' => $areas,
        ];
    }

    /**
     * Holt die Roh-Zeilen einer Tabelle für genau diese Firma — entweder
     * direkt über die company_id-Spalte oder (für Pivots/Sub-Tabellen ohne
     * eigene company_id) über die Parent-Tabelle.
     *
     * @param  array<string, mixed>  $area
     * @return Collection<int, object>
     */
    private static function fetchRowsForCompany(array $area, Company $company): Collection
    {
        if (isset($area['company_via'])) {
            $cv = $area['company_via'];
            $parentIds = DB::table($cv['parent_table'])
                ->where('company_id', $company->id)
                ->pluck('id');

            if ($parentIds->isEmpty()) {
                return collect();
            }

            return DB::table($area['table'])
                ->whereIn($cv['fk'], $parentIds)
                ->get();
        }

        return DB::table($area['table'])
            ->where('company_id', $company->id)
            ->get();
    }
}
