<?php

use App\Enums\RiskCategory;
use App\Enums\RiskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default(RiskCategory::Operational->value);
            $table->unsignedTinyInteger('probability');
            $table->unsignedTinyInteger('impact');
            $table->unsignedTinyInteger('residual_probability')->nullable();
            $table->unsignedTinyInteger('residual_impact')->nullable();
            $table->string('status')->default(RiskStatus::Identified->value);
            $table->string('treatment_strategy')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_due_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'review_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
