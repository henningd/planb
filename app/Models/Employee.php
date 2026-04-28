<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\CrisisRole;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'company_id',
    'first_name',
    'last_name',
    'position',
    'department_id',
    'work_phone',
    'mobile_phone',
    'private_phone',
    'email',
    'location_id',
    'emergency_contact',
    'is_key_personnel',
    'crisis_role',
    'is_crisis_deputy',
    'notes',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function nameLastFirst(): string
    {
        $last = trim((string) $this->last_name);
        $first = trim((string) $this->first_name);

        if ($last === '' && $first === '') {
            return '';
        }
        if ($last === '') {
            return $first;
        }
        if ($first === '') {
            return $last;
        }

        return "{$last}, {$first}";
    }

    public function auditLabel(): string
    {
        return $this->fullName();
    }

    /**
     * Vorgesetzte (Mehrfachzuordnung): einer oder mehrere andere Mitarbeiter,
     * die diesen Mitarbeiter führen — fachlich und/oder disziplinarisch.
     *
     * @return BelongsToMany<Employee, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_manager', 'employee_id', 'manager_id');
    }

    /**
     * Direkt unterstellte Mitarbeiter (Umkehrung von managers()).
     *
     * @return BelongsToMany<Employee, $this>
     */
    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_manager', 'manager_id', 'employee_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['id', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps()
            ->wherePivotNull('removed_at')
            ->orderBy('sort')
            ->orderBy('name');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function rolesHistory(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['id', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_key_personnel' => 'boolean',
            'is_crisis_deputy' => 'boolean',
            'crisis_role' => CrisisRole::class,
        ];
    }
}
