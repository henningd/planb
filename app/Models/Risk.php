<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\RiskCategory;
use App\Enums\RiskStatus;
use App\Enums\RiskTreatmentStrategy;
use Database\Factories\RiskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'title',
    'description',
    'category',
    'probability',
    'impact',
    'residual_probability',
    'residual_impact',
    'status',
    'treatment_strategy',
    'owner_user_id',
    'review_due_at',
])]
class Risk extends Model
{
    /** @use HasFactory<RiskFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    /**
     * @return HasMany<RiskMitigation, $this>
     */
    public function mitigations(): HasMany
    {
        return $this->hasMany(RiskMitigation::class);
    }

    /**
     * @return BelongsToMany<System, $this>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class, 'risk_system');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function score(): int
    {
        return $this->probability * $this->impact;
    }

    public function residualScore(): ?int
    {
        if ($this->residual_probability === null || $this->residual_impact === null) {
            return null;
        }

        return $this->residual_probability * $this->residual_impact;
    }

    public function severityLevel(?int $score = null): string
    {
        $score ??= $this->score();

        return match (true) {
            $score >= 15 => 'critical',
            $score >= 10 => 'high',
            $score >= 5 => 'medium',
            default => 'low',
        };
    }

    public function severityColor(?int $score = null): string
    {
        return match ($this->severityLevel($score)) {
            'critical' => 'rose',
            'high' => 'orange',
            'medium' => 'amber',
            default => 'zinc',
        };
    }

    public function severityLabel(?int $score = null): string
    {
        return match ($this->severityLevel($score)) {
            'critical' => 'Kritisch',
            'high' => 'Hoch',
            'medium' => 'Mittel',
            default => 'Niedrig',
        };
    }

    public function isOverdue(): bool
    {
        return $this->review_due_at !== null && $this->review_due_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RiskCategory::class,
            'status' => RiskStatus::class,
            'treatment_strategy' => RiskTreatmentStrategy::class,
            'review_due_at' => 'date',
        ];
    }
}
