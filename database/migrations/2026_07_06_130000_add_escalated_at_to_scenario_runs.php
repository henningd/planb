<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merkt sich, wann ein echter Alarm mangels Quittierung eskaliert wurde
 * (erneuter Push + SMS an den Krisenstab). NULL = noch nicht eskaliert.
 * Genau ein Eskalations-Durchlauf pro Run — der Wert wirkt als Sperre
 * gegen Doppel-Eskalation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenario_runs', function (Blueprint $table) {
            $table->timestamp('escalated_at')->nullable()->after('aborted_at');
        });
    }

    public function down(): void
    {
        Schema::table('scenario_runs', function (Blueprint $table) {
            $table->dropColumn('escalated_at');
        });
    }
};
