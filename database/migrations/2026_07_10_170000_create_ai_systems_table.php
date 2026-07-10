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
        if (Schema::hasTable('ai_systems')) {
            return;
        }

        Schema::create('ai_systems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('purpose')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('role')->default('deployer');
            $table->string('risk_class')->default('unclassified');
            $table->string('annex_category')->nullable();
            $table->text('data_sources')->nullable();
            $table->text('human_oversight')->nullable();
            $table->foreignUuid('responsible_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('conformity_status')->nullable();
            $table->string('eu_db_registration')->nullable();
            $table->text('transparency_measures')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->date('next_review_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('risk_class');
            $table->index('next_review_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_systems');
    }
};
