<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_dependencies', function (Blueprint $table) {
            $table->foreignUuid('system_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('depends_on_system_id')->constrained('systems')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['system_id', 'depends_on_system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_dependencies');
    }
};
