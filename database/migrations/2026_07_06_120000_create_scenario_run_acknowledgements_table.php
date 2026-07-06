<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenario_run_acknowledgements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('scenario_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['scenario_run_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_run_acknowledgements');
    }
};
