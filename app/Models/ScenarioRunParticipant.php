<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Heartbeat-basierte Präsenz: welcher Nutzer (Web/App) gerade an einem
 * laufenden Notfall arbeitet. Ein Eintrag je (Run, User), per Heartbeat
 * aktualisiert; „aktiv" = last_seen_at innerhalb des Frischefensters.
 */
#[Fillable([
    'scenario_run_id',
    'user_id',
    'last_seen_at',
])]
class ScenarioRunParticipant extends Model
{
    use HasUuids;

    /** Frischefenster in Sekunden: danach gilt ein Teilnehmer als nicht mehr aktiv. */
    public const FRESH_SECONDS = 120;

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
            'last_seen_at' => 'datetime',
        ];
    }
}
