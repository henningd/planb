<?php

use App\Enums\RiskMitigationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_mitigations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('risk_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default(RiskMitigationStatus::Planned->value);
            $table->date('target_date')->nullable();
            $table->date('implemented_at')->nullable();
            $table->foreignUuid('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index(['risk_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_mitigations');
    }
};
