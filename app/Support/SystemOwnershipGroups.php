<?php

namespace App\Support;

use App\Enums\SystemOwnership;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Gruppiert Mitarbeiter / Dienstleister / Rollen-Zuordnungen eines Systems
 * nach Eigentums-Kategorie (Owner / Operator / Ansprechpartner) und
 * trennt Hauptpersonen von Vertretungen.
 *
 * Erwartet, dass das Pivot die Spalten `ownership_kind` und `is_deputy`
 * trägt (siehe Migration add_ownership_kind_to_system_pivots).
 *
 * @phpstan-type GroupBucket array{
 *   kind: SystemOwnership,
 *   primaries: SupportCollection<int, mixed>,
 *   deputies: SupportCollection<int, mixed>,
 *   total: int,
 * }
 */
class SystemOwnershipGroups
{
    /**
     * @param  Collection<int, mixed>  $items
     * @return array<string, GroupBucket>
     */
    public static function group(Collection $items): array
    {
        $by = [];
        foreach (SystemOwnership::ordered() as $kind) {
            $by[$kind->value] = [
                'kind' => $kind,
                'primaries' => collect(),
                'deputies' => collect(),
                'total' => 0,
            ];
        }

        foreach ($items as $item) {
            $kindValue = $item->pivot->ownership_kind ?? null;
            $kind = $kindValue !== null ? SystemOwnership::tryFrom($kindValue) : null;
            if ($kind === null) {
                continue;
            }
            $bucket = &$by[$kind->value];
            $isDeputy = (bool) ($item->pivot->is_deputy ?? false);
            if ($isDeputy) {
                $bucket['deputies']->push($item);
            } else {
                $bucket['primaries']->push($item);
            }
            $bucket['total']++;
            unset($bucket);
        }

        return $by;
    }

    /**
     * Items ohne `ownership_kind` (zur Anzeige in einer „Sonstige"-Sektion).
     *
     * @param  Collection<int, mixed>  $items
     * @return SupportCollection<int, mixed>
     */
    public static function unassigned(Collection $items): SupportCollection
    {
        return $items->filter(fn ($i) => empty($i->pivot->ownership_kind))->values();
    }
}
