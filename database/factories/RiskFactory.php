<?php

namespace Database\Factories;

use App\Enums\RiskCategory;
use App\Enums\RiskStatus;
use App\Models\Company;
use App\Models\Risk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Risk>
 */
class RiskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => RiskCategory::Operational,
            'probability' => fake()->numberBetween(1, 5),
            'impact' => fake()->numberBetween(1, 5),
            'residual_probability' => null,
            'residual_impact' => null,
            'status' => RiskStatus::Identified,
            'treatment_strategy' => null,
            'owner_user_id' => null,
            'review_due_at' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn () => [
            'probability' => 5,
            'impact' => 5,
        ]);
    }

    public function mitigated(): static
    {
        return $this->state(fn () => [
            'status' => RiskStatus::Mitigated,
            'residual_probability' => 2,
            'residual_impact' => 2,
        ]);
    }

    public function overdueReview(): static
    {
        return $this->state(fn () => [
            'review_due_at' => now()->subDays(7)->toDateString(),
        ]);
    }
}
