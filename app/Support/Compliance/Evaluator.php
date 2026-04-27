<?php

namespace App\Support\Compliance;

use App\Enums\ComplianceCategory;
use App\Models\Company;

class Evaluator
{
    public static function for(Company $company): Report
    {
        $items = [];
        $byCategory = [];

        foreach (Catalog::all() as $check) {
            $result = $check->evaluate($company);
            $entry = ['check' => $check, 'result' => $result];
            $items[] = $entry;
            $byCategory[$check->category->value][] = $entry;
        }

        $categories = [];
        foreach (ComplianceCategory::ordered() as $category) {
            $categories[$category->value] = new CategoryReport(
                $category,
                $byCategory[$category->value] ?? [],
            );
        }

        return new Report(
            company: $company,
            generatedAt: now(),
            items: $items,
            categories: $categories,
        );
    }
}
