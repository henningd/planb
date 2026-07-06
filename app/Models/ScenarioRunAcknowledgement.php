<?php

namespace App\Models;

use Database\Factories\ScenarioRunAcknowledgementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alarm-Quittierung eines Nutzers zu einem Notfall-Ablauf („gesehen" oder
 * „übernehme"). Pro (Run, User) existiert höchstens eine Quittierung —
 * `taking_over` ersetzt `seen`, ein späteres `seen` downgraded nie.
 */
#[Fillable([
    'scenario_run_id',
    'user_id',
    'status',
    'acknowledged_at',
])]
class ScenarioRunAcknowledgement extends Model
{
    public const STATUS_SEEN = 'seen';

    public const STATUS_TAKING_OVER = 'taking_over';

    /** @use HasFactory<ScenarioRunAcknowledgementFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<ScenarioRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ScenarioRun::class, 'scenario_run_id');
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
            'acknowledged_at' => 'datetime',
        ];
    }
}
