<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\FordecDecision;
use App\Models\ScenarioRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FordecDecision>
 */
class FordecDecisionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'scenario_run_id' => ScenarioRun::factory(),
            'user_id' => null,
            'title' => fake()->optional()->sentence(3),
            'facts' => fake()->sentence(),
            'options' => fake()->sentence(),
            'risks_benefits' => fake()->sentence(),
            'decision' => fake()->sentence(),
            'execution' => fake()->sentence(),
            'check_at' => fake()->optional()->dateTimeBetween('now', '+2 days'),
            'created_by_name' => fake()->name(),
        ];
    }
}
