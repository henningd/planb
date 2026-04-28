<?php

namespace App\Models;

use App\Enums\LessonLearnedActionItemStatus;
use Database\Factories\LessonLearnedActionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lesson_learned_id',
    'description',
    'responsible_employee_id',
    'due_date',
    'status',
    'completed_at',
    'notes',
])]
class LessonLearnedActionItem extends Model
{
    /** @use HasFactory<LessonLearnedActionItemFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<LessonLearned, $this>
     */
    public function lessonLearned(): BelongsTo
    {
        return $this->belongsTo(LessonLearned::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function isOverdue(): bool
    {
        if ($this->status === LessonLearnedActionItemStatus::Done) {
            return false;
        }

        if ($this->status === LessonLearnedActionItemStatus::Cancelled) {
            return false;
        }

        return $this->due_date !== null && $this->due_date->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LessonLearnedActionItemStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
