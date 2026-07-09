<?php

namespace Database\Factories;

use App\Enums\OpenItemConversion;
use App\Enums\OpenItemStatus;
use App\Models\Company;
use App\Models\OpenItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenItem>
 */
class OpenItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->sentence(4),
            'relevance' => fake()->optional()->sentence(),
            'risk_id' => null,
            'responsible_employee_id' => null,
            'responsible_role_id' => null,
            'due_at' => fake()->optional()->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'review_at' => fake()->optional()->dateTimeBetween('+3 months', '+9 months')?->format('Y-m-d'),
            'status' => OpenItemStatus::Open,
            'conversion' => null,
            'resolution_note' => null,
            'resolved_at' => null,
            'sort' => 0,
        ];
    }

    /**
     * Frist bereits überschritten, weiterhin offen.
     */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_at' => now()->subWeek()->format('Y-m-d'),
            'status' => OpenItemStatus::Open,
        ]);
    }

    /**
     * Erledigt und in ein Artefakt überführt.
     */
    public function resolved(OpenItemConversion $conversion = OpenItemConversion::Measure): static
    {
        return $this->state(fn () => [
            'status' => OpenItemStatus::Resolved,
            'conversion' => $conversion,
            'resolved_at' => now(),
        ]);
    }
}
