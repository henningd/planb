<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_mitigations', function (Blueprint $table) {
            $table->foreignUuid('system_task_id')->nullable()->after('responsible_employee_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('risk_mitigations', function (Blueprint $table) {
            $table->dropForeign(['system_task_id']);
            $table->dropColumn('system_task_id');
        });
    }
};
