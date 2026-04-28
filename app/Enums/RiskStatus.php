<?php

namespace App\Enums;

enum RiskStatus: string
{
    case Identified = 'identified';
    case Assessed = 'assessed';
    case Mitigated = 'mitigated';
    case Accepted = 'accepted';
    case Transferred = 'transferred';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Identified => 'Identifiziert',
            self::Assessed => 'Bewertet',
            self::Mitigated => 'Behandelt',
            self::Accepted => 'Akzeptiert',
            self::Transferred => 'Übertragen',
            self::Closed => 'Geschlossen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Identified => 'zinc',
            self::Assessed => 'amber',
            self::Mitigated => 'emerald',
            self::Accepted => 'sky',
            self::Transferred => 'violet',
            self::Closed => 'zinc',
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
