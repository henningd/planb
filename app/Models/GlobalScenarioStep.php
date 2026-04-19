<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['global_scenario_id', 'sort', 'title', 'description', 'responsible'])]
class GlobalScenarioStep extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<GlobalScenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(GlobalScenario::class, 'global_scenario_id');
    }
}
