<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_system', function (Blueprint $table) {
            $table->foreignUuid('system_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['system_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_system');
    }
};
