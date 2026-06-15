<?php

use App\Enums\ProcessCriticality;
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
        Schema::create('business_processes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('criticality')->default(ProcessCriticality::Mittel->value);
            $table->unsignedInteger('mtpd_minutes')->nullable();
            $table->unsignedInteger('rto_minutes')->nullable();
            $table->unsignedInteger('rpo_minutes')->nullable();
            $table->string('peak_times')->nullable();
            $table->foreignUuid('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignUuid('responsible_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'criticality']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_processes');
    }
};
