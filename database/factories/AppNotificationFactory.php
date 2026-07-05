<?php

namespace Database\Factories;

use App\Models\AppNotification;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppNotification>
 */
class AppNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => 'incident_started',
            'title' => 'Notfall gemeldet',
            'body' => fake()->sentence(3),
            'scenario_run_id' => null,
        ];
    }
}
