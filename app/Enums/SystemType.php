<?php

namespace App\Enums;

enum SystemType: string
{
    case Anwendung = 'anwendung';
    case Kommunikation = 'kommunikation';
    case Server = 'server';
    case Infrastruktur = 'infrastruktur';

    public function label(): string
    {
        return match ($this) {
            self::Anwendung => 'Anwendung',
            self::Kommunikation => 'Kommunikation',
            self::Server => 'Server',
            self::Infrastruktur => 'Infrastruktur',
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
