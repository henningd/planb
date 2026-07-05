<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->string('triggered_by_name')->nullable()->after('body');
            $table->string('severity')->default('info')->after('triggered_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropColumn(['triggered_by_name', 'severity']);
        });
    }
};
