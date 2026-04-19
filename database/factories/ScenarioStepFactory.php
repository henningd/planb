<?php

namespace Database\Factories;

use App\Models\Scenario;
use App\Models\ScenarioStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScenarioStep>
 */
class ScenarioStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scenario_id' => Scenario::factory(),
            'sort' => 1,
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'responsible' => fake()->randomElement(['Geschäftsführung', 'IT-Dienstleister', 'Mitarbeiter']),
        ];
    }
}
