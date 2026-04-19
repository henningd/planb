<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'position' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Geschäftsführung', 'Vertrieb', 'Produktion', 'IT', 'Buchhaltung', 'Service']),
            'work_phone' => fake()->phoneNumber(),
            'mobile_phone' => fake()->phoneNumber(),
            'private_phone' => null,
            'email' => fake()->safeEmail(),
            'location' => null,
            'emergency_contact' => null,
            'manager_id' => null,
            'is_key_personnel' => false,
            'notes' => null,
        ];
    }
}
