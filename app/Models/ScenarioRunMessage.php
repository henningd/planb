<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Freie Koordinations-/Lagemeldung unter den Bearbeitenden eines laufenden
 * Notfalls (zusätzlich zum Ereignis-Log und den Schritt-Notizen). Append-only.
 */
#[Fillable([
    'company_id',
    'scenario_run_id',
    'user_id',
    'author_name',
    'body',
])]
class ScenarioRunMessage extends Model
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
}
