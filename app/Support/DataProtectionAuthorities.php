<?php

namespace App\Support;

use App\Models\DataProtectionAuthority;
use App\Models\DataProtectionAuthorityPostalCodeRange;

/**
 * Auflöser für Datenschutz-Aufsichtsbehörden anhand der PLZ.
 *
 * Zuständigkeit richtet sich nach Bundesland (Sitz der Hauptniederlassung).
 * Die seedseite hinterlegt eine Liste „von-bis"-PLZ-Bereiche pro Behörde —
 * an Bundesland-Grenzen kann es Edge-Cases geben, die der Nutzer manuell
 * überschreiben muss.
 */
class DataProtectionAuthorities
{
    /**
     * Findet die für eine PLZ zuständige Behörde. Liefert `null`, wenn die
     * PLZ ungültig ist oder kein Bereich passt.
     */
    public static function resolveByPostalCode(?string $plz): ?DataProtectionAuthority
    {
        $normalized = self::normalize($plz);
        if ($normalized === null) {
            return null;
        }

        $range = DataProtectionAuthorityPostalCodeRange::query()
            ->where('plz_from', '<=', $normalized)
            ->where('plz_to', '>=', $normalized)
            ->with('authority')
            ->orderBy('plz_from')
            ->first();

        return $range?->authority;
    }

    /**
     * Normalisiert eine PLZ auf 5 Ziffern. Liefert `null` bei ungültiger Eingabe.
     */
    public static function normalize(?string $plz): ?string
    {
        if ($plz === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $plz);
        if ($digits === null || strlen($digits) !== 5) {
            return null;
        }

        return $digits;
    }
}
