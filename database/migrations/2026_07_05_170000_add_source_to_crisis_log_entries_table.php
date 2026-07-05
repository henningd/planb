<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hält fest, aus welcher Oberfläche eine Krisen-Logbuch-Aktion stammt
 * (web = Dashboard/Cockpit, app = Notfall-App, system = automatisch).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crisis_log_entries', function (Blueprint $table) {
            $table->string('source', 16)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('crisis_log_entries', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
