<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_tasks', function (Blueprint $table) {
            $table->dropForeign(['assignee_employee_id']);
            $table->dropColumn('assignee_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('system_tasks', function (Blueprint $table) {
            $table->foreignUuid('assignee_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
        });
    }
};
