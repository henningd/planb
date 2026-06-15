<?php

namespace App\Casts;

use App\Enums\Industry;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Toleranter Cast für die Branche.
 *
 * Unbekannte oder ungültige Werte (z. B. Freitext aus Alt-Daten oder einem
 * externen Import) fallen auf {@see Industry::Sonstiges} zurück, statt beim
 * Laden des Modells einen ValueError zu werfen und damit die ganze Seite/App
 * für diesen Mandanten abstürzen zu lassen.
 *
 * @implements CastsAttributes<Industry|null, Industry|string|null>
 */
class IndustryEnum implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Industry
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Industry::tryFrom((string) $value) ?? Industry::Sonstiges;
    }

    /**
     * @param  Industry|string|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Industry) {
            return $value->value;
        }

        return (Industry::tryFrom((string) $value) ?? Industry::Sonstiges)->value;
    }
}
