<?php

namespace Database\Factories;

use App\Enums\ScenarioRunMode;
use App\Models\Company;
use App\Models\ScenarioRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScenarioRun>
 */
class ScenarioRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'scenario_id' => null,
            'started_by_user_id' => null,
            'title' => fake()->sentence(3),
            'mode' => ScenarioRunMode::Drill,
            'started_at' => now(),
            'ended_at' => null,
            'aborted_at' => null,
            'summary' => null,
        ];
    }
}
