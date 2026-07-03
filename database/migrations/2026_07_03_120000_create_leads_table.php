<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('source')->default('nis2-quick-check');
            $table->json('answers')->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->string('readiness')->nullable();
            $table->boolean('consent_marketing')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('report_sent_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'created_at']);
            $table->index('email');
            $table->index('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
