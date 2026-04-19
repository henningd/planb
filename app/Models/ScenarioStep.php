<?php

namespace App\Models;

use Database\Factories\ScenarioStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['scenario_id', 'sort', 'title', 'description', 'responsible'])]
class ScenarioStep extends Model
{
    /** @use HasFactory<ScenarioStepFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Scenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }
}
