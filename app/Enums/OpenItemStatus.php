<?php

namespace App\Enums;

enum OpenItemStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::InProgress => 'In Klärung',
            self::Resolved => 'Erledigt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::InProgress => 'blue',
            self::Resolved => 'emerald',
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
