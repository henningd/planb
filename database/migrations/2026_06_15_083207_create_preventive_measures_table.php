<?php

use App\Enums\PreventiveMeasureStatus;
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
        Schema::create('preventive_measures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('status')->default(PreventiveMeasureStatus::Planned->value);
            $table->string('interval')->nullable();
            $table->date('target_date')->nullable();
            $table->date('last_executed_at')->nullable();
            $table->date('next_due_at')->nullable();
            $table->string('effectiveness')->nullable();
            $table->foreignUuid('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignUuid('responsible_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignUuid('risk_id')->nullable()->constrained('risks')->nullOnDelete();
            $table->text('result_notes')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['system_id', 'status']);
            $table->index('next_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventive_measures');
    }
};
