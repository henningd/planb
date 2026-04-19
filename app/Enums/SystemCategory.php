<?php

namespace App\Enums;

enum SystemCategory: string
{
    case Basisbetrieb = 'basisbetrieb';
    case Geschaeftsbetrieb = 'geschaeftsbetrieb';
    case Unterstuetzend = 'unterstuetzend';

    public function label(): string
    {
        return match ($this) {
            self::Basisbetrieb => 'Basisbetrieb',
            self::Geschaeftsbetrieb => 'Geschäftsbetrieb',
            self::Unterstuetzend => 'Unterstützend',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Basisbetrieb => 'Grundlegende Infrastruktur: Strom, Internet, Telefon, Kernserver. Ohne diese Systeme läuft nichts.',
            self::Geschaeftsbetrieb => 'Systeme, mit denen Umsatz erwirtschaftet wird: Warenwirtschaft, Kassensystem, CRM, Produktionssteuerung.',
            self::Unterstuetzend => 'Hilfssysteme für den Alltag, die kurzfristig kompensiert werden können.',
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
