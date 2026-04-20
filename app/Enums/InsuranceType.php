<?php

namespace App\Enums;

enum InsuranceType: string
{
    case Cyber = 'cyber';
    case Liability = 'liability';
    case Property = 'property';
    case BusinessInterruption = 'business_interruption';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cyber => 'Cyberversicherung',
            self::Liability => 'Haftpflicht',
            self::Property => 'Sachversicherung',
            self::BusinessInterruption => 'Betriebsunterbrechung',
            self::Other => 'Sonstige',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->toArray();
    }
}
