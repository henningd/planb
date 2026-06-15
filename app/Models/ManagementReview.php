<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use Database\Factories\ManagementReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Dokumentierte Leitungsbewertung des BCMS (Management-Review) nach
 * ISO 22301 §9.3 / BSI 200-4 „Aufrechterhaltung“.
 *
 * Hält fest, welche Eingaben (Kennzahlen, offene Maßnahmen, Vorfälle,
 * Übungsergebnisse) bewertet wurden und welche Beschlüsse die Leitung
 * daraus abgeleitet hat – Grundlage für den kontinuierlichen
 * Verbesserungsprozess (KVP).
 */
#[Fillable([
    'company_id',
    'title',
    'review_date',
    'participants',
    'summary',
    'decisions',
    'next_review_at',
    'conducted_by',
])]
class ManagementReview extends Model
{
    /** @use HasFactory<ManagementReviewFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->title;
    }

    /**
     * Ist der nächste geplante Review-Termin bereits überschritten?
     */
    public function isFollowUpOverdue(): bool
    {
        return $this->next_review_at !== null
            && $this->next_review_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'next_review_at' => 'date',
        ];
    }
}
