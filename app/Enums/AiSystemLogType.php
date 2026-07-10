<?php

namespace App\Enums;

/**
 * Art eines Protokoll-Eintrags zu einem KI-System — Nachweisführung nach
 * EU-KI-Verordnung (u. a. Aufsicht Art. 14, Protokollierung Art. 12/26,
 * Vorfälle Art. 73).
 */
enum AiSystemLogType: string
{
    case Review = 'review';
    case Oversight = 'oversight';
    case Test = 'test';
    case Incident = 'incident';
    case Change = 'change';
    case Training = 'training';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Review => 'Prüfung',
            self::Oversight => 'Aufsichts-Eingriff',
            self::Test => 'Test / Validierung',
            self::Incident => 'Vorfall',
            self::Change => 'Änderung',
            self::Training => 'Schulung',
            self::Note => 'Notiz',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Review => 'sky',
            self::Oversight => 'indigo',
            self::Test => 'emerald',
            self::Incident => 'red',
            self::Change => 'amber',
            self::Training => 'violet',
            self::Note => 'zinc',
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
