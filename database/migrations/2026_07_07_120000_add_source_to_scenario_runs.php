<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auslöse-Quelle eines Notfall-Ablaufs: 'web' (Incident-Launcher),
 * 'app' (Notfall-App) oder 'monitoring' (automatisch durch einen kritischen
 * Monitoring-Alert). `trigger_detail` trägt bei Monitoring-Auslösung den
 * auslösenden Host — sichtbar im Cockpit, im Chat-Post und in den Apps,
 * damit niemand rätseln muss, wer „Unbekannt" ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenario_runs', function (Blueprint $table) {
            $table->string('source', 20)->default('web')->after('mode');
            $table->string('trigger_detail')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('scenario_runs', function (Blueprint $table) {
            $table->dropColumn(['source', 'trigger_detail']);
        });
    }
};
