<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_records') && ! Schema::hasColumn('training_records', 'responsible_employee_id')) {
            Schema::table('training_records', function (Blueprint $table) {
                $table->foreignUuid('responsible_employee_id')->nullable()
                    ->after('employee_id')
                    ->constrained('employees')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('training_records') && Schema::hasColumn('training_records', 'responsible_employee_id')) {
            Schema::table('training_records', function (Blueprint $table) {
                $table->dropConstrainedForeignId('responsible_employee_id');
            });
        }
    }
};
