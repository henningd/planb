<?php

namespace App\Enums;

enum ScenarioRunMode: string
{
    case Drill = 'drill';
    case Real = 'real';

    public function label(): string
    {
        return match ($this) {
            self::Drill => 'Übung',
            self::Real => 'Ernstfall',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Drill => 'indigo',
            self::Real => 'rose',
        };
    }
}
