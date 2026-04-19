<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scenario>
 */
class ScenarioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Ransomware', 'Serverausfall', 'Stromausfall']),
            'description' => fake()->sentence(),
            'trigger' => fake()->sentence(),
        ];
    }
}
