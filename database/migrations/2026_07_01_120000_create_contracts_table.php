<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_provider_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('contract_number')->nullable();
            $table->string('coverage')->nullable();
            $table->string('service_hours')->nullable();
            $table->unsignedInteger('response_time_minutes')->nullable();
            $table->unsignedInteger('resolution_time_minutes')->nullable();
            $table->decimal('availability_percent', 5, 2)->nullable();
            $table->string('emergency_hotline')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'title']);
            $table->index(['company_id', 'end_date']);
            $table->index('service_provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
