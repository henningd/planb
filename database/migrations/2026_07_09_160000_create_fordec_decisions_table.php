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
        // Idempotent: ein abgebrochener früherer Lauf kann die Tabelle bereits
        // angelegt haben, ohne die Migration zu registrieren.
        if (Schema::hasTable('fordec_decisions')) {
            return;
        }

        Schema::create('fordec_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('scenario_run_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('facts')->nullable();
            $table->text('options')->nullable();
            $table->text('risks_benefits')->nullable();
            $table->text('decision')->nullable();
            $table->text('execution')->nullable();
            $table->timestamp('check_at')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();

            $table->index('scenario_run_id');
            $table->index('check_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fordec_decisions');
    }
};
