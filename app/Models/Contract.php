<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\ContractCoverage;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'company_id',
    'service_provider_id',
    'title',
    'contract_number',
    'coverage',
    'service_hours',
    'response_time_minutes',
    'resolution_time_minutes',
    'availability_percent',
    'emergency_hotline',
    'emergency_contact_name',
    'emergency_contact_phone',
    'start_date',
    'end_date',
    'notes',
])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->title;
    }

    /**
     * @return BelongsTo<ServiceProvider, $this>
     */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /**
     * @return BelongsToMany<System, $this>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)->withTimestamps();
    }

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        if ($this->end_date === null || $this->isExpired()) {
            return false;
        }

        return $this->end_date->lte(now()->addDays($withinDays));
    }

    /**
     * @return 'expired'|'expiring'|'active'
     */
    public function status(): string
    {
        return match (true) {
            $this->isExpired() => 'expired',
            $this->isExpiringSoon() => 'expiring',
            default => 'active',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'expired' => 'Abgelaufen',
            'expiring' => 'Läuft bald ab',
            default => 'Aktiv',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status()) {
            'expired' => 'rose',
            'expiring' => 'amber',
            default => 'emerald',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'coverage' => ContractCoverage::class,
            'response_time_minutes' => 'integer',
            'resolution_time_minutes' => 'integer',
            'availability_percent' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
