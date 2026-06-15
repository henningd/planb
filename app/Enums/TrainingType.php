<?php

namespace App\Enums;

enum TrainingType: string
{
    case Bcm = 'bcm';
    case Security = 'security';
    case Leadership = 'leadership';
    case DataProtection = 'data_protection';

    public function label(): string
    {
        return match ($this) {
            self::Bcm => 'BCM-/Notfallschulung',
            self::Security => 'IT-Sicherheit',
            self::Leadership => 'Leitungsschulung',
            self::DataProtection => 'Datenschutz',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bcm => 'sky',
            self::Security => 'indigo',
            self::Leadership => 'amber',
            self::DataProtection => 'teal',
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
