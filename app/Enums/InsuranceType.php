<?php

namespace App\Enums;

enum InsuranceType: string
{
    case Cyber = 'cyber';
    case BusinessInterruption = 'business_interruption';
    case Electronics = 'electronics';
    case Building = 'building';
    case Liability = 'liability';
    case Contents = 'contents';
    case Machinery = 'machinery';
    case Transport = 'transport';
    case Property = 'property';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cyber => 'Cyberversicherung',
            self::BusinessInterruption => 'Betriebsunterbrechung',
            self::Electronics => 'Elektronikversicherung',
            self::Building => 'Gebäudeversicherung',
            self::Liability => 'Betriebshaftpflicht',
            self::Contents => 'Inhaltsversicherung',
            self::Machinery => 'Maschinenversicherung',
            self::Transport => 'Transportversicherung',
            self::Property => 'Sachversicherung',
            self::Other => 'Sonstige / branchenspezifisch',
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
