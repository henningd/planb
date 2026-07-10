<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\InsuranceType;
use Database\Factories\InsurancePolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'company_id',
    'type',
    'insurer',
    'policy_number',
    'valid_from',
    'valid_until',
    'hotline',
    'email',
    'reporting_window',
    'required_documents',
    'deductible',
    'coverage_amount',
    'contact_name',
    'responsible_role_id',
    'approval_required',
    'approval_note',
    'claims_process_tested_at',
    'last_reviewed_at',
    'next_review_at',
    'notes',
])]
class InsurancePolicy extends Model
{
    /** @use HasFactory<InsurancePolicyFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->insurer.' ('.$this->type->label().')';
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function responsibleRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'responsible_role_id');
    }

    /**
     * Szenarien, für die diese Versicherung im Schadenfall greift.
     *
     * @return BelongsToMany<Scenario, $this>
     */
    public function scenarios(): BelongsToMany
    {
        return $this->belongsToMany(Scenario::class, 'insurance_policy_scenario')->withTimestamps();
    }

    /**
     * Ist die Police abgelaufen (Ablaufdatum in der Vergangenheit)?
     */
    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    /**
     * Ist die nächste Prüfung der Versicherungsdaten überfällig?
     */
    public function isReviewOverdue(): bool
    {
        return $this->next_review_at !== null && $this->next_review_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InsuranceType::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
            'claims_process_tested_at' => 'date',
            'last_reviewed_at' => 'date',
            'next_review_at' => 'date',
            'approval_required' => 'boolean',
        ];
    }
}
