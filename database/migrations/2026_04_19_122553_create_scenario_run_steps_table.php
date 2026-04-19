<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenario_run_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('scenario_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('responsible')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['scenario_run_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_run_steps');
    }
};
