<?php

use App\Enums\ScenarioRunMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenario_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('scenario_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('mode')->default(ScenarioRunMode::Drill->value);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('aborted_at')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_runs');
    }
};
