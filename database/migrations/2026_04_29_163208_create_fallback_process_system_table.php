<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fallback_process_system', function (Blueprint $table) {
            $table->foreignUuid('fallback_process_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_id')->constrained()->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->primary(['fallback_process_id', 'system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fallback_process_system');
    }
};
