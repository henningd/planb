<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Geschäftsführung', 'Vertrieb', 'Produktion', 'IT', 'Buchhaltung', 'Service', 'Verwaltung']),
            'description' => null,
            'sort' => 0,
        ];
    }
}
