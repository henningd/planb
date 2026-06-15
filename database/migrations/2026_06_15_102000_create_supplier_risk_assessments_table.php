<?php

use App\Enums\SecurityAssessmentStatus;
use App\Enums\SupplierCriticality;
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
        Schema::create('supplier_risk_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_provider_id')->constrained()->cascadeOnDelete();
            $table->string('criticality')->default(SupplierCriticality::Mittel->value);
            $table->string('security_status')->default(SecurityAssessmentStatus::NotAssessed->value);
            $table->date('last_assessed_at')->nullable();
            $table->string('interval')->nullable();
            $table->date('next_assessment_at')->nullable();
            $table->text('alternative_provider')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['service_provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_risk_assessments');
    }
};
