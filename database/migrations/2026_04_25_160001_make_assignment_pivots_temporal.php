<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Wandelt die fünf Zuweisungs-Pivots in eine temporale Form um:
 *  - eigener UUID-Primärschlüssel statt composite PK
 *  - assigned_at / removed_at und assigned_by_user_id / removed_by_user_id
 *  - partieller Unique-Index (fk1, fk2) WHERE removed_at IS NULL
 *
 * Nach der Migration gilt: ein "detach" wird durch removed_at = now() abgebildet,
 * historische Zuordnungen bleiben damit revisionssicher erhalten.
 */
return new class extends Migration
{
    /**
     * @var list<array{table: string, fkCols: list<string>, extras: list<array{name: string, type: string, nullable?: bool, length?: int, default?: mixed}>}>
     */
    private array $pivots = [
        [
            'table' => 'employee_role',
            'fkCols' => ['role_id', 'employee_id'],
            'extras' => [],
        ],
        [
            'table' => 'role_system',
            'fkCols' => ['role_id', 'system_id'],
            'extras' => [
                ['name' => 'raci_role', 'type' => 'string', 'nullable' => true],
                ['name' => 'sort', 'type' => 'unsignedInteger', 'default' => 0],
                ['name' => 'note', 'type' => 'text', 'nullable' => true],
            ],
        ],
        [
            'table' => 'role_system_task',
            'fkCols' => ['role_id', 'system_task_id'],
            'extras' => [
                ['name' => 'raci_role', 'type' => 'string', 'nullable' => true],
                ['name' => 'sort', 'type' => 'unsignedInteger', 'default' => 0],
            ],
        ],
        [
            'table' => 'employee_system',
            'fkCols' => ['system_id', 'employee_id'],
            'extras' => [
                ['name' => 'raci_role', 'type' => 'string', 'nullable' => true, 'length' => 1],
                ['name' => 'sort', 'type' => 'unsignedInteger', 'default' => 0],
                ['name' => 'note', 'type' => 'string', 'nullable' => true, 'length' => 500],
            ],
        ],
        [
            'table' => 'system_task_employee',
            'fkCols' => ['system_task_id', 'employee_id'],
            'extras' => [
                ['name' => 'raci_role', 'type' => 'string', 'length' => 1],
            ],
        ],
    ];

    public function up(): void
    {
        foreach ($this->pivots as $pivot) {
            $this->upgradePivot($pivot['table'], $pivot['fkCols'], $pivot['extras']);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->pivots) as $pivot) {
            $this->revertPivot($pivot['table'], $pivot['fkCols'], $pivot['extras']);
        }
    }

    /**
     * @param  list<string>  $fkCols
     * @param  list<array{name: string, type: string, nullable?: bool, length?: int, default?: mixed}>  $extras
     */
    private function upgradePivot(string $table, array $fkCols, array $extras): void
    {
        $tmp = "{$table}_legacy";

        // Idempotenz: erkennt halbfertige Läufe (z. B. nach abgebrochenem
        // Deploy). Ist die Tabelle bereits temporal (hat removed_at), nur
        // den Legacy-Rest wegräumen und die Indexe sicherstellen.
        $alreadyMigrated = Schema::hasTable($table) && Schema::hasColumn($table, 'removed_at');

        if ($alreadyMigrated) {
            Schema::dropIfExists($tmp);
            $this->ensureTemporalIndexes($table, $fkCols);

            return;
        }

        // Frischer Lauf — falls von einem früheren Versuch noch ein
        // Legacy-Stub herumliegt, wegräumen, damit das Rename greift.
        if (Schema::hasTable($tmp)) {
            Schema::dropIfExists($tmp);
        }

        Schema::rename($table, $tmp);

        Schema::create($table, function (Blueprint $t) use ($fkCols, $extras) {
            $t->uuid('id')->primary();
            foreach ($fkCols as $col) {
                $t->foreignUuid($col)->constrained()->cascadeOnDelete();
            }
            foreach ($extras as $extra) {
                $this->addExtraColumn($t, $extra);
            }
            $t->timestamp('assigned_at')->nullable();
            $t->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('removed_at')->nullable();
            $t->foreignId('removed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        $extraNames = array_map(fn (array $e): string => $e['name'], $extras);
        $copyCols = array_merge($fkCols, $extraNames, ['created_at', 'updated_at']);

        DB::table($tmp)->orderBy($fkCols[0])->orderBy($fkCols[1])->each(function ($row) use ($table, $copyCols) {
            $payload = ['id' => (string) Str::uuid()];
            foreach ($copyCols as $col) {
                $payload[$col] = $row->{$col} ?? null;
            }
            $payload['assigned_at'] = $row->created_at ?? now();
            DB::table($table)->insert($payload);
        });

        Schema::dropIfExists($tmp);

        $this->ensureTemporalIndexes($table, $fkCols);
    }

    /**
     * Legt die drei Standard-Indexe für temporale Pivots an. Der partielle
     * Unique-Index ist Postgres/SQLite-Syntax und wird auf MySQL
     * übersprungen — dort sichert AssignmentSync die Active-Uniqueness
     * applikationsseitig. CREATE-Statements sind try-catch-gewrappt, damit
     * ein zweiter Lauf nicht an existierenden Indexen scheitert.
     *
     * @param  list<string>  $fkCols
     */
    private function ensureTemporalIndexes(string $table, array $fkCols): void
    {
        $driver = DB::getDriverName();
        $cols = implode(', ', $fkCols);

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            $this->safelyRunStatement(
                "CREATE UNIQUE INDEX {$table}_active_unique ON {$table} ({$cols}) WHERE removed_at IS NULL"
            );
        }

        $this->safelyRunStatement(
            "CREATE INDEX {$table}_lookup_idx ON {$table} ({$fkCols[0]}, removed_at)"
        );
        $this->safelyRunStatement(
            "CREATE INDEX {$table}_inverse_idx ON {$table} ({$fkCols[1]}, removed_at)"
        );
    }

    private function safelyRunStatement(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (Throwable) {
            // Index existiert bereits aus früherem Lauf – ok.
        }
    }

    /**
     * @param  list<string>  $fkCols
     * @param  list<array{name: string, type: string, nullable?: bool, length?: int, default?: mixed}>  $extras
     */
    private function revertPivot(string $table, array $fkCols, array $extras): void
    {
        $tmp = "{$table}_legacy";

        Schema::rename($table, $tmp);

        Schema::create($table, function (Blueprint $t) use ($fkCols, $extras, $table) {
            foreach ($fkCols as $col) {
                $t->foreignUuid($col)->constrained()->cascadeOnDelete();
            }
            foreach ($extras as $extra) {
                $this->addExtraColumn($t, $extra);
            }
            $t->timestamps();
            $t->primary($fkCols);
            if ($table === 'employee_role') {
                $t->index('employee_id');
            }
            if ($table === 'role_system') {
                $t->index('system_id');
            }
            if ($table === 'role_system_task') {
                $t->index('system_task_id');
            }
        });

        $extraNames = array_map(fn (array $e): string => $e['name'], $extras);
        $copyCols = array_merge($fkCols, $extraNames, ['created_at', 'updated_at']);

        DB::table($tmp)->whereNull('removed_at')->orderBy('assigned_at')->each(function ($row) use ($table, $copyCols) {
            $payload = [];
            foreach ($copyCols as $col) {
                $payload[$col] = $row->{$col} ?? null;
            }
            DB::table($table)->insertOrIgnore($payload);
        });

        Schema::dropIfExists($tmp);
    }

    /**
     * @param  array{name: string, type: string, nullable?: bool, length?: int, default?: mixed}  $extra
     */
    private function addExtraColumn(Blueprint $t, array $extra): void
    {
        $col = match ($extra['type']) {
            'string' => isset($extra['length'])
                ? $t->string($extra['name'], $extra['length'])
                : $t->string($extra['name']),
            'text' => $t->text($extra['name']),
            'unsignedInteger' => $t->unsignedInteger($extra['name']),
            default => throw new RuntimeException("Unsupported column type {$extra['type']}"),
        };

        if ($extra['nullable'] ?? false) {
            $col->nullable();
        }
        if (array_key_exists('default', $extra)) {
            $col->default($extra['default']);
        }
    }
};
