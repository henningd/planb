<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_task_employee', function (Blueprint $table) {
            $table->foreignUuid('system_task_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('raci_role', 1);
            $table->timestamps();

            $table->primary(['system_task_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_task_employee');
    }
};
