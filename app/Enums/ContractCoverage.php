<?php

namespace App\Enums;

enum ContractCoverage: string
{
    case AroundTheClock = 'around_the_clock';
    case Extended = 'extended';
    case BusinessHours = 'business_hours';
    case OnCall = 'on_call';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::AroundTheClock => '24/7 (rund um die Uhr)',
            self::Extended => 'Erweitert (Mo–Sa)',
            self::BusinessHours => 'Geschäftszeiten (Mo–Fr)',
            self::OnCall => 'Rufbereitschaft',
            self::Custom => 'Individuell',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::AroundTheClock => 'emerald',
            self::Extended => 'sky',
            self::BusinessHours => 'amber',
            self::OnCall => 'violet',
            self::Custom => 'zinc',
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
