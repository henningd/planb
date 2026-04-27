<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_resources', function (Blueprint $table) {
            if (! Schema::hasColumn('emergency_resources', 'available_budget')) {
                $table->unsignedBigInteger('available_budget')->nullable()->after('access_holders');
            }
        });
    }

    public function down(): void
    {
        Schema::table('emergency_resources', function (Blueprint $table) {
            if (Schema::hasColumn('emergency_resources', 'available_budget')) {
                $table->dropColumn('available_budget');
            }
        });
    }
};
