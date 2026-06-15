<?php

namespace Database\Factories;

use App\Enums\PreventiveMeasureCategory;
use App\Enums\PreventiveMeasureEffectiveness;
use App\Enums\PreventiveMeasureInterval;
use App\Enums\PreventiveMeasureStatus;
use App\Models\PreventiveMeasure;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreventiveMeasure>
 */
class PreventiveMeasureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement(PreventiveMeasureCategory::cases()),
            'status' => PreventiveMeasureStatus::Active,
            'interval' => null,
            'target_date' => null,
            'last_executed_at' => null,
            'next_due_at' => null,
            'effectiveness' => PreventiveMeasureEffectiveness::NotAssessed,
            'sort' => 0,
        ];
    }

    public function forSystem(System $system): self
    {
        return $this->state([
            'system_id' => $system->id,
            'company_id' => $system->company_id,
        ]);
    }

    /**
     * Wiederkehrende Kontrolle mit Intervall und nächster Fälligkeit.
     */
    public function recurring(?PreventiveMeasureInterval $interval = null, ?string $nextDueAt = null): self
    {
        $interval ??= PreventiveMeasureInterval::Quarterly;

        return $this->state([
            'interval' => $interval,
            'last_executed_at' => now()->subMonths($interval->months())->format('Y-m-d'),
            'next_due_at' => $nextDueAt ?? now()->format('Y-m-d'),
        ]);
    }

    public function overdue(): self
    {
        return $this->state([
            'interval' => PreventiveMeasureInterval::Monthly,
            'next_due_at' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }
}
