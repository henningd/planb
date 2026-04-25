<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('street');
            $table->string('postal_code');
            $table->string('city');
            $table->string('country', 2)->default('DE');
            $table->boolean('is_headquarters')->default(false);
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
