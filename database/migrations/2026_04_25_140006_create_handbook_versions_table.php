<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handbook_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->date('changed_at');
            $table->foreignUuid('changed_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('change_reason');
            $table->date('approved_at')->nullable();
            $table->foreignUuid('approved_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('approved_by_name')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_versions');
    }
};
