<?php

namespace App\Support\Compliance;

use App\Enums\ComplianceCategory;

class CategoryReport
{
    /**
     * @param  list<array{check: Check, result: Result}>  $items
     */
    public function __construct(
        public readonly ComplianceCategory $category,
        public readonly array $items,
    ) {}

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

    public function passCount(): int
    {
        return count(array_filter(
            $this->items,
            fn (array $e) => $e['result']->status === Status::Pass,
        ));
    }

    public function totalCounted(): int
    {
        return count(array_filter(
            $this->items,
            fn (array $e) => $e['result']->isCounted(),
        ));
    }
}
