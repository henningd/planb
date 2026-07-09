<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Database\Factories\FordecDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eine nachvollziehbar dokumentierte Krisenentscheidung nach dem FORDEC-Schema
 * (Facts, Options, Risks & Benefits, Decision, Execution, Check). Gehört zu
 * einem laufenden Vorfall (ScenarioRun) und wird zusätzlich als Eintrag ins
 * Krisen-Logbuch geschrieben. Append-only – wie das Logbuch nicht editierbar.
 */
#[Fillable([
    'company_id',
    'scenario_run_id',
    'user_id',
    'title',
    'facts',
    'options',
    'risks_benefits',
    'decision',
    'execution',
    'check_at',
    'created_by_name',
])]
class FordecDecision extends Model
{
    /** @use HasFactory<FordecDecisionFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids;

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_at' => 'datetime',
        ];
    }
}
