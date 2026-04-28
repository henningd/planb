<?php

namespace App\Support\Employees;

use App\Models\Company;
use App\Models\Employee;
use App\Scopes\CurrentCompanyScope;

/**
 * Exportiert die Mitarbeiter-Liste eines Mandanten als menschenlesbares
 * JSON-Array. Im Gegensatz zum vollständigen Mandanten-Backup ist hier
 * jede Beziehung mit Klar-Namen aufgelöst (Abteilung, Standort, Vorgesetzte,
 * Rollen, System-Zuweisungen mit RACI), damit der Export auch ohne die
 * Plattform inspizierbar ist.
 */
class EmployeeExporter
{
    /**
     * @return array{
     *     exported_at: string,
     *     company: array{id: string, name: string},
     *     count: int,
     *     employees: list<array<string, mixed>>
     * }
     */
    public static function export(Company $company): array
    {
        $employees = Employee::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->with([
                'department:id,name',
                'location:id,name',
                'managers:id,first_name,last_name',
                'reports:id,first_name,last_name',
                'roles:id,name',
                'systems:id,name',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $rows = $employees->map(fn (Employee $e) => self::serialize($e))->all();

        return [
            'exported_at' => now()->toIso8601String(),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'count' => count($rows),
            'employees' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(Employee $e): array
    {
        return [
            'id' => $e->id,
            'first_name' => $e->first_name,
            'last_name' => $e->last_name,
            'name_last_first' => $e->nameLastFirst(),
            'position' => $e->position,
            'department' => $e->department?->name,
            'location' => $e->location?->name,
            'work_phone' => $e->work_phone,
            'mobile_phone' => $e->mobile_phone,
            'private_phone' => $e->private_phone,
            'email' => $e->email,
            'emergency_contact' => $e->emergency_contact,
            'is_key_personnel' => (bool) $e->is_key_personnel,
            'crisis_role' => $e->crisis_role?->value,
            'crisis_role_label' => $e->crisis_role?->label(),
            'is_crisis_deputy' => (bool) $e->is_crisis_deputy,
            'notes' => $e->notes,
            'managers' => $e->managers
                ->map(fn (Employee $m) => ['id' => $m->id, 'name' => $m->nameLastFirst()])
                ->values()
                ->all(),
            'reports' => $e->reports
                ->map(fn (Employee $r) => ['id' => $r->id, 'name' => $r->nameLastFirst()])
                ->values()
                ->all(),
            'roles' => $e->roles
                ->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])
                ->values()
                ->all(),
            'systems' => $e->systems
                ->map(fn ($system) => [
                    'id' => $system->id,
                    'name' => $system->name,
                    'raci_role' => $system->pivot->raci_role,
                    'note' => $system->pivot->note,
                ])
                ->values()
                ->all(),
            'created_at' => $e->created_at?->toIso8601String(),
            'updated_at' => $e->updated_at?->toIso8601String(),
        ];
    }

    public static function filename(Company $company): string
    {
        return sprintf(
            'planb-mitarbeiter-%s-%s.json',
            $company->team?->slug ?? 'firma',
            now()->format('Y-m-d_His'),
        );
    }
}
