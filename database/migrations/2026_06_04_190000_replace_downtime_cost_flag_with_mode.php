<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ersetzt das boolesche Flag downtime_cost_from_dependents durch einen
 * Drei-Wege-Modus downtime_cost_mode (own | from_dependents | own_plus_dependents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->string('downtime_cost_mode', 32)->default('own')->after('downtime_cost_per_hour');
        });

        DB::table('systems')
            ->where('downtime_cost_from_dependents', true)
            ->update(['downtime_cost_mode' => 'from_dependents']);

        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('downtime_cost_from_dependents');
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->boolean('downtime_cost_from_dependents')->default(false)->after('downtime_cost_per_hour');
        });

        DB::table('systems')
            ->where('downtime_cost_mode', 'from_dependents')
            ->update(['downtime_cost_from_dependents' => true]);

        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('downtime_cost_mode');
        });
    }
};
