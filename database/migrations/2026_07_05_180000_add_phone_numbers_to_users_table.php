<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telefonnummern je Benutzer (Mobil, Festnetz/normal, Notruf), damit man einen
 * Nutzer aus der App heraus direkt kontaktieren kann (z. B. aus dem Verlauf).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_phone')->nullable()->after('email');
            $table->string('phone')->nullable()->after('mobile_phone');
            $table->string('emergency_phone')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile_phone', 'phone', 'emergency_phone']);
        });
    }
};
