<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\PreventiveMeasureInterval;
use App\Enums\TrainingType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TrainingRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Schulungs-/Awareness-Nachweis: wer wurde wann zu welchem Thema geschult
 * (NIS2 Art. 21 – Cyberhygiene & verpflichtende Leitungsschulung).
 *
 * Ist ein `interval` gesetzt, handelt es sich um eine wiederkehrende Schulung
 * mit `next_due_at`; ohne Intervall ist es ein einmaliger Nachweis.
 */
#[Fillable([
    'company_id',
    'employee_id',
    'topic',
    'type',
    'completed_at',
    'interval',
    'next_due_at',
    'notes',
])]
class TrainingRecord extends Model
{
    /** @use HasFactory<TrainingRecordFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->topic;
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isOverdue(): bool
    {
        return $this->next_due_at !== null && $this->next_due_at->isPast();
    }

    /**
     * Schulung als absolviert markieren: setzt das Abschlussdatum und berechnet
     * bei wiederkehrenden Schulungen die nächste Fälligkeit.
     */
    public function markCompleted(?CarbonInterface $at = null): void
    {
        $at ??= CarbonImmutable::now();
        $this->completed_at = $at;

        if ($this->interval !== null) {
            $this->next_due_at = $at->copy()->addMonths($this->interval->months());
        }

        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TrainingType::class,
            'interval' => PreventiveMeasureInterval::class,
            'completed_at' => 'date',
            'next_due_at' => 'date',
        ];
    }
}
