<?php

namespace App\Enums;

enum PreventiveMeasureStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Active = 'active';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Geplant',
            self::InProgress => 'In Umsetzung',
            self::Active => 'Aktiv',
            self::Paused => 'Ausgesetzt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'zinc',
            self::InProgress => 'amber',
            self::Active => 'emerald',
            self::Paused => 'rose',
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
