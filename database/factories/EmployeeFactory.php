<?php

namespace Database\Factories;

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'position' => fake()->jobTitle(),
            'department_id' => null,
            'work_phone' => fake()->phoneNumber(),
            'mobile_phone' => fake()->phoneNumber(),
            'private_phone' => null,
            'email' => fake()->safeEmail(),
            'location_id' => null,
            'emergency_contact' => null,
            'is_key_personnel' => false,
            'notes' => null,
        ];
    }

    /**
     * Hängt nach dem Anlegen ein employee_role-Pivot zur passenden
     * System-Rolle (per `system_key`) ein. Falls die System-Rolle für
     * die Firma noch nicht existiert (z. B. ohne Observer), wird sie
     * defensiv erzeugt.
     */
    public function withCrisisRole(CrisisRole $role, bool $deputy = false): static
    {
        return $this->afterCreating(function (Employee $employee) use ($role, $deputy) {
            $systemRole = Role::withoutGlobalScope(CurrentCompanyScope::class)
                ->where('company_id', $employee->company_id)
                ->where('system_key', $role->value)
                ->first();

            if ($systemRole === null) {
                $systemRole = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
                    'company_id' => $employee->company_id,
                    'name' => $role->label(),
                    'system_key' => $role->value,
                    'sort' => 0,
                ]);
            }

            AssignmentSync::attach(
                $employee,
                $employee->roles(),
                $systemRole->id,
                ['is_deputy' => $deputy],
            );
        });
    }
}
