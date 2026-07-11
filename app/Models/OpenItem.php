<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\OpenItemConversion;
use App\Enums\OpenItemStatus;
use Database\Factories\OpenItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Offener Punkt / Klärpunkt: ein bekanntes Thema, das noch nicht final
 * entschieden, geprüft, dokumentiert oder getestet ist. Kein Risiko an sich,
 * sondern eine nachzuhaltende Lücke im BCMS – mit Verantwortlichem, Frist und
 * Wiedervorlage. Bei Erledigung wird festgehalten, worin der Punkt überführt
 * wurde (Risiko, Maßnahme, Szenario oder Test). Erscheint im Governance-/
 * Audit-Teil des Handbuchs, nicht im Ernstfall-Teil.
 */
#[Fillable([
    'company_id',
    'business_process_id',
    'training_record_id',
    'title',
    'relevance',
    'risk_id',
    'responsible_employee_id',
    'responsible_role_id',
    'due_at',
    'review_at',
    'status',
    'conversion',
    'resolution_note',
    'resolved_at',
    'sort',
])]
class OpenItem extends Model
{
    /** @use HasFactory<OpenItemFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->title;
    }

    /**
     * @return BelongsTo<Risk, $this>
     */
    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    /**
     * @return BelongsTo<BusinessProcess, $this>
     */
    public function businessProcess(): BelongsTo
    {
        return $this->belongsTo(BusinessProcess::class);
    }

    /**
     * Schulung, aus der dieser offene Punkt / diese Maßnahme entstanden ist (optional).
     *
     * @return BelongsTo<TrainingRecord, $this>
     */
    public function trainingRecord(): BelongsTo
    {
        return $this->belongsTo(TrainingRecord::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function responsibleRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'responsible_role_id');
    }

    /**
     * Frist überschritten und noch nicht erledigt?
     */
    public function isOverdue(): bool
    {
        return $this->status !== OpenItemStatus::Resolved
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    /**
     * Wiedervorlage fällig und noch nicht erledigt?
     */
    public function isReviewDue(): bool
    {
        return $this->status !== OpenItemStatus::Resolved
            && $this->review_at !== null
            && $this->review_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'review_at' => 'date',
            'resolved_at' => 'datetime',
            'status' => OpenItemStatus::class,
            'conversion' => OpenItemConversion::class,
        ];
    }
}
