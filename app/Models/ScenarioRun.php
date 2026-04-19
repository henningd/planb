<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Enums\ScenarioRunMode;
use Database\Factories\ScenarioRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'scenario_id',
    'started_by_user_id',
    'title',
    'mode',
    'started_at',
    'ended_at',
    'aborted_at',
    'summary',
])]
class ScenarioRun extends Model
{
    /** @use HasFactory<ScenarioRunFactory> */
    use BelongsToCurrentCompany, HasFactory;

    /**
     * @return BelongsTo<Scenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    /**
     * @return HasMany<ScenarioRunStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ScenarioRunStep::class)->orderBy('sort');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null && $this->aborted_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => ScenarioRunMode::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'aborted_at' => 'datetime',
        ];
    }
}
