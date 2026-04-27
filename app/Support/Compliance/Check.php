<?php

namespace App\Support\Compliance;

use App\Enums\ComplianceCategory;
use App\Models\Company;
use Closure;

/**
 * Definition eines Compliance-Checks.
 *
 * Der Evaluator-Closure erhält die Company und liefert ein Result zurück.
 */
class Check
{
    /**
     * @param  Closure(Company): Result  $evaluator
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly ComplianceCategory $category,
        public readonly int $weight,
        public readonly Closure $evaluator,
    ) {}

    public function evaluate(Company $company): Result
    {
        return ($this->evaluator)($company);
    }
}
