<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('handbook_tests', function (Blueprint $table) {
            $table->timestamp('last_reminder_sent_at')->nullable()->after('result_notes');
        });
    }

    public function down(): void
    {
        Schema::table('handbook_tests', function (Blueprint $table) {
            $table->dropColumn('last_reminder_sent_at');
        });
    }
};
