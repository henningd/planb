<?php

namespace Database\Factories;

use App\Enums\ProcessCriticality;
use App\Models\BusinessProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessProcess>
 */
class BusinessProcessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'criticality' => fake()->randomElement(ProcessCriticality::cases()),
            'mtpd_minutes' => fake()->randomElement([240, 480, 1440, 2880]),
            'rto_minutes' => fake()->randomElement([60, 120, 240, 480]),
            'rpo_minutes' => fake()->randomElement([15, 30, 60, 240]),
            'peak_times' => fake()->optional()->randomElement(['Mo–Fr 08–18 Uhr', 'Monatsende', 'Quartalsende']),
            'notes' => null,
            'sort' => 0,
        ];
    }
}
