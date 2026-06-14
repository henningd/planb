<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bestandsnutzer „grandfathern": Vor Einführung von MustVerifyEmail gab es
 * keine E-Mail-Verifizierung, daher haben Alt-Accounts ein leeres
 * email_verified_at und würden nun ausgesperrt. Wir markieren alle bereits
 * existierenden Nutzer rückwirkend als verifiziert (Zeitpunkt = created_at),
 * damit sie wie zuvor arbeiten können. Neue Registrierungen ab jetzt müssen
 * ihre E-Mail regulär bestätigen.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update([
                'email_verified_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        // Bewusst kein Rollback: Verifizierungsdaten werden nicht zurückgesetzt,
        // da nicht unterscheidbar ist, welche Nutzer hier befüllt wurden.
    }
};
