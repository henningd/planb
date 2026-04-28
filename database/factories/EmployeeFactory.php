<?php

namespace Database\Factories;

use App\Enums\CrisisRole;
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
            'department_id' => null,
            'work_phone' => fake()->phoneNumber(),
            'mobile_phone' => fake()->phoneNumber(),
            'private_phone' => null,
            'email' => fake()->safeEmail(),
            'location_id' => null,
            'emergency_contact' => null,
            'is_key_personnel' => false,
            'crisis_role' => null,
            'is_crisis_deputy' => false,
            'notes' => null,
        ];
    }

    public function withCrisisRole(CrisisRole $role, bool $deputy = false): static
    {
        return $this->state(fn () => [
            'crisis_role' => $role,
            'is_crisis_deputy' => $deputy,
        ]);
    }
}
