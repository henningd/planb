<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_system', function (Blueprint $table) {
            $table->string('raci_role', 1)->nullable()->after('employee_id');
        });

        Schema::table('service_provider_system', function (Blueprint $table) {
            $table->string('raci_role', 1)->nullable()->after('service_provider_id');
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('employee_system', function (Blueprint $table) {
            $table->dropColumn('raci_role');
        });

        Schema::table('service_provider_system', function (Blueprint $table) {
            $table->string('role')->nullable()->after('service_provider_id');
            $table->dropColumn('raci_role');
        });
    }
};
