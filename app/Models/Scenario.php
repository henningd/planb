<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Database\Factories\ScenarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'description', 'trigger'])]
class Scenario extends Model
{
    /** @use HasFactory<ScenarioFactory> */
    use BelongsToCurrentCompany, HasFactory;

    /**
     * @return HasMany<ScenarioStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ScenarioStep::class)->orderBy('sort');
    }

    /**
     * @return HasMany<ScenarioRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ScenarioRun::class);
    }
}
