<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_resources', function (Blueprint $table) {
            $table->timestamp('last_reminder_sent_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('emergency_resources', function (Blueprint $table) {
            $table->dropColumn('last_reminder_sent_at');
        });
    }
};
