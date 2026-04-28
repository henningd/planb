<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_system', function (Blueprint $table) {
            $table->foreignUuid('risk_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_id')->constrained('systems')->cascadeOnDelete();

            $table->primary(['risk_id', 'system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_system');
    }
};
