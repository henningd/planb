<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_provider_system', function (Blueprint $table) {
            $table->foreignUuid('system_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_provider_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->primary(['system_id', 'service_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provider_system');
    }
};
