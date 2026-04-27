<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{table: string, fk: list<string>}>
     */
    private array $pivots = [
        ['table' => 'system_task_employee', 'fk' => ['system_task_id', 'employee_id']],
        ['table' => 'service_provider_system_task', 'fk' => ['system_task_id', 'service_provider_id']],
        ['table' => 'role_system_task', 'fk' => ['system_task_id', 'role_id']],
    ];

    /**
     * Erlaubt mehrere aktive Pivot-Zeilen für dieselbe Personen-/Dienstleister-/
     * Rollen-Aufgabe-Kombination, solange sich `raci_role` oder `is_deputy`
     * unterscheiden. Damit kann z. B. eine Rolle gleichzeitig als A und als
     * C bei derselben Aufgabe hinterlegt sein.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // MySQL/MariaDB hatte keine partiellen Unique-Indexe — nichts zu tun.
        if ($driver === 'mysql' || $driver === 'mariadb') {
            return;
        }

        foreach ($this->pivots as $p) {
            $oldName = "{$p['table']}_active_unique";
            $newName = "{$p['table']}_active_unique_v2";
            $cols = implode(', ', [...$p['fk'], 'raci_role', 'is_deputy']);

            $this->safelyRunStatement("DROP INDEX IF EXISTS {$oldName}");
            $this->safelyRunStatement("DROP INDEX IF EXISTS {$newName}");
            $this->safelyRunStatement(
                "CREATE UNIQUE INDEX {$newName} ON {$p['table']} ({$cols}) WHERE removed_at IS NULL"
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            return;
        }

        foreach ($this->pivots as $p) {
            $oldName = "{$p['table']}_active_unique";
            $newName = "{$p['table']}_active_unique_v2";
            $cols = implode(', ', $p['fk']);

            $this->safelyRunStatement("DROP INDEX IF EXISTS {$newName}");
            $this->safelyRunStatement(
                "CREATE UNIQUE INDEX {$oldName} ON {$p['table']} ({$cols}) WHERE removed_at IS NULL"
            );
        }
    }

    private function safelyRunStatement(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (Throwable) {
            // Index existiert nicht / existiert bereits — ok.
        }
    }
};
