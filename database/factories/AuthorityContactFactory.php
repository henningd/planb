<?php

namespace Database\Factories;

use App\Enums\AuthorityContactType;
use App\Models\AuthorityContact;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthorityContact>
 */
class AuthorityContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => fake()->randomElement(AuthorityContactType::cases()),
            'name' => fake()->company().' Behörde',
            'occasion' => fake()->sentence(),
            'deadline' => fake()->randomElement(['unverzüglich', 'binnen 72 Stunden', 'binnen 3 Tagen', null]),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'contact_way' => fake()->optional()->url(),
            'address' => fake()->optional()->address(),
            'contact_name' => fake()->optional()->name(),
            'responsible_role_id' => null,
            'communication_template_id' => null,
            'notes' => fake()->optional()->sentence(),
            'sort' => 0,
        ];
    }
}
