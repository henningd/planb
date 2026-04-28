<?php

namespace Database\Factories;

use App\Enums\LessonLearnedActionItemStatus;
use App\Models\LessonLearned;
use App\Models\LessonLearnedActionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonLearnedActionItem>
 */
class LessonLearnedActionItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_learned_id' => LessonLearned::factory(),
            'description' => fake()->sentence(),
            'responsible_employee_id' => null,
            'due_date' => null,
            'status' => LessonLearnedActionItemStatus::Open,
            'completed_at' => null,
            'notes' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(3)->toDateString(),
            'status' => LessonLearnedActionItemStatus::Open,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => LessonLearnedActionItemStatus::Done,
            'completed_at' => now(),
        ]);
    }
}
