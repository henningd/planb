<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\BcmPolicyStatus;
use Database\Factories\BcmPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BCM-Leitlinie der Company: dokumentiert Geltungsbereich und Inhalt der
 * Business-Continuity-Governance und wird durch die Leitung freigegeben
 * (NIS2 Art. 20/21, BSI 200-4). Pro Company gibt es typischerweise genau
 * eine aktuelle Leitlinie mit Versionsstand und Review-Zyklus.
 */
#[Fillable([
    'company_id',
    'scope',
    'content',
    'version',
    'status',
    'approved_by',
    'approved_at',
    'review_due_at',
])]
class BcmPolicy extends Model
{
    /** @use HasFactory<BcmPolicyFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return 'BCM-Leitlinie v'.$this->version;
    }

    public function isApproved(): bool
    {
        return $this->status === BcmPolicyStatus::Approved;
    }

    public function isReviewOverdue(): bool
    {
        return $this->review_due_at !== null && $this->review_due_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BcmPolicyStatus::class,
            'approved_at' => 'date',
            'review_due_at' => 'date',
        ];
    }
}
