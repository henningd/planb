<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Hauptsitz', 'Niederlassung', 'Lager', 'Filiale']),
            'street' => fake()->streetAddress(),
            'postal_code' => (string) fake()->numberBetween(10000, 99999),
            'city' => fake()->city(),
            'country' => 'DE',
            'is_headquarters' => false,
            'phone' => fake()->phoneNumber(),
            'notes' => null,
            'sort' => 0,
        ];
    }

    public function headquarters(): static
    {
        return $this->state(fn () => ['is_headquarters' => true, 'name' => 'Hauptsitz']);
    }
}
