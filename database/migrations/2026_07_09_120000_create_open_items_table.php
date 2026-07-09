<?php

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
        Schema::create('open_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('relevance')->nullable();
            $table->foreignUuid('risk_id')->nullable()->constrained('risks')->nullOnDelete();
            $table->foreignUuid('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignUuid('responsible_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->date('due_at')->nullable();
            $table->date('review_at')->nullable();
            $table->string('status')->default('open');
            $table->string('conversion')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('due_at');
            $table->index('review_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_items');
    }
};
