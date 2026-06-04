<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein revisionssicherer Eintrag im Krisen-Logbuch eines ScenarioRuns.
 * Hält automatisch erzeugte System-Einträge (Schritte, Alarmierung,
 * Lauf-Ende) sowie manuelle Notizen, Entscheidungen und Maßnahmen.
 */
#[Fillable([
    'company_id',
    'scenario_run_id',
    'user_id',
    'type',
    'message',
    'occurred_at',
])]
class CrisisLogEntry extends Model
{
    use BelongsToCurrentCompany, HasUuids;

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
            'occurred_at' => 'datetime',
        ];
    }
}
