<?php

namespace Database\Factories;

use App\Enums\BcmPolicyStatus;
use App\Models\BcmPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BcmPolicy>
 */
class BcmPolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scope' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'version' => '1.0',
            'status' => BcmPolicyStatus::Draft,
            'approved_by' => null,
            'approved_at' => null,
            'review_due_at' => null,
        ];
    }

    /**
     * Freigegebene Leitlinie mit Freigabevermerk und Review-Termin.
     */
    public function approved(?string $approvedBy = null): self
    {
        return $this->state([
            'status' => BcmPolicyStatus::Approved,
            'approved_by' => $approvedBy ?? fake()->name(),
            'approved_at' => now()->format('Y-m-d'),
            'review_due_at' => now()->addYear()->format('Y-m-d'),
        ]);
    }

    /**
     * Freigegebene Leitlinie mit überfälligem Review.
     */
    public function reviewOverdue(): self
    {
        return $this->approved()->state([
            'review_due_at' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }
}
