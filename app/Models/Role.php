<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['company_id', 'name', 'system_key', 'description', 'sort'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->name;
    }

    /**
     * Vom System angelegte Rolle (eine pro CrisisRole) — darf nicht
     * gelöscht werden; Name/Beschreibung sind Vorgaben aus dem Catalog.
     */
    public function isSystem(): bool
    {
        return $this->system_key !== null;
    }

    /**
     * Aktive Mitarbeiter dieser Rolle (entfernte Zuordnungen sind ausgefiltert).
     *
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot(['id', 'is_deputy', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at')
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    /**
     * Alle Zuordnungen inklusive entfernter — für Historie- und Audit-Anfragen.
     *
     * @return BelongsToMany<Employee, $this>
     */
    public function employeesHistory(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot(['id', 'is_deputy', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

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
     * @return BelongsToMany<SystemTask, $this>
     */
    public function systemTasks(): BelongsToMany
    {
        return $this->belongsToMany(SystemTask::class)
            ->withPivot(['id', 'raci_role', 'sort', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at');
    }

    /**
     * @return BelongsToMany<SystemTask, $this>
     */
    public function systemTasksHistory(): BelongsToMany
    {
        return $this->belongsToMany(SystemTask::class)
            ->withPivot(['id', 'raci_role', 'sort', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }
}
