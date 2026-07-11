<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scenario_runs') && ! Schema::hasColumn('scenario_runs', 'share_token')) {
            Schema::table('scenario_runs', function (Blueprint $table) {
                $table->string('share_token', 64)->nullable()->unique()->after('summary');
                $table->timestamp('share_token_created_at')->nullable()->after('share_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scenario_runs') && Schema::hasColumn('scenario_runs', 'share_token')) {
            Schema::table('scenario_runs', function (Blueprint $table) {
                $table->dropColumn(['share_token', 'share_token_created_at']);
            });
        }
    }
};
