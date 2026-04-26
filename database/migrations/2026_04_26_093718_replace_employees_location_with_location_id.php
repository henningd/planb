<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tauscht den freien Text `employees.location` gegen einen FK auf
     * die Standorte-Tabelle aus. Bestehende Texte werden anhand des
     * Namens (case-insensitive) auf die Standorte derselben Firma
     * gemappt; nicht auflösbare Texte bleiben verloren (akzeptabel,
     * da das Feld bisher rein dekorativ war).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignUuid('location_id')->nullable()->after('email')->constrained('locations')->nullOnDelete();
        });

        $rows = DB::table('employees')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->get(['id', 'company_id', 'location']);

        foreach ($rows as $row) {
            $locationId = DB::table('locations')
                ->where('company_id', $row->company_id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($row->location)])
                ->value('id');

            if ($locationId !== null) {
                DB::table('employees')->where('id', $row->id)->update(['location_id' => $locationId]);
            }
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('location')->nullable()->after('email');
        });

        $rows = DB::table('employees')
            ->whereNotNull('location_id')
            ->get(['id', 'location_id']);

        foreach ($rows as $row) {
            $name = DB::table('locations')->where('id', $row->location_id)->value('name');
            if ($name !== null) {
                DB::table('employees')->where('id', $row->id)->update(['location' => $name]);
            }
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
