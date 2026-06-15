<?php

namespace App\Enums;

enum PreventiveMeasureInterval: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Biannually = 'biannually';
    case Yearly = 'yearly';
    case BiYearly = 'bi_yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monatlich',
            self::Quarterly => 'Quartalsweise',
            self::Biannually => 'Halbjährlich',
            self::Yearly => 'Jährlich',
            self::BiYearly => 'Alle 2 Jahre',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Biannually => 6,
            self::Yearly => 12,
            self::BiYearly => 24,
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
