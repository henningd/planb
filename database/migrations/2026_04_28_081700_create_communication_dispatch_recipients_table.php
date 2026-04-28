<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotenz: ein vorheriger Lauf hat ggf. die Tabelle erzeugt, ist
        // dann am FK-Identifier (MySQL 64-Char-Limit) gescheitert. Tabelle
        // ist in dem Fall leer und darf verworfen werden.
        Schema::dropIfExists('communication_dispatch_recipients');

        Schema::create('communication_dispatch_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('communication_dispatch_id');
            $table->foreignUuid('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('status'); // sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Explizite Kurz-Namen — der Convention-Default überschreitet auf
            // MySQL das 64-Char-Identifier-Limit.
            $table->foreign('communication_dispatch_id', 'cdr_dispatch_fk')
                ->references('id')->on('communication_dispatches')
                ->cascadeOnDelete();
            $table->index(['communication_dispatch_id', 'status'], 'cdr_dispatch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_dispatch_recipients');
    }
};
