<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use Database\Factories\LessonLearnedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'incident_report_id',
    'scenario_run_id',
    'title',
    'root_cause',
    'what_went_well',
    'what_went_poorly',
    'author_user_id',
    'finalized_at',
])]
class LessonLearned extends Model
{
    /** @use HasFactory<LessonLearnedFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    protected $table = 'lessons_learned';

    /**
     * @return BelongsTo<IncidentReport, $this>
     */
    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class);
    }

    /**
     * @return BelongsTo<ScenarioRun, $this>
     */
    public function scenarioRun(): BelongsTo
    {
        return $this->belongsTo(ScenarioRun::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /**
     * @return HasMany<LessonLearnedActionItem, $this>
     */
    public function actionItems(): HasMany
    {
        return $this->hasMany(LessonLearnedActionItem::class)->orderByRaw('due_date IS NULL, due_date asc');
    }

    public function subject(): IncidentReport|ScenarioRun|null
    {
        return $this->incidentReport ?? $this->scenarioRun;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'finalized_at' => 'datetime',
        ];
    }
}
