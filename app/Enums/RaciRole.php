<?php

namespace App\Enums;

enum RaciRole: string
{
    case Accountable = 'A';
    case Responsible = 'R';
    case Consulted = 'C';
    case Informed = 'I';

    public function label(): string
    {
        return match ($this) {
            self::Accountable => 'Verantwortlich',
            self::Responsible => 'Durchführend',
            self::Consulted => 'Konsultiert',
            self::Informed => 'Informiert',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Accountable => 'Trägt die Rechenschaft für das Ergebnis (genau eine Person pro Aufgabe empfohlen).',
            self::Responsible => 'Führt die Aufgabe operativ durch.',
            self::Consulted => 'Wird vor der Entscheidung konsultiert (Zweiwegkommunikation).',
            self::Informed => 'Wird über das Ergebnis informiert (Einwegkommunikation).',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Accountable => 'rose',
            self::Responsible => 'amber',
            self::Consulted => 'sky',
            self::Informed => 'zinc',
        };
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $case) => [
                'value' => $case->value,
                'label' => $case->value.' – '.$case->label(),
            ])
            ->values()
            ->toArray();
    }
}
