<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\SystemCategory;
use Database\Factories\SystemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'description', 'fallback_process', 'runbook_reference', 'emergency_level_id', 'category', 'system_priority_id', 'rto_minutes', 'rpo_minutes', 'downtime_cost_per_hour', 'monitoring_keys'])]
class System extends Model
{
    /** @use HasFactory<SystemFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    /**
     * @return BelongsTo<SystemPriority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(SystemPriority::class, 'system_priority_id');
    }

    /**
     * @return BelongsTo<EmergencyLevel, $this>
     */
    public function emergencyLevel(): BelongsTo
    {
        return $this->belongsTo(EmergencyLevel::class);
    }

    /**
     * @return BelongsToMany<ServiceProvider, $this>
     */
    public function serviceProviders(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at')
            ->orderBy('service_provider_system.sort');
    }

    /**
     * @return BelongsToMany<ServiceProvider, $this>
     */
    public function serviceProvidersHistory(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at')
            ->orderBy('employee_system.sort');
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employeesHistory(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at')
            ->orderBy('role_system.sort');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function rolesHistory(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['id', 'raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<SystemTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(SystemTask::class);
    }

    /**
     * Systems this one depends on and that must come up first.
     *
     * @return BelongsToMany<System, $this>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            System::class,
            'system_dependencies',
            'system_id',
            'depends_on_system_id',
        )
            ->withPivot(['sort', 'note'])
            ->withTimestamps()
            ->orderBy('system_dependencies.sort');
    }

    /**
     * Systems that depend on this one (inverse).
     *
     * @return BelongsToMany<System, $this>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            System::class,
            'system_dependencies',
            'depends_on_system_id',
            'system_id',
        )
            ->withPivot(['sort', 'note'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => SystemCategory::class,
            'rto_minutes' => 'integer',
            'rpo_minutes' => 'integer',
            'downtime_cost_per_hour' => 'integer',
            'monitoring_keys' => 'array',
        ];
    }
}
