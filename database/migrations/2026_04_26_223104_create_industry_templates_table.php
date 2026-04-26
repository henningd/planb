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
        Schema::create('industry_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('industry', 50);
            $table->text('description')->nullable();
            // Backup-kompatibles Payload — derselbe Aufbau wie Backup-Export.
            // Wird beim Apply via App\Support\Backup\Importer in den Mandanten
            // gespiegelt. Nullable, damit man auch Templates ohne Inhalte als
            // Platzhalter anlegen kann.
            $table->longText('payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['industry', 'sort']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_templates');
    }
};
