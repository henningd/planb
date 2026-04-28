<?php

namespace App\Enums;

enum LessonLearnedActionItemStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::InProgress => 'In Bearbeitung',
            self::Done => 'Erledigt',
            self::Cancelled => 'Verworfen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'zinc',
            self::InProgress => 'amber',
            self::Done => 'emerald',
            self::Cancelled => 'rose',
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
