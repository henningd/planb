<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scenario_runs') && ! Schema::hasTable('scenario_run_participants')) {
            Schema::create('scenario_run_participants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('scenario_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('last_seen_at');
                $table->timestamps();

                $table->unique(['scenario_run_id', 'user_id']);
                $table->index('last_seen_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_run_participants');
    }
};
