<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmergencyResourceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyResourceCategory>
 */
class EmergencyResourceCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'sort' => 0,
        ];
    }
}
