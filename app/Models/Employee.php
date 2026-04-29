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
use Illuminate\Support\Collection;

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
    'notes',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    /**
     * Pflichtrolle aus den Rollen-Zuordnungen ableiten — die erste
     * zugeordnete System-Rolle (mit gesetztem `system_key`) gilt als
     * primäre Krisenrolle. Mehrfachzuweisungen sind möglich; UI-
     * Konsumenten, die alle anzeigen wollen, iterieren über
     * {@see crisisRoleAssignments()}.
     */
    public function crisisRole(): ?CrisisRole
    {
        $role = $this->resolveSystemRole();
        if ($role === null || $role->system_key === null) {
            return null;
        }

        return CrisisRole::tryFrom($role->system_key);
    }

    /**
     * Ist die Person Vertretung in der primären System-Rolle?
     */
    public function isCrisisDeputy(): bool
    {
        $role = $this->resolveSystemRole();

        return (bool) ($role?->pivot?->is_deputy ?? false);
    }

    /**
     * Alle System-Rollen, denen die Person aktuell zugeordnet ist —
     * inklusive Pivot mit is_deputy. Beibehält die Reihenfolge der
     * `roles`-Relation (sort, name).
     *
     * @return Collection<int, Role>
     */
    public function crisisRoleAssignments(): Collection
    {
        if (! $this->relationLoaded('roles')) {
            $this->load('roles');
        }

        return $this->roles->filter(fn (Role $r) => $r->system_key !== null)->values();
    }

    /**
     * Erste System-Rolle (sortiert nach sort/name) — Helper für die
     * Backward-kompatiblen single-value Accessoren.
     */
    private function resolveSystemRole(): ?Role
    {
        return $this->crisisRoleAssignments()->first();
    }

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
            ->withPivot(['id', 'is_deputy', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
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
            ->withPivot(['id', 'is_deputy', 'assigned_at', 'assigned_by_user_id', 'removed_at', 'removed_by_user_id'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_key_personnel' => 'boolean',
        ];
    }
}
