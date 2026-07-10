<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\AiRiskClass;
use App\Enums\AiSystemRole;
use Database\Factories\AiSystemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ein KI-System im Register nach EU-KI-Verordnung: Zweck, Rolle des
 * Unternehmens (Anbieter/Betreiber …), Risikoklasse, Aufsicht, Konformität
 * und Prüftermine. Grundlage für Klassifizierung, Pflichten-Nachweis und
 * Protokollierung.
 */
#[Fillable([
    'company_id',
    'name',
    'purpose',
    'provider_name',
    'role',
    'risk_class',
    'annex_category',
    'data_sources',
    'human_oversight',
    'responsible_role_id',
    'conformity_status',
    'eu_db_registration',
    'transparency_measures',
    'last_reviewed_at',
    'next_review_at',
    'notes',
    'sort',
])]
class AiSystem extends Model
{
    /** @use HasFactory<AiSystemFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->name;
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function responsibleRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'responsible_role_id');
    }

    /**
     * @return HasMany<AiSystemLogEntry, $this>
     */
    public function logEntries(): HasMany
    {
        return $this->hasMany(AiSystemLogEntry::class)->orderByDesc('occurred_at')->orderByDesc('created_at');
    }

    public function isProhibited(): bool
    {
        return $this->risk_class === AiRiskClass::Prohibited;
    }

    public function isHighRisk(): bool
    {
        return $this->risk_class === AiRiskClass::High;
    }

    /**
     * Nächste Überprüfung überfällig?
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
            'role' => AiSystemRole::class,
            'risk_class' => AiRiskClass::class,
            'last_reviewed_at' => 'date',
            'next_review_at' => 'date',
            'sort' => 'integer',
        ];
    }
}
