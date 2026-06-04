<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Markiert ein System als „Träger" (z. B. Stromversorgung), dessen eigene
 * Ausfallkosten in Summen nicht mehr zählen, weil sein Schaden bereits über
 * die abhängigen Systeme abgebildet wird (Vermeidung von Doppelzählung).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->boolean('downtime_cost_from_dependents')->default(false)->after('downtime_cost_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('downtime_cost_from_dependents');
        });
    }
};
