<?php

namespace App\Enums;

enum Industry: string
{
    case Handwerk = 'handwerk';
    case Handel = 'handel';
    case Dienstleistung = 'dienstleistung';
    case Produktion = 'produktion';
    case OeffentlicheEinrichtung = 'oeffentliche_einrichtung';
    case Sonstiges = 'sonstiges';

    public function label(): string
    {
        return match ($this) {
            self::Handwerk => 'Handwerk',
            self::Handel => 'Handel',
            self::Dienstleistung => 'Dienstleistung',
            self::Produktion => 'Produktion',
            self::OeffentlicheEinrichtung => 'Öffentliche Einrichtung',
            self::Sonstiges => 'Sonstiges',
        };
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        $collator = class_exists(\Collator::class) ? new \Collator('de_DE') : null;

        return collect(self::cases())
            ->map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()])
            ->sort(fn (array $a, array $b) => $collator
                ? $collator->compare($a['label'], $b['label'])
                : strcmp($a['label'], $b['label']))
            ->values()
            ->toArray();
    }
}
