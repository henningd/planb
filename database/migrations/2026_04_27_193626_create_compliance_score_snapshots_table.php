<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_score_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->unsignedTinyInteger('score');
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->index('snapshot_date');
            $table->unique(['company_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_score_snapshots');
    }
};
