<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entfernt die alten Pflichtrollen-Spalten am Mitarbeiter, nachdem die
 * Daten in das `employee_role`-Pivot überführt wurden (siehe Migration
 * 2026_04_29_232708_migrate_crisis_role_to_employee_role_pivot).
 *
 * Pflichtrollen werden danach ausschließlich über die System-Rollen
 * (Role mit `system_key`) und das Pivot ausgedrückt.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'crisis_role')) {
            Schema::table('employees', function (Blueprint $table) {
                try {
                    $table->dropIndex('employees_crisis_role_idx');
                } catch (Throwable $e) {
                    // Index existiert ggf. nicht; ignorieren.
                }
                $table->dropColumn(['crisis_role', 'is_crisis_deputy']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'crisis_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('crisis_role')->nullable()->after('is_key_personnel');
                $table->boolean('is_crisis_deputy')->default(false)->after('crisis_role');
                $table->index('crisis_role');
            });
        }
    }
};
