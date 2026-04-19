<?php

namespace App\Enums;

enum Industry: string
{
    case Handwerk = 'handwerk';
    case Handel = 'handel';
    case Dienstleistung = 'dienstleistung';
    case Produktion = 'produktion';
    case Sonstiges = 'sonstiges';

    public function label(): string
    {
        return match ($this) {
            self::Handwerk => 'Handwerk',
            self::Handel => 'Handel',
            self::Dienstleistung => 'Dienstleistung',
            self::Produktion => 'Produktion',
            self::Sonstiges => 'Sonstiges',
        };
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->toArray();
    }
}
