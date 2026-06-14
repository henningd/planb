<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag, ob ein Nutzer die Zwei-Faktor-Authentifizierung einrichten MUSS.
 * Bestandsnutzer erhalten den Default false (werden nicht gezwungen, arbeiten
 * wie zuvor weiter). Neue Registrierungen werden in CreateNewUser auf true
 * gesetzt und somit zur 2FA-Einrichtung geführt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_required')->default(false)->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_required');
        });
    }
};
