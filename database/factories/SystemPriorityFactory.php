<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SystemPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemPriority>
 */
class SystemPriorityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Kritisch', 'Hoch', 'Normal']),
            'description' => fake()->sentence(),
            'sort' => 0,
        ];
    }
}
