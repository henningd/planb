<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entfernt die redundante Spalte `locations_count`. Die Anzahl der Standorte
 * wird ab sofort aus den tatsächlichen Location-Datensätzen abgeleitet
 * (Company::locations()->count()), damit es nur noch eine Wahrheitsquelle gibt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('locations_count');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('locations_count')->nullable();
        });
    }
};
