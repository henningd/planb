<?php

use Database\Seeders\DataProtectionAuthoritiesSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Spielt die Reference-Daten der 16 Landes-DPAs + BfDI ein.
 *
 * Hintergrund: die Schema-Migration wurde im Deploy bereits als „run"
 * markiert, bevor sie den Seeder-Aufruf enthielt — auf Live blieben die
 * Tabellen daher leer. Diese Folge-Migration spielt die Daten nach. Der
 * Seeder ist idempotent (updateOrCreate per `key`), so dass mehrfaches
 * Laufen unkritisch bleibt.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new DataProtectionAuthoritiesSeeder)->run();
    }

    public function down(): void
    {
        // Bewusst leer: Reference-Daten werden nicht durch Down-Migrations entfernt.
    }
};
