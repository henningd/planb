<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('api_token_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('system_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('incident_report_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');                 // zabbix, prometheus
            $table->string('idempotency_key');
            $table->string('severity')->nullable();   // info, warning, average, high, disaster, critical
            $table->string('status');                 // firing, resolved
            $table->string('host')->nullable();
            $table->string('subject')->nullable();
            $table->json('payload');
            $table->string('handling');               // ignored, matched_existing, created_incident, no_system_match, severity_below_threshold
            $table->text('note')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique(['company_id', 'source', 'idempotency_key']);
            $table->index(['company_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_alerts');
    }
};
