<?php

namespace Database\Factories;

use App\Enums\HandbookTestInterval;
use App\Enums\HandbookTestType;
use App\Models\Company;
use App\Models\HandbookTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandbookTest>
 */
class HandbookTestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => HandbookTestType::ContactCheck,
            'name' => null,
            'description' => null,
            'interval' => HandbookTestInterval::Yearly,
            'last_executed_at' => null,
            'next_due_at' => now()->addMonths(12)->toDateString(),
            'responsible_employee_id' => null,
            'result_notes' => null,
            'sort' => 0,
        ];
    }

    public function ofType(HandbookTestType $type, HandbookTestInterval $interval): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'interval' => $interval,
        ]);
    }
}
