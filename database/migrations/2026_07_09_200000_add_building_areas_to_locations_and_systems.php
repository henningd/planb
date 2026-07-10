<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'building_areas')) {
                $table->text('building_areas')->nullable()->after('notes');
            }
        });

        Schema::table('systems', function (Blueprint $table) {
            if (! Schema::hasColumn('systems', 'location_detail')) {
                $table->string('location_detail')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('building_areas');
        });

        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('location_detail');
        });
    }
};
