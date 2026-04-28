<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_manager', function (Blueprint $table) {
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUuid('manager_id')->constrained('employees')->cascadeOnDelete();

            $table->primary(['employee_id', 'manager_id']);
        });

        // Bestehende manager_id-Werte in die Pivot-Tabelle übertragen, damit
        // niemand seinen primären Vorgesetzten verliert.
        DB::table('employees')
            ->whereNotNull('manager_id')
            ->select('id', 'manager_id')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                $links = [];
                foreach ($rows as $row) {
                    $links[] = [
                        'employee_id' => $row->id,
                        'manager_id' => $row->manager_id,
                    ];
                }
                if ($links !== []) {
                    DB::table('employee_manager')->insertOrIgnore($links);
                }
            });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn('manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignUuid('manager_id')->nullable()->after('emergency_contact')->constrained('employees')->nullOnDelete();
        });

        // Bei Rollback: pro Mitarbeiter den ersten Vorgesetzten als manager_id
        // setzen — Mehrfach-Vorgesetzte gehen dabei verloren.
        DB::table('employee_manager')->orderBy('employee_id')->chunk(500, function ($rows) {
            $seen = [];
            foreach ($rows as $row) {
                if (isset($seen[$row->employee_id])) {
                    continue;
                }
                $seen[$row->employee_id] = true;
                DB::table('employees')->where('id', $row->employee_id)->update(['manager_id' => $row->manager_id]);
            }
        });

        Schema::dropIfExists('employee_manager');
    }
};
