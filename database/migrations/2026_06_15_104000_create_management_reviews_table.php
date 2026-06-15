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
        Schema::create('management_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('review_date')->nullable();
            $table->text('participants')->nullable();
            $table->text('summary')->nullable();
            $table->text('decisions')->nullable();
            $table->date('next_review_at')->nullable();
            $table->string('conducted_by')->nullable();
            $table->timestamps();

            $table->index('review_date');
            $table->index('next_review_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('management_reviews');
    }
};
