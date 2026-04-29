<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fallback_processes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('trigger')->nullable();
            $table->foreignUuid('responsible_role_id')
                ->nullable()
                ->constrained('roles')
                ->nullOnDelete();
            $table->foreignUuid('responsible_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->unsignedInteger('max_duration_hours')->nullable();
            $table->text('handover_notes')->nullable();
            $table->unsignedTinyInteger('priority')->default(2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fallback_processes');
    }
};
