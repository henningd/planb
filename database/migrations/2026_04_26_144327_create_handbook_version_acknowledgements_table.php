<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handbook_version_acknowledgements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('handbook_version_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamp('acknowledged_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Expliziter, kurzer Index-Name: der Convention-Default ist auf
            // MySQL > 64 Zeichen (Identifier-Limit).
            $table->unique(['handbook_version_id', 'employee_id'], 'hbk_ack_version_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_version_acknowledgements');
    }
};
