<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use Database\Factories\ScenarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'name',
    'description',
    'trigger',
    'alarm_chain_detector',
    'alarm_chain_first_contact',
    'alarm_chain_lead_role',
    'alarm_chain_providers',
    'alarm_chain_management',
    'alarm_chain_authorities',
    'alarm_chain_comms_approval',
])]
class Scenario extends Model
{
    /** @use HasFactory<ScenarioFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

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
