<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scenario_run_steps') && Schema::hasTable('employees')
            && ! Schema::hasColumn('scenario_run_steps', 'assigned_employee_id')) {
            Schema::table('scenario_run_steps', function (Blueprint $table) {
                $table->foreignUuid('assigned_employee_id')->nullable()
                    ->after('responsible')
                    ->constrained('employees')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scenario_run_steps') && Schema::hasColumn('scenario_run_steps', 'assigned_employee_id')) {
            Schema::table('scenario_run_steps', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assigned_employee_id');
            });
        }
    }
};
