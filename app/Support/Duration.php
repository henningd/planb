<?php

namespace App\Support;

/**
 * Shared set of duration steps for RTO/RPO and similar planning values.
 * Values are stored as minutes; labels are user-friendly German.
 */
class Duration
{
    /**
     * @var array<int, string>
     */
    public const OPTIONS = [
        15 => '15 Minuten',
        60 => '1 Stunde',
        240 => '4 Stunden',
        480 => '8 Stunden',
        1440 => '1 Tag',
        4320 => '3 Tage',
        10080 => '1 Woche',
    ];

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::OPTIONS as $minutes => $label) {
            $options[] = ['value' => $minutes, 'label' => $label];
        }

        return $options;
    }

    public static function format(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        return self::OPTIONS[$minutes] ?? $minutes.' Min.';
    }

    /**
     * Render a minute value as a readable amount of hours — used where users
     * think in hours rather than minutes (e.g. business-process recovery
     * objectives). 240 → "4 Stunden", 90 → "1,5 Stunden", 60 → "1 Stunde".
     */
    public static function inHours(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        $hours = $minutes / 60;
        $label = rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',');

        return $label.' '.($hours === 1.0 ? 'Stunde' : 'Stunden');
    }
}
