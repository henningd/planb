<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crisis_log_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('scenario_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32);
            $table->text('message');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'scenario_run_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crisis_log_entries');
    }
};
