<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verknüpft Risiken, Präventivmaßnahmen und Offene Punkte optional mit
     * einem Geschäftsprozess (BIA) — Grundlage für die Audit-Detailsicht.
     */
    public function up(): void
    {
        foreach (['risks', 'preventive_measures', 'open_items'] as $table) {
            if (! Schema::hasColumn($table, 'business_process_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignUuid('business_process_id')->nullable()->after('company_id')
                        ->constrained('business_processes')->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['risks', 'preventive_measures', 'open_items'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('business_process_id');
            });
        }
    }
};
