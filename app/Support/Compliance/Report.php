<?php

namespace App\Support\Compliance;

use App\Enums\ComplianceCategory;
use App\Models\Company;
use Carbon\CarbonInterface;

class Report
{
    /**
     * @param  list<array{check: Check, result: Result}>  $items
     * @param  array<string, CategoryReport>  $categories  keyed by category value
     */
    public function __construct(
        public readonly Company $company,
        public readonly CarbonInterface $generatedAt,
        public readonly array $items,
        public readonly array $categories,
    ) {}

    /**
     * Gewichteter Gesamt-Score 0–100 über alle gewerteten Checks.
     */
    public function score(): int
    {
        $weighted = 0;
        $weights = 0;
        foreach ($this->items as $entry) {
            if (! $entry['result']->isCounted()) {
                continue;
            }
            $weighted += $entry['result']->score * $entry['check']->weight;
            $weights += $entry['check']->weight;
        }
        if ($weights === 0) {
            return 0;
        }

        return (int) round($weighted / $weights);
    }

    public function readinessLabel(): string
    {
        return match (true) {
            $this->score() >= 90 => 'Hervorragend',
            $this->score() >= 75 => 'Gut',
            $this->score() >= 50 => 'Ausbaufähig',
            $this->score() >= 25 => 'Kritisch',
            default => 'Nicht vorbereitet',
        };
    }

    public function readinessColor(): string
    {
        return match (true) {
            $this->score() >= 90 => 'emerald',
            $this->score() >= 75 => 'lime',
            $this->score() >= 50 => 'amber',
            $this->score() >= 25 => 'orange',
            default => 'rose',
        };
    }

    public function readinessHex(): string
    {
        return match (true) {
            $this->score() >= 90 => '#10b981',
            $this->score() >= 75 => '#84cc16',
            $this->score() >= 50 => '#f59e0b',
            $this->score() >= 25 => '#f97316',
            default => '#f43f5e',
        };
    }

    public function category(ComplianceCategory $category): ?CategoryReport
    {
        return $this->categories[$category->value] ?? null;
    }

    /**
     * Top-Aktionspunkte: Checks mit Status fail/partial, geordnet nach
     * potenziellem Score-Gewinn (Gewicht × verlorene Punkte).
     *
     * @return list<array{check: Check, result: Result, gain: int}>
     */
    public function topActions(int $limit = 5): array
    {
        $candidates = [];
        foreach ($this->items as $entry) {
            $result = $entry['result'];
            if (! $result->isCounted() || $result->status === Status::Pass) {
                continue;
            }
            $gain = $entry['check']->weight * (100 - $result->score);
            $candidates[] = ['check' => $entry['check'], 'result' => $result, 'gain' => $gain];
        }
        usort($candidates, fn ($a, $b) => $b['gain'] <=> $a['gain']);

        return array_slice($candidates, 0, $limit);
    }

    public function counts(): array
    {
        $counts = [Status::Pass->value => 0, Status::Partial->value => 0, Status::Fail->value => 0, Status::NotApplicable->value => 0];
        foreach ($this->items as $entry) {
            $counts[$entry['result']->status->value]++;
        }

        return $counts;
    }
}
