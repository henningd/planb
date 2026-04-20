<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Carbon;

/**
 * Replaces `{{ placeholder }}` tokens inside template bodies with concrete
 * values for preview or export. Unknown tokens stay untouched so the user
 * notices missing context in the rendered output.
 */
class TemplatePlaceholders
{
    /**
     * Known placeholder keys and their short labels. Used both for resolving
     * and for showing hints in the editor.
     *
     * @return array<string, string>
     */
    public static function known(): array
    {
        return [
            'firma' => 'Firmenname',
            'zeitpunkt' => 'Aktuelles Datum + Uhrzeit',
            'datum' => 'Aktuelles Datum',
            'vorfall' => 'Titel/Beschreibung des Vorfalls',
            'ansprechpartner' => 'Primärer Ansprechpartner',
        ];
    }

    /**
     * @param  array<string, string|null>  $overrides  per-call values, e.g. from an incident.
     */
    public static function resolve(string $text, ?Company $company, array $overrides = []): string
    {
        $values = self::buildValues($company, $overrides);

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/u',
            static fn (array $m) => $values[mb_strtolower($m[1])] ?? $m[0],
            $text,
        ) ?? $text;
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, string>
     */
    protected static function buildValues(?Company $company, array $overrides): array
    {
        $primary = $company?->contacts()->where('is_primary', true)->first();
        $now = Carbon::now();

        $values = [
            'firma' => (string) ($company?->name ?? ''),
            'zeitpunkt' => $now->translatedFormat('d.m.Y, H:i').' Uhr',
            'datum' => $now->translatedFormat('d.m.Y'),
            'vorfall' => '',
            'ansprechpartner' => (string) ($primary?->name ?? ''),
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $values[mb_strtolower($key)] = (string) $value;
        }

        return $values;
    }
}
