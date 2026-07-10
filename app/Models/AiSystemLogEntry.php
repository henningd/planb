<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Enums\AiSystemLogType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Protokoll-/Nachweis-Eintrag zu einem KI-System (Prüfung, Aufsichts-
 * Eingriff, Test, Vorfall, Änderung, Schulung, Notiz). Append-only als
 * Konformitätsnachweis nach EU-KI-Verordnung.
 */
#[Fillable([
    'company_id',
    'ai_system_id',
    'user_id',
    'type',
    'summary',
    'occurred_at',
])]
class AiSystemLogEntry extends Model
{
    use BelongsToCurrentCompany, HasUuids;

    /**
     * @return BelongsTo<AiSystem, $this>
     */
    public function aiSystem(): BelongsTo
    {
        return $this->belongsTo(AiSystem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AiSystemLogType::class,
            'occurred_at' => 'date',
        ];
    }
}
