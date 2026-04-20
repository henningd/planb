<?php

namespace Database\Factories;

use App\Models\System;
use App\Models\SystemTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemTask>
 */
class SystemTaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'due_date' => fake()->optional()->dateTimeBetween('+1 day', '+3 months')?->format('Y-m-d'),
            'completed_at' => null,
        ];
    }

    public function forSystem(System $system): self
    {
        return $this->state([
            'system_id' => $system->id,
            'company_id' => $system->company_id,
        ]);
    }

    public function completed(): self
    {
        return $this->state(['completed_at' => now()]);
    }
}
