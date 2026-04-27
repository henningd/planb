<?php

namespace App\Support\Graph;

use App\Models\Company;
use App\Models\System;

/**
 * Berechnet einen Recovery-Zeitplan für alle Systeme einer Firma.
 *
 * Topologische Sortierung über die Abhängigkeitskette:
 *  - `start_minute` = max(`end_minute` aller Dependencies); 0 wenn keine
 *  - `end_minute` = `start_minute + rto_minutes` (rto_minutes default 60 wenn null)
 *
 * Systeme, die Teil eines Zyklus sind, werden separat ausgewiesen
 * und bekommen keine Zeitwerte.
 *
 * @phpstan-type TimelineEntry array{
 *   system: System,
 *   start: int,
 *   end: int,
 *   duration: int,
 *   rto_minutes: int,
 *   rto_missing: bool,
 *   level_color: string,
 * }
 */
class RecoveryTimelineBuilder
{
    public const DEFAULT_RTO_MINUTES = 60;

    /**
     * @return array{
     *   entries: list<TimelineEntry>,
     *   cycles: list<array{id: string, name: string}>,
     *   total_minutes: int,
     *   missing_rto_count: int,
     *   stats: array{systems: int, total_minutes: int, missing_rto: int, cycles: int},
     * }
     */
    public static function build(Company $company): array
    {
        $systems = System::query()
            ->where('company_id', $company->id)
            ->with(['emergencyLevel', 'dependencies:id'])
            ->orderBy('name')
            ->get();

        /** @var array<string, System> $byId */
        $byId = $systems->keyBy('id')->all();

        $adj = [];
        $incoming = [];
        foreach ($systems as $sys) {
            $adj[$sys->id] = [];
            $incoming[$sys->id] = [];
        }
        foreach ($systems as $sys) {
            foreach ($sys->dependencies as $dep) {
                if (! isset($byId[$dep->id])) {
                    continue;
                }
                $adj[$sys->id][$dep->id] = true;
                $incoming[$dep->id][$sys->id] = true;
            }
        }

        $cycleIds = self::detectCycleNodes($adj);

        $endMinute = [];
        $startMinute = [];
        $missingRto = [];

        $resolved = [];
        $remaining = array_diff_key($byId, $cycleIds);

        while ($remaining !== []) {
            $progressed = false;

            foreach ($remaining as $id => $sys) {
                $depsIds = array_keys($adj[$id]);
                $allReady = true;
                foreach ($depsIds as $depId) {
                    if (isset($cycleIds[$depId])) {
                        $allReady = false;
                        break;
                    }
                    if (! isset($resolved[$depId])) {
                        $allReady = false;
                        break;
                    }
                }
                if (! $allReady) {
                    continue;
                }

                $start = 0;
                foreach ($depsIds as $depId) {
                    if (($endMinute[$depId] ?? 0) > $start) {
                        $start = $endMinute[$depId];
                    }
                }

                $rto = $sys->rto_minutes;
                $rtoMissing = $rto === null || $rto <= 0;
                if ($rtoMissing) {
                    $rto = self::DEFAULT_RTO_MINUTES;
                    $missingRto[$id] = true;
                }

                $startMinute[$id] = $start;
                $endMinute[$id] = $start + $rto;
                $resolved[$id] = true;
                unset($remaining[$id]);
                $progressed = true;
            }

            if (! $progressed) {
                foreach ($remaining as $id => $_sys) {
                    $cycleIds[$id] = true;
                }
                break;
            }
        }

        $entries = [];
        foreach ($byId as $id => $sys) {
            if (isset($cycleIds[$id])) {
                continue;
            }
            $start = $startMinute[$id];
            $end = $endMinute[$id];
            $duration = $end - $start;
            $entries[] = [
                'system' => $sys,
                'start' => $start,
                'end' => $end,
                'duration' => $duration,
                'rto_minutes' => $duration,
                'rto_missing' => isset($missingRto[$id]),
                'level_color' => self::levelColor($sys->emergencyLevel?->sort),
            ];
        }

        usort($entries, static function (array $a, array $b): int {
            if ($a['start'] !== $b['start']) {
                return $a['start'] <=> $b['start'];
            }
            if ($a['end'] !== $b['end']) {
                return $a['end'] <=> $b['end'];
            }

            return strcasecmp($a['system']->name, $b['system']->name);
        });

        $cycles = [];
        foreach (array_keys($cycleIds) as $id) {
            if (! isset($byId[$id])) {
                continue;
            }
            $cycles[] = [
                'id' => (string) $id,
                'name' => (string) $byId[$id]->name,
            ];
        }
        usort($cycles, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        $totalMinutes = 0;
        foreach ($entries as $entry) {
            if ($entry['end'] > $totalMinutes) {
                $totalMinutes = $entry['end'];
            }
        }

        return [
            'entries' => $entries,
            'cycles' => $cycles,
            'total_minutes' => $totalMinutes,
            'missing_rto_count' => count($missingRto),
            'stats' => [
                'systems' => count($entries),
                'total_minutes' => $totalMinutes,
                'missing_rto' => count($missingRto),
                'cycles' => count($cycles),
            ],
        ];
    }

    /**
     * Liefert eine kompakte deutsche Dauer-Beschriftung wie „1 h 30 min".
     */
    public static function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 min';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return $rest.' min';
        }
        if ($rest === 0) {
            return $hours.' h';
        }

        return $hours.' h '.$rest.' min';
    }

    /**
     * Hex-Farbe für einen Notfall-Level-Sort-Wert.
     */
    public static function levelColor(?int $sort): string
    {
        return match ($sort) {
            1 => '#f43f5e',
            2 => '#f59e0b',
            3 => '#0ea5e9',
            4 => '#10b981',
            default => '#71717a',
        };
    }

    /**
     * Iteratives Tarjan-ähnliches Verfahren: liefert IDs der Knoten,
     * die Teil eines (gerichteten) Zyklus sind.
     *
     * @param  array<string, array<string, bool>>  $adj
     * @return array<string, bool>
     */
    private static function detectCycleNodes(array $adj): array
    {
        $visited = [];
        $stack = [];
        $inCycle = [];

        $dfs = function (string $node) use (&$dfs, &$adj, &$visited, &$stack, &$inCycle): void {
            $visited[$node] = true;
            $stack[$node] = true;
            foreach (array_keys($adj[$node] ?? []) as $next) {
                if (! isset($visited[$next])) {
                    $dfs($next);
                } elseif (isset($stack[$next])) {
                    $inCycle[$node] = true;
                    $inCycle[$next] = true;
                }
            }
            unset($stack[$node]);
        };

        foreach (array_keys($adj) as $node) {
            if (! isset($visited[$node])) {
                $dfs($node);
            }
        }

        return $inCycle;
    }
}
