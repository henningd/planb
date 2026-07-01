<?php

namespace Database\Factories;

use App\Enums\ContractCoverage;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'service_provider_id' => ServiceProvider::factory(),
            'title' => fake()->randomElement(['Wartungsvertrag', 'Servicevertrag', 'SLA-Rahmenvertrag']).' '.fake()->word(),
            'contract_number' => fake()->bothify('V-####-??'),
            'coverage' => fake()->randomElement(ContractCoverage::cases()),
            'service_hours' => fake()->randomElement(['Mo–Fr 8–17', '24/7', 'Mo–Sa 7–20']),
            'response_time_minutes' => fake()->randomElement([60, 240, 480]),
            'resolution_time_minutes' => fake()->randomElement([480, 1440, 4320]),
            'availability_percent' => fake()->randomElement([95.0, 99.0, 99.9]),
            'emergency_hotline' => fake()->phoneNumber(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'notes' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['end_date' => now()->subDay()->toDateString()]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => ['end_date' => now()->addDays(10)->toDateString()]);
    }
}
