<?php

namespace App\Models;

use App\Enums\Industry;
use App\Observers\CompanyObserver;
use Carbon\CarbonInterface;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['team_id', 'name', 'industry', 'employee_count', 'locations_count', 'review_cycle_months', 'last_reviewed_at', 'last_reminder_sent_at'])]
#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<EmergencyLevel, $this>
     */
    public function emergencyLevels(): HasMany
    {
        return $this->hasMany(EmergencyLevel::class)->orderBy('sort');
    }

    /**
     * @return HasMany<System, $this>
     */
    public function systems(): HasMany
    {
        return $this->hasMany(System::class);
    }

    /**
     * @return HasMany<SystemPriority, $this>
     */
    public function systemPriorities(): HasMany
    {
        return $this->hasMany(SystemPriority::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Scenario, $this>
     */
    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class)->orderBy('name');
    }

    /**
     * @return HasMany<ScenarioRun, $this>
     */
    public function scenarioRuns(): HasMany
    {
        return $this->hasMany(ScenarioRun::class);
    }

    /**
     * @return HasMany<IncidentReport, $this>
     */
    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class);
    }

    /**
     * @return HasMany<CommunicationTemplate, $this>
     */
    public function communicationTemplates(): HasMany
    {
        return $this->hasMany(CommunicationTemplate::class)->orderBy('sort')->orderBy('name');
    }

    /**
     * @return HasMany<InsurancePolicy, $this>
     */
    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class)->orderBy('type')->orderBy('insurer');
    }

    /**
     * @return HasMany<HandbookShare, $this>
     */
    public function handbookShares(): HasMany
    {
        return $this->hasMany(HandbookShare::class)->orderByDesc('created_at');
    }

    public function primaryContact(): ?Contact
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    public function hasPrimaryContact(): bool
    {
        return $this->contacts()->where('is_primary', true)->exists();
    }

    /**
     * Date when the next review is due. Null if no confirmation has happened
     * yet – in that case the company is "due" once it is older than the cycle.
     */
    public function reviewDueAt(): ?CarbonInterface
    {
        if ($this->last_reviewed_at) {
            return $this->last_reviewed_at->copy()->addMonths($this->review_cycle_months);
        }

        return $this->created_at?->copy()->addMonths($this->review_cycle_months);
    }

    public function isReviewDue(): bool
    {
        $due = $this->reviewDueAt();

        return $due !== null && $due->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'industry' => Industry::class,
            'employee_count' => 'integer',
            'locations_count' => 'integer',
            'review_cycle_months' => 'integer',
            'last_reviewed_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
        ];
    }
}
