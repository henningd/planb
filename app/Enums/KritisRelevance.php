<?php

namespace App\Enums;

enum KritisRelevance: string
{
    case Yes = 'yes';
    case No = 'no';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Ja',
            self::No => 'Nein',
            self::Pending => 'Prüfung ausstehend',
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
