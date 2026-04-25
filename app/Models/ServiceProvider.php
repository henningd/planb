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
     * @return BelongsToMany<System, $this>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class)
            ->withPivot(['id', 'raci_role', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at');
    }

    /**
     * @return BelongsToMany<System, $this>
     */
    public function systemsHistory(): BelongsToMany
    {
        return $this->belongsToMany(System::class)
            ->withPivot(['id', 'raci_role', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
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
