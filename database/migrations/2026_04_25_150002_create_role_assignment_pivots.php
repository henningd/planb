<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_system', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_id')->constrained()->cascadeOnDelete();
            $table->string('raci_role')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->primary(['role_id', 'system_id']);
            $table->index('system_id');
        });

        Schema::create('role_system_task', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_task_id')->constrained()->cascadeOnDelete();
            $table->string('raci_role')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->primary(['role_id', 'system_task_id']);
            $table->index('system_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_system_task');
        Schema::dropIfExists('role_system');
    }
};
