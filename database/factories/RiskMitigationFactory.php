<?php

namespace Database\Factories;

use App\Enums\RiskMitigationStatus;
use App\Models\Risk;
use App\Models\RiskMitigation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiskMitigation>
 */
class RiskMitigationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'risk_id' => Risk::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => RiskMitigationStatus::Planned,
            'target_date' => now()->addDays(30)->toDateString(),
            'implemented_at' => null,
            'responsible_employee_id' => null,
        ];
    }

    public function implemented(): static
    {
        return $this->state(fn () => [
            'status' => RiskMitigationStatus::Implemented,
            'implemented_at' => now()->toDateString(),
        ]);
    }
}
