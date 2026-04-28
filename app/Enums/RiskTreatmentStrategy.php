<?php

namespace App\Enums;

enum RiskTreatmentStrategy: string
{
    case Mitigate = 'mitigate';
    case Accept = 'accept';
    case Transfer = 'transfer';
    case Avoid = 'avoid';

    public function label(): string
    {
        return match ($this) {
            self::Mitigate => 'Reduzieren (Maßnahmen)',
            self::Accept => 'Akzeptieren',
            self::Transfer => 'Übertragen (Versicherung/Partner)',
            self::Avoid => 'Vermeiden',
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
