<?php

namespace Database\Factories;

use App\Enums\SystemCategory;
use App\Models\Company;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<System>
 */
class SystemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(SystemCategory::cases()),
            'system_priority_id' => null,
        ];
    }
}
