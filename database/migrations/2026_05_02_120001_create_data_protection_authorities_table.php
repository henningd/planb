<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_protection_authorities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('state')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('breach_notification_url')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('data_protection_authority_postal_code_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('data_protection_authority_id')
                ->constrained('data_protection_authorities')
                ->cascadeOnDelete();
            $table->string('plz_from', 5);
            $table->string('plz_to', 5);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['plz_from', 'plz_to'], 'dpa_plz_range_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_protection_authority_postal_code_ranges');
        Schema::dropIfExists('data_protection_authorities');
    }
};
