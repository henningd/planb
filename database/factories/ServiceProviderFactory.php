<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceProvider>
 */
class ServiceProviderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'hotline' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'contract_number' => fake()->bothify('K-####-??'),
            'sla' => fake()->randomElement(['24/7', 'Mo-Fr 8-17', 'Mo-Fr 8-20, Sa 9-13']),
            'notes' => null,
        ];
    }
}
