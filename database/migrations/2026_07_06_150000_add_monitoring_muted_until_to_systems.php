<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wartungsfenster je System: Solange now() < monitoring_muted_until, werden
 * eingehende Monitoring-Alerts nur protokolliert (handling=muted) — es wird
 * kein Incident angelegt und kein Auto-Alarm gestartet. NULL = kein
 * Wartungsfenster (Default). Entwarnungen (resolved) sind nicht betroffen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->timestamp('monitoring_muted_until')->nullable()->after('emergency_scenario_id');
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('monitoring_muted_until');
        });
    }
};
