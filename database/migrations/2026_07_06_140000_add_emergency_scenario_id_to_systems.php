<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opt-in je System: Wenn gesetzt, löst ein kritischer Monitoring-Alert,
 * der einen neuen Incident für dieses System eröffnet, automatisch einen
 * echten Alarm (ScenarioRun, mode=real) für das verknüpfte Szenario aus.
 * NULL = keine automatische Alarmierung (Default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->foreignUuid('emergency_scenario_id')
                ->nullable()
                ->after('monitoring_keys')
                ->constrained('scenarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropConstrainedForeignId('emergency_scenario_id');
        });
    }
};
