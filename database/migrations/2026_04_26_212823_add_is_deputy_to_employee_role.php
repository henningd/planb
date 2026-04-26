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
        Schema::table('employee_role', function (Blueprint $table) {
            // Markiert eine Rollen-Zuordnung als Vertretung (statt Hauptperson).
            // Pro Rolle sind beliebig viele Hauptpersonen UND beliebig viele
            // Vertreter zulässig. Wechsel zwischen Haupt/Vertr. wird von
            // AssignmentSync als neue Zeile abgebildet, die Historie bleibt.
            $table->boolean('is_deputy')->default(false)->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_role', function (Blueprint $table) {
            $table->dropColumn('is_deputy');
        });
    }
};
