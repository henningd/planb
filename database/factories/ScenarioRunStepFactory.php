<?php

namespace Database\Factories;

use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScenarioRunStep>
 */
class ScenarioRunStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scenario_run_id' => ScenarioRun::factory(),
            'sort' => 1,
            'title' => fake()->sentence(4),
            'description' => null,
            'responsible' => null,
            'checked_at' => null,
            'checked_by_user_id' => null,
            'note' => null,
        ];
    }
}
