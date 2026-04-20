<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedSmallInteger('review_cycle_months')->default(6);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['review_cycle_months', 'last_reviewed_at', 'last_reminder_sent_at']);
        });
    }
};
