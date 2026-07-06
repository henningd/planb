<?php

namespace Database\Factories;

use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScenarioRunAcknowledgement>
 */
class ScenarioRunAcknowledgementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scenario_run_id' => ScenarioRun::factory(),
            'user_id' => User::factory(),
            'status' => ScenarioRunAcknowledgement::STATUS_SEEN,
            'acknowledged_at' => now(),
        ];
    }
}
