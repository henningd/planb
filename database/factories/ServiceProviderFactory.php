<?php

namespace Database\Factories;

use App\Enums\ServiceProviderType;
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
            'type' => ServiceProviderType::Other,
            'contact_name' => fake()->name(),
            'hotline' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'contract_number' => fake()->bothify('K-####-??'),
            'sla' => fake()->randomElement(['24/7', 'Mo-Fr 8-17', 'Mo-Fr 8-20, Sa 9-13']),
            'direct_order_limit' => null,
            'notes' => null,
        ];
    }

    public function ofType(ServiceProviderType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
