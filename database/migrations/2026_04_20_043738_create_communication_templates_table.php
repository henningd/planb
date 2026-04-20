<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('scenario_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('audience');
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->text('fallback')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
