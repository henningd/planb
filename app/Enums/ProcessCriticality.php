<?php

namespace App\Enums;

enum ProcessCriticality: string
{
    case Niedrig = 'niedrig';
    case Mittel = 'mittel';
    case Hoch = 'hoch';
    case Existenzkritisch = 'existenzkritisch';

    public function label(): string
    {
        return match ($this) {
            self::Niedrig => 'Niedrig',
            self::Mittel => 'Mittel',
            self::Hoch => 'Hoch',
            self::Existenzkritisch => 'Existenzkritisch',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Niedrig => 'zinc',
            self::Mittel => 'sky',
            self::Hoch => 'amber',
            self::Existenzkritisch => 'rose',
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
