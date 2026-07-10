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
        Schema::table('business_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('business_processes', 'fallback_process')) {
                $table->text('fallback_process')->nullable()->after('peak_times');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_processes', function (Blueprint $table) {
            $table->dropColumn('fallback_process');
        });
    }
};
