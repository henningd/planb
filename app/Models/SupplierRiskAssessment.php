<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\PreventiveMeasureInterval;
use App\Enums\SecurityAssessmentStatus;
use App\Enums\SupplierCriticality;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\SupplierRiskAssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lieferketten-Risikobewertung eines Dienstleisters (NIS2 Art. 21 Supply-Chain).
 *
 * Hält Kritikalität, Sicherheitsbewertungsstatus und – bei gesetztem `interval` –
 * die nächste fällige Wiederbewertung sowie einen möglichen Ausweich-Dienstleister.
 */
#[Fillable([
    'company_id',
    'service_provider_id',
    'criticality',
    'security_status',
    'last_assessed_at',
    'interval',
    'next_assessment_at',
    'alternative_provider',
    'notes',
])]
class SupplierRiskAssessment extends Model
{
    /** @use HasFactory<SupplierRiskAssessmentFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->serviceProvider?->name ?? $this->criticality->label();
    }

    /**
     * @return BelongsTo<ServiceProvider, $this>
     */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function isOverdue(): bool
    {
        return $this->next_assessment_at !== null
            && $this->next_assessment_at->isPast();
    }

    /**
     * Bewertung als durchgeführt markieren: setzt das Bewertungsdatum und berechnet
     * bei gesetztem Intervall die nächste fällige Wiederbewertung.
     */
    public function markAssessed(?CarbonInterface $at = null): void
    {
        $at ??= CarbonImmutable::now();
        $this->last_assessed_at = $at;

        if ($this->interval !== null) {
            $this->next_assessment_at = $at->copy()->addMonths($this->interval->months());
        }

        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'criticality' => SupplierCriticality::class,
            'security_status' => SecurityAssessmentStatus::class,
            'interval' => PreventiveMeasureInterval::class,
            'last_assessed_at' => 'date',
            'next_assessment_at' => 'date',
        ];
    }
}
