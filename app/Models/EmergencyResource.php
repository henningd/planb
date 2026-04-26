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

#[Fillable([
    'company_id',
    'type',
    'name',
    'description',
    'location',
    'access_holders',
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
        return $this->name ?? $this->type->label();
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
            'sort' => 'integer',
        ];
    }
}
