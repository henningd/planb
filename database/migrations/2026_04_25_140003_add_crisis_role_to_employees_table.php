<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('crisis_role')->nullable()->after('is_key_personnel');
            $table->boolean('is_crisis_deputy')->default(false)->after('crisis_role');

            $table->index(['company_id', 'crisis_role', 'is_crisis_deputy'], 'employees_crisis_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_crisis_role_idx');
            $table->dropColumn(['crisis_role', 'is_crisis_deputy']);
        });
    }
};
