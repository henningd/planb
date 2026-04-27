<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('handbook_tests', 'responsible_role_id')) {
            Schema::table('handbook_tests', function (Blueprint $table) {
                $table->foreignUuid('responsible_role_id')
                    ->nullable()
                    ->after('responsible_employee_id')
                    ->constrained('roles')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('handbook_tests', 'responsible_role_id')) {
            Schema::table('handbook_tests', function (Blueprint $table) {
                $table->dropForeign(['responsible_role_id']);
                $table->dropColumn('responsible_role_id');
            });
        }
    }
};
