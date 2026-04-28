<?php

namespace App\Enums;

enum RiskMitigationStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Implemented = 'implemented';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Geplant',
            self::InProgress => 'In Umsetzung',
            self::Implemented => 'Umgesetzt',
            self::Verified => 'Verifiziert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'zinc',
            self::InProgress => 'amber',
            self::Implemented => 'sky',
            self::Verified => 'emerald',
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
