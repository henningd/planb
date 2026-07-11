<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scenario_runs') && ! Schema::hasTable('scenario_run_messages')) {
            Schema::create('scenario_run_messages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('scenario_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('author_name')->nullable();
                $table->text('body');
                $table->timestamps();

                $table->index(['scenario_run_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_run_messages');
    }
};
