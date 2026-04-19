<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_report_obligations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_report_id')->constrained()->cascadeOnDelete();
            $table->string('obligation');
            $table->timestamp('reported_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['incident_report_id', 'obligation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_report_obligations');
    }
};
