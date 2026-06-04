<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_activity_log', function (Blueprint $table) {
            $table->index(['company_id', 'event', 'created_at'], 'auth_activity_log_company_event_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('auth_activity_log', function (Blueprint $table) {
            $table->dropIndex('auth_activity_log_company_event_created_index');
        });
    }
};
