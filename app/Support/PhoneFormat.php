<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Formatiert Rufnummern für Anzeige und tel:-Links.
 *
 * Erwartet typischerweise E.164-Strings (z. B. "+4930123456"). Bei nicht
 * parsebaren Werten wird der ursprüngliche String fehlertolerant durchgereicht,
 * damit Altdaten ohne E.164-Normalisierung weiterhin lesbar bleiben.
 */
class PhoneFormat
{
    /**
     * Anzeigeformat: national, wenn die Nummer aus dem $defaultRegion-Land
     * stammt, sonst international.
     */
    public static function display(?string $value, string $defaultRegion = 'DE'): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $parsed = $util->parse((string) $value, $defaultRegion);
            $region = $util->getRegionCodeForNumber($parsed);

            $format = ($region === $defaultRegion)
                ? PhoneNumberFormat::NATIONAL
                : PhoneNumberFormat::INTERNATIONAL;

            return $util->format($parsed, $format);
        } catch (NumberParseException) {
            return (string) $value;
        }
    }

    /**
     * Für tel:-Links: E.164-Format (z. B. "+4930123456"). Fällt bei nicht
     * parsebaren Werten auf den Original-String zurück.
     */
    public static function tel(?string $value, string $defaultRegion = 'DE'): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $parsed = $util->parse((string) $value, $defaultRegion);

            return $util->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return (string) $value;
        }
    }
}
