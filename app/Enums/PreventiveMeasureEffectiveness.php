<?php

namespace App\Enums;

enum PreventiveMeasureEffectiveness: string
{
    case NotAssessed = 'not_assessed';
    case Effective = 'effective';
    case Partial = 'partial';
    case Ineffective = 'ineffective';

    public function label(): string
    {
        return match ($this) {
            self::NotAssessed => 'Noch nicht bewertet',
            self::Effective => 'Wirksam',
            self::Partial => 'Teilweise wirksam',
            self::Ineffective => 'Unwirksam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotAssessed => 'zinc',
            self::Effective => 'emerald',
            self::Partial => 'amber',
            self::Ineffective => 'rose',
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
