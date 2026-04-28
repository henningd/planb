<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('current_step')->nullable();
            $table->json('completed_steps')->nullable();
            $table->json('skipped_steps')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_states');
    }
};
