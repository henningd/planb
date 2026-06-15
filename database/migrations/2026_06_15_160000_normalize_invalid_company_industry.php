<?php

use App\Enums\Industry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Setzt ungültige/Legacy-Branchenwerte (Freitext, der kein gültiger
     * Industry-Enum-Wert ist) auf 'sonstiges' zurück, damit der Enum-Cast
     * nicht mehr fehlschlägt.
     */
    public function up(): void
    {
        $valid = array_map(fn (Industry $c) => $c->value, Industry::cases());

        DB::table('companies')
            ->whereNotNull('industry')
            ->whereNotIn('industry', $valid)
            ->update(['industry' => Industry::Sonstiges->value]);
    }

    public function down(): void
    {
        // Keine sinnvolle Umkehrung — Originalwerte sind verloren.
    }
};
