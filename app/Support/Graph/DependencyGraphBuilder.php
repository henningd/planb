<?php

namespace App\Support\Graph;

use App\Enums\SystemCategory;
use App\Models\Company;
use App\Models\System;
use Illuminate\Database\Eloquent\Collection;

/**
 * Baut die Daten für die System-Abhängigkeits-Visualisierung.
 *
 * Liefert ein Cytoscape-kompatibles Format `{ nodes: [...], edges: [...] }`,
 * das clientseitig direkt rendern kann.
 *
 * @phpstan-type GraphNode array{
 *   data: array{
 *     id: string,
 *     label: string,
 *     category: string,
 *     category_label: string,
 *     level: ?string,
 *     level_color: string,
 *     level_text: string,
 *     rto: ?int,
 *     show_url: string,
 *     dependencies_count: int,
 *     dependents_count: int,
 *   }
 * }
 * @phpstan-type GraphEdge array{
 *   data: array{
 *     id: string,
 *     source: string,
 *     target: string,
 *     note: ?string,
 *   }
 * }
 */
class DependencyGraphBuilder
{
    /**
     * @return array{
     *   nodes: list<GraphNode>,
     *   edges: list<GraphEdge>,
     *   stats: array{systems: int, edges: int, isolated: int, cycles: int},
     *   levels: list<array{id: string, name: string, color: string}>,
     *   categories: list<array{value: string, label: string}>,
     * }
     */
    public static function build(Company $company): array
    {
        $systems = System::query()
            ->where('company_id', $company->id)
            ->with(['emergencyLevel', 'dependencies:id,name'])
            ->orderBy('name')
            ->get();

        $nodes = [];
        $edges = [];
        $isolated = 0;
        $teamSlug = $company->team?->slug;

        foreach ($systems as $system) {
            $level = $system->emergencyLevel;
            $color = self::levelColor($level?->sort);
            $depsCount = $system->dependencies->count();
            $depCount = $system->dependents()->count();
            if ($depsCount === 0 && $depCount === 0) {
                $isolated++;
            }
            $cat = $system->category instanceof SystemCategory ? $system->category : null;

            $nodes[] = [
                'data' => [
                    'id' => (string) $system->id,
                    'label' => (string) $system->name,
                    'category' => $cat?->value ?? '',
                    'category_label' => $cat?->label() ?? '',
                    'level' => $level?->id,
                    'level_name' => $level?->name ?? '–',
                    'level_color' => $color['bg'],
                    'level_text' => $color['text'],
                    'level_border' => $color['border'],
                    'rto' => $system->rto_minutes,
                    'show_url' => $teamSlug
                        ? route('systems.show', ['current_team' => $teamSlug, 'system' => $system->id])
                        : '#',
                    'dependencies_count' => $depsCount,
                    'dependents_count' => $depCount,
                ],
            ];

            foreach ($system->dependencies as $dep) {
                $edges[] = [
                    'data' => [
                        'id' => (string) $system->id.'__'.(string) $dep->id,
                        'source' => (string) $system->id,
                        'target' => (string) $dep->id,
                        'note' => $dep->pivot->note ?? null,
                    ],
                ];
            }
        }

        $cycleCount = self::countCycles($systems);
        $levels = self::levels($systems);
        $categories = collect(SystemCategory::cases())
            ->map(fn (SystemCategory $c) => ['value' => $c->value, 'label' => $c->label()])
            ->values()
            ->all();

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'stats' => [
                'systems' => $systems->count(),
                'edges' => count($edges),
                'isolated' => $isolated,
                'cycles' => $cycleCount,
            ],
            'levels' => $levels,
            'categories' => $categories,
        ];
    }

    /**
     * @return array{bg: string, text: string, border: string}
     */
    private static function levelColor(?int $sort): array
    {
        return match ($sort) {
            1 => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#dc2626'],
            2 => ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#d97706'],
            3 => ['bg' => '#e0f2fe', 'text' => '#075985', 'border' => '#0284c7'],
            4 => ['bg' => '#dcfce7', 'text' => '#166534', 'border' => '#16a34a'],
            default => ['bg' => '#f4f4f5', 'text' => '#3f3f46', 'border' => '#71717a'],
        };
    }

    /**
     * @param  Collection<int, System>  $systems
     * @return list<array{id: string, name: string, color: string}>
     */
    private static function levels($systems): array
    {
        $seen = [];
        foreach ($systems as $sys) {
            $lvl = $sys->emergencyLevel;
            if (! $lvl || isset($seen[$lvl->id])) {
                continue;
            }
            $seen[$lvl->id] = [
                'id' => (string) $lvl->id,
                'name' => (string) $lvl->name,
                'color' => self::levelColor($lvl->sort)['border'],
                'sort' => $lvl->sort,
            ];
        }
        $list = array_values($seen);
        usort($list, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return array_map(fn ($l) => ['id' => $l['id'], 'name' => $l['name'], 'color' => $l['color']], $list);
    }

    /**
     * Zählt Knoten, die Teil eines Zyklus in den Abhängigkeiten sind.
     *
     * @param  Collection<int, System>  $systems
     */
    private static function countCycles($systems): int
    {
        $adj = [];
        foreach ($systems as $sys) {
            $adj[$sys->id] = $sys->dependencies->pluck('id')->all();
        }
        $visited = [];
        $stack = [];
        $inCycle = [];
        $dfs = function (string $node) use (&$dfs, &$adj, &$visited, &$stack, &$inCycle) {
            $visited[$node] = true;
            $stack[$node] = true;
            foreach ($adj[$node] ?? [] as $next) {
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

        return count($inCycle);
    }
}
