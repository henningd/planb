<?php

namespace Database\Factories;

use App\Models\ManagementReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManagementReview>
 */
class ManagementReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'review_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'participants' => fake()->optional()->name().', '.fake()->name(),
            'summary' => fake()->optional()->paragraph(),
            'decisions' => fake()->optional()->paragraph(),
            'next_review_at' => fake()->dateTimeBetween('+6 months', '+12 months')->format('Y-m-d'),
            'conducted_by' => fake()->name(),
        ];
    }

    /**
     * Review mit bereits überschrittenem Folgetermin.
     */
    public function followUpOverdue(): self
    {
        return $this->state([
            'next_review_at' => now()->subDays(7)->format('Y-m-d'),
        ]);
    }
}
