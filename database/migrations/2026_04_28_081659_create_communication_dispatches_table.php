<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('communication_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dispatched_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('dispatched_at');
            $table->timestamps();

            $table->index(['company_id', 'dispatched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_dispatches');
    }
};
