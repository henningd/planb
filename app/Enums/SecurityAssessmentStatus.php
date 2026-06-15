<?php

namespace App\Enums;

enum SecurityAssessmentStatus: string
{
    case NotAssessed = 'not_assessed';
    case InProgress = 'in_progress';
    case Passed = 'passed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NotAssessed => 'Nicht bewertet',
            self::InProgress => 'In Prüfung',
            self::Passed => 'Bestanden',
            self::Failed => 'Nicht bestanden',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotAssessed => 'zinc',
            self::InProgress => 'amber',
            self::Passed => 'emerald',
            self::Failed => 'rose',
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
