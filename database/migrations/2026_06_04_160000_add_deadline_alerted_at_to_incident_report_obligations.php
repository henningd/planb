<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_report_obligations', function (Blueprint $table) {
            $table->timestamp('deadline_alerted_at')->nullable()->after('reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('incident_report_obligations', function (Blueprint $table) {
            $table->dropColumn('deadline_alerted_at');
        });
    }
};
