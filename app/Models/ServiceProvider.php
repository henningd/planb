<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\ServiceProviderType;
use Database\Factories\ServiceProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'company_id',
    'name',
    'type',
    'contact_name',
    'hotline',
    'email',
    'contract_number',
    'sla',
    'direct_order_limit',
    'notes',
])]
class ServiceProvider extends Model
{
    /** @use HasFactory<ServiceProviderFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class)->orderBy('title');
    }

    /**
     * @return BelongsToMany<System, $this>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at');
    }

    /**
     * @return BelongsToMany<System, $this>
     */
    public function systemsHistory(): BelongsToMany
    {
        return $this->belongsToMany(System::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

    /**
     * Aufgaben (über alle Systeme), denen dieser Dienstleister zugeordnet ist —
     * ohne entfernte Zuordnungen.
     *
     * @return BelongsToMany<SystemTask, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(SystemTask::class, 'service_provider_system_task')
            ->withPivot(['id', 'raci_role', 'is_deputy', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at');
    }

    /**
     * @return HasOne<SupplierRiskAssessment, $this>
     */
    public function riskAssessment(): HasOne
    {
        return $this->hasOne(SupplierRiskAssessment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ServiceProviderType::class,
            'direct_order_limit' => 'decimal:2',
        ];
    }
}
