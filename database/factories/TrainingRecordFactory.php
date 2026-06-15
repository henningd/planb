<?php

namespace Database\Factories;

use App\Enums\PreventiveMeasureInterval;
use App\Enums\TrainingType;
use App\Models\Employee;
use App\Models\TrainingRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingRecord>
 */
class TrainingRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic' => fake()->sentence(3),
            'type' => fake()->randomElement(TrainingType::cases()),
            'completed_at' => now()->subMonths(2)->format('Y-m-d'),
            'interval' => null,
            'next_due_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forEmployee(Employee $employee): self
    {
        return $this->state([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
        ]);
    }

    /**
     * Wiederkehrende Schulung mit Intervall und nächster Fälligkeit.
     */
    public function recurring(?PreventiveMeasureInterval $interval = null, ?string $nextDueAt = null): self
    {
        $interval ??= PreventiveMeasureInterval::Yearly;

        return $this->state([
            'interval' => $interval,
            'next_due_at' => $nextDueAt ?? now()->addMonths($interval->months())->format('Y-m-d'),
        ]);
    }

    public function overdue(): self
    {
        return $this->state([
            'interval' => PreventiveMeasureInterval::Yearly,
            'next_due_at' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }
}
