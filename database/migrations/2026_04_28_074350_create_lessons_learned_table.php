<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons_learned', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('incident_report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('scenario_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('root_cause')->nullable();
            $table->text('what_went_well')->nullable();
            $table->text('what_went_poorly')->nullable();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons_learned');
    }
};
