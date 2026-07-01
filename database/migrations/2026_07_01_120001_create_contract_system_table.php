<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_system', function (Blueprint $table) {
            $table->foreignUuid('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['contract_id', 'system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_system');
    }
};
