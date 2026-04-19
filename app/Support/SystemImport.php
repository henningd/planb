<?php

namespace App\Support;

use App\Enums\SystemCategory;

/**
 * Parses and validates a JSON payload describing a list of systems.
 *
 * Accepted shapes:
 *   1. { "version": 1, "systems": [ ... ] }
 *   2. [ ... ]                                     (bare array, version implied = 1)
 *
 * Each system item:
 *   - name            string, required, max 255
 *   - description     string, optional, max 2000
 *   - category        string, required, one of SystemCategory values
 *   - priority        string, optional (will be matched by name against company priorities)
 *   - rto_minutes     int, optional, must be one of Duration::OPTIONS keys
 *   - rpo_minutes     int, optional, must be one of Duration::OPTIONS keys
 */
class SystemImport
{
    public const CURRENT_VERSION = 1;

    public const MAX_ITEMS = 500;

    /**
     * @param  array<int, array<string, mixed>>  $systems
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public readonly array $systems,
        public readonly array $errors,
    ) {}

    public static function fromJson(string $raw): self
    {
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return new self([], ['Ungültiges JSON oder falsches Format.']);
        }

        if (isset($decoded['systems'])) {
            $version = $decoded['version'] ?? self::CURRENT_VERSION;
            if ($version !== self::CURRENT_VERSION) {
                return new self([], ["Nicht unterstützte Version: {$version}. Erwartet: ".self::CURRENT_VERSION.'.']);
            }
            $items = $decoded['systems'];
        } else {
            $items = $decoded;
        }

        if (! is_array($items) || array_is_list($items) === false) {
            return new self([], ['Feld "systems" muss eine Liste sein.']);
        }

        if (count($items) === 0) {
            return new self([], ['Keine Systeme in der Datei gefunden.']);
        }

        if (count($items) > self::MAX_ITEMS) {
            return new self([], ['Datei enthält zu viele Systeme (max. '.self::MAX_ITEMS.').']);
        }

        return self::validate($items);
    }

    /**
     * @param  array<int, mixed>  $items
     */
    protected static function validate(array $items): self
    {
        $validCategories = array_column(SystemCategory::cases(), 'value');
        $validDurations = array_keys(Duration::OPTIONS);

        $cleaned = [];
        $errors = [];

        foreach ($items as $i => $item) {
            $index = $i + 1;

            if (! is_array($item)) {
                $errors[] = "Eintrag #{$index}: Objekt erwartet.";

                continue;
            }

            if (empty($item['name']) || ! is_string($item['name'])) {
                $errors[] = "Eintrag #{$index}: Feld 'name' fehlt oder ist ungültig.";

                continue;
            }

            if (mb_strlen($item['name']) > 255) {
                $errors[] = "Eintrag #{$index}: 'name' zu lang (max. 255 Zeichen).";

                continue;
            }

            if (empty($item['category']) || ! is_string($item['category']) || ! in_array($item['category'], $validCategories, true)) {
                $errors[] = "Eintrag #{$index}: 'category' muss einer von ".implode(', ', $validCategories).' sein.';

                continue;
            }

            if (isset($item['description']) && (! is_string($item['description']) || mb_strlen($item['description']) > 2000)) {
                $errors[] = "Eintrag #{$index}: 'description' ungültig oder zu lang.";

                continue;
            }

            if (isset($item['priority']) && $item['priority'] !== null && ! is_string($item['priority'])) {
                $errors[] = "Eintrag #{$index}: 'priority' muss ein String (Name der Stufe) sein.";

                continue;
            }

            foreach (['rto_minutes', 'rpo_minutes'] as $durField) {
                if (isset($item[$durField]) && $item[$durField] !== null) {
                    if (! is_int($item[$durField]) || ! in_array($item[$durField], $validDurations, true)) {
                        $errors[] = "Eintrag #{$index}: '{$durField}' muss einer von: ".implode(', ', $validDurations).'.';

                        continue 2;
                    }
                }
            }

            if (isset($item['downtime_cost_per_hour']) && $item['downtime_cost_per_hour'] !== null) {
                if (! is_int($item['downtime_cost_per_hour']) || $item['downtime_cost_per_hour'] < 0 || $item['downtime_cost_per_hour'] > 100000000) {
                    $errors[] = "Eintrag #{$index}: 'downtime_cost_per_hour' muss eine positive ganze Zahl (Euro) sein.";

                    continue;
                }
            }

            $cleaned[] = [
                'name' => trim($item['name']),
                'description' => isset($item['description']) ? trim($item['description']) : null,
                'category' => $item['category'],
                'priority' => isset($item['priority']) && $item['priority'] !== '' ? trim($item['priority']) : null,
                'rto_minutes' => $item['rto_minutes'] ?? null,
                'rpo_minutes' => $item['rpo_minutes'] ?? null,
                'downtime_cost_per_hour' => $item['downtime_cost_per_hour'] ?? null,
            ];
        }

        return new self($cleaned, $errors);
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
