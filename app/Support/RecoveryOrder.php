<?php

namespace App\Support;

use App\Models\System;
use Illuminate\Support\Collection;

/**
 * Computes the order in which systems should be brought back up after an
 * outage. A dependency A→B (A depends on B) means B must come up first.
 * Systems on the same stage have no dependency chain between them and can
 * be recovered in parallel.
 */
class RecoveryOrder
{
    /**
     * @param  Collection<int, System>  $systems  pre-loaded with `dependencies` and `priority`.
     * @return array{stages: array<int, array<int, System>>, cycles: array<int, System>}
     */
    public static function compute(Collection $systems): array
    {
        $byId = $systems->keyBy('id');
        $incoming = [];
        $outgoing = [];

        foreach ($systems as $system) {
            $incoming[$system->id] = [];
            $outgoing[$system->id] = [];
        }

        foreach ($systems as $system) {
            foreach ($system->dependencies as $dep) {
                if (! isset($byId[$dep->id])) {
                    continue;
                }
                $incoming[$system->id][$dep->id] = true;
                $outgoing[$dep->id][$system->id] = true;
            }
        }

        $stages = [];
        $remaining = array_fill_keys($systems->pluck('id')->all(), true);

        while ($remaining !== []) {
            $ready = [];
            foreach (array_keys($remaining) as $id) {
                if ($incoming[$id] === []) {
                    $ready[] = $byId[$id];
                }
            }

            if ($ready === []) {
                break;
            }

            usort($ready, static fn (System $a, System $b) => self::compareForRecovery($a, $b));

            $stages[] = $ready;

            foreach ($ready as $system) {
                unset($remaining[$system->id]);
                foreach (array_keys($outgoing[$system->id]) as $dependentId) {
                    unset($incoming[$dependentId][$system->id]);
                }
            }
        }

        $cycles = array_values(array_map(fn (string $id) => $byId[$id], array_keys($remaining)));

        return ['stages' => $stages, 'cycles' => $cycles];
    }

    protected static function compareForRecovery(System $a, System $b): int
    {
        $sortA = $a->priority?->sort ?? PHP_INT_MAX;
        $sortB = $b->priority?->sort ?? PHP_INT_MAX;

        if ($sortA !== $sortB) {
            return $sortA <=> $sortB;
        }

        $rtoA = $a->rto_minutes ?? PHP_INT_MAX;
        $rtoB = $b->rto_minutes ?? PHP_INT_MAX;

        if ($rtoA !== $rtoB) {
            return $rtoA <=> $rtoB;
        }

        return strcasecmp($a->name, $b->name);
    }
}
