<?php

namespace App\Enums;

enum Nis2Classification: string
{
    case Essential = 'essential';
    case Important = 'important';
    case NotAffected = 'not_affected';

    public function label(): string
    {
        return match ($this) {
            self::Essential => 'Wesentliche Einrichtung',
            self::Important => 'Wichtige Einrichtung',
            self::NotAffected => 'Nicht betroffen',
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
