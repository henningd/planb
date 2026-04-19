<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmergencyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyLevel>
 */
class EmergencyLevelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Kritisch', 'Wichtig', 'Beobachten']),
            'description' => fake()->sentence(),
            'reaction' => fake()->sentence(),
            'sort' => 0,
        ];
    }
}
