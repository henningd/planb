<?php

namespace App\Enums;

enum RiskCategory: string
{
    case Technical = 'technical';
    case Organizational = 'organizational';
    case Operational = 'operational';
    case Legal = 'legal';
    case ThirdParty = 'third_party';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technisch',
            self::Organizational => 'Organisatorisch',
            self::Operational => 'Operativ',
            self::Legal => 'Rechtlich / Compliance',
            self::ThirdParty => 'Dritte / Lieferkette',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Technical => 'sky',
            self::Organizational => 'violet',
            self::Operational => 'amber',
            self::Legal => 'rose',
            self::ThirdParty => 'emerald',
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
