<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\EmergencyResourceType;
use Database\Factories\EmergencyResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'category_id',
    'type',
    'name',
    'description',
    'location',
    'access_holders',
    'available_budget',
    'last_check_at',
    'next_check_at',
    'notes',
    'last_reminder_sent_at',
    'sort',
])]
class EmergencyResource extends Model
{
    /** @use HasFactory<EmergencyResourceFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->name ?? $this->category?->name ?? $this->type?->label() ?? 'Notfallressource';
    }

    /**
     * @return BelongsTo<EmergencyResourceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EmergencyResourceCategory::class);
    }

    /**
     * Anzuzeigende Kategorie-Bezeichnung (frei konfigurierte Kategorie, sonst
     * der alte Enum-Typ als Fallback für Altdaten).
     */
    public function categoryLabel(): string
    {
        return $this->category?->name ?? $this->type?->label() ?? '—';
    }

    public function isOverdue(): bool
    {
        return $this->next_check_at !== null && $this->next_check_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EmergencyResourceType::class,
            'last_check_at' => 'date',
            'next_check_at' => 'date',
            'last_reminder_sent_at' => 'datetime',
            'available_budget' => 'integer',
            'sort' => 'integer',
        ];
    }
}
