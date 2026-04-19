<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Enums\IncidentType;
use Database\Factories\IncidentReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'scenario_run_id', 'title', 'type', 'occurred_at', 'notes'])]
class IncidentReport extends Model
{
    /** @use HasFactory<IncidentReportFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids;

    /**
     * @return BelongsTo<ScenarioRun, $this>
     */
    public function scenarioRun(): BelongsTo
    {
        return $this->belongsTo(ScenarioRun::class);
    }

    /**
     * @return HasMany<IncidentReportObligation, $this>
     */
    public function obligations(): HasMany
    {
        return $this->hasMany(IncidentReportObligation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IncidentType::class,
            'occurred_at' => 'datetime',
        ];
    }
}
