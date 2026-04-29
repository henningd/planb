<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Migriert vorhandene `employees.crisis_role` / `employees.is_crisis_deputy`-
 * Werte in das `employee_role`-Pivot zur jeweiligen System-Rolle (Role mit
 * passendem `system_key`).
 *
 * Pre-Refactor: Krisenrolle direkt am Mitarbeiter als Enum.
 * Post-Refactor: Krisenrolle ausschließlich über Pivot-Zuordnung zur
 * System-Rolle (`role.system_key` = CrisisRole-Wert), `is_deputy` aus
 * `is_crisis_deputy`.
 *
 * Die Spalten selbst werden in einer späteren Migration gelöscht — hier
 * nur Daten kopieren, damit die Code-Umstellungen darauf aufsetzen können.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'crisis_role')) {
            return;
        }

        $now = Carbon::now();

        $employees = DB::table('employees')
            ->whereNotNull('crisis_role')
            ->select('id', 'company_id', 'crisis_role', 'is_crisis_deputy')
            ->get();

        foreach ($employees as $employee) {
            $role = DB::table('roles')
                ->where('company_id', $employee->company_id)
                ->where('system_key', $employee->crisis_role)
                ->first();

            // Sollte durch SystemRoleProvisioner immer existieren; falls
            // doch nicht, hier defensiv anlegen.
            if ($role === null) {
                $roleId = (string) Str::uuid();
                DB::table('roles')->insert([
                    'id' => $roleId,
                    'company_id' => $employee->company_id,
                    'name' => $this->labelFor($employee->crisis_role),
                    'system_key' => $employee->crisis_role,
                    'description' => 'Automatisch ergänzt durch Pflichtrollen-Refactoring.',
                    'sort' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $roleId = $role->id;
            }

            $isDeputy = (bool) $employee->is_crisis_deputy;

            $exists = DB::table('employee_role')
                ->where('employee_id', $employee->id)
                ->where('role_id', $roleId)
                ->whereNull('removed_at')
                ->exists();

            if (! $exists) {
                DB::table('employee_role')->insert([
                    'id' => (string) Str::uuid(),
                    'employee_id' => $employee->id,
                    'role_id' => $roleId,
                    'is_deputy' => $isDeputy,
                    'assigned_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                // Wenn vorhanden, aber is_deputy abweichend: aktualisieren.
                DB::table('employee_role')
                    ->where('employee_id', $employee->id)
                    ->where('role_id', $roleId)
                    ->whereNull('removed_at')
                    ->update([
                        'is_deputy' => $isDeputy,
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Bewusst kein Rollback: der Inhalt der Pivot-Tabelle ist nach
        // dem Refactoring die Quelle der Wahrheit. Eine Down-Migration
        // würde den neuen Datenstand zerstören.
    }

    private function labelFor(string $crisisRoleValue): string
    {
        return match ($crisisRoleValue) {
            'emergency_officer' => 'Notfallbeauftragte/r',
            'it_lead' => 'IT-Verantwortliche/r',
            'dpo' => 'Datenschutzbeauftragte/r',
            'communications_lead' => 'Kommunikationsverantwortliche/r',
            'management' => 'Geschäftsführung',
            default => ucfirst(str_replace('_', ' ', $crisisRoleValue)),
        };
    }
};
