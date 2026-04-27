<?php

namespace App\Support\Settings;

/**
 * Single source of truth for every system/company setting:
 * key, scope, type, hardcoded fallback default, UI label.
 *
 * Adding a new setting = add one entry here, then wire its effect.
 */
class SettingsCatalog
{
    public const SYSTEM = 'system';

    public const COMPANY = 'company';

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public static function all(): array
    {
        return [
            'registration_enabled' => [
                'scope' => self::SYSTEM,
                'type' => 'bool',
                'default' => true,
                'label' => 'Registrierung aktiv',
                'description' => 'Wenn deaktiviert, sehen Besucher kein Registrierungsformular.',
            ],
            'demo_locked' => [
                'scope' => self::SYSTEM,
                'type' => 'bool',
                'default' => false,
                'label' => 'Demo-Funktion sperren',
                'description' => 'Sperrt /admin/demo gegen versehentliches Wipe/Seed in Produktion.',
            ],
            'platform_name' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => '',
                'label' => 'Plattform-Name (Override)',
                'description' => 'Leer = APP_NAME aus .env. Wirkt im <title> und im Sidebar-Header.',
            ],
            'platform_footer' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => '',
                'label' => 'Plattform-Fußzeile',
                'description' => 'Optionaler Hinweis-Text (z. B. Impressum-Link), erscheint unten in der Sidebar.',
            ],

            'auto_pdf_enabled' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => false,
                'label' => 'Auto-PDF bei neuer Version',
                'description' => 'Erzeugt automatisch ein revisionssicheres PDF, sobald eine HandbookVersion angelegt wird.',
            ],
            'incident_mode_enabled' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => true,
                'label' => 'Live-Inzident-Modus',
                'description' => 'Zeigt im Ernstfall ein reduziertes Krisen-Cockpit mit Krisenstab, Wiederanlauf-Reihenfolge, Schritten und Meldepflichten. Bei einem aktiven Szenario-Lauf erscheint zusätzlich ein Banner.',
            ],
            'enforce_2fa_admins' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => false,
                'label' => '2FA-Pflicht für Team-Admins',
                'description' => 'Team-Admins ohne bestätigtes 2FA werden zur Security-Seite umgeleitet.',
            ],
            'share_link_default_days' => [
                'scope' => self::COMPANY,
                'type' => 'int',
                'default' => 30,
                'min' => 1,
                'max' => 365,
                'label' => 'Default-Laufzeit Freigabelinks (Tage)',
                'description' => 'Vorbelegung beim Anlegen eines neuen Freigabelinks.',
            ],
            'audit_retention_days' => [
                'scope' => self::COMPANY,
                'type' => 'int',
                'default' => 0,
                'min' => 0,
                'max' => 3650,
                'label' => 'Audit-Log Aufbewahrung (Tage)',
                'description' => '0 = unbegrenzt aufbewahren. Sonst tägliche Bereinigung älterer Einträge.',
            ],
            'pdf_paper_size' => [
                'scope' => self::COMPANY,
                'type' => 'enum',
                'default' => 'a4',
                'enum' => ['a4' => 'A4', 'letter' => 'US Letter'],
                'label' => 'PDF-Papierformat',
                'description' => '',
            ],
            'pdf_footer_show_hash' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => true,
                'label' => 'SHA-256 im PDF-Footer',
                'description' => 'Zeigt den PDF-Hash unten als Revisionsanker an.',
            ],
        ];
    }

    /**
     * @return array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}|null
     */
    public static function definition(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public static function byScope(string $scope): array
    {
        return array_filter(self::all(), fn ($def) => $def['scope'] === $scope);
    }

    public static function defaultFor(string $key): mixed
    {
        return self::definition($key)['default'] ?? null;
    }

    /**
     * Coerce a raw input value (typically string from a form) into the
     * type declared by the catalog. Unknown keys pass through unchanged.
     */
    public static function cast(string $key, mixed $value): mixed
    {
        $def = self::definition($key);
        if ($def === null) {
            return $value;
        }

        return match ($def['type']) {
            'bool' => (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'int' => (int) $value,
            'enum' => array_key_exists((string) $value, $def['enum'] ?? []) ? (string) $value : $def['default'],
            default => (string) $value,
        };
    }
}
