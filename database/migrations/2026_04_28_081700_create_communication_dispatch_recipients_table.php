<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_dispatch_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('communication_dispatch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('status'); // sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['communication_dispatch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_dispatch_recipients');
    }
};
