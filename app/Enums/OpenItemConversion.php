<?php

namespace App\Enums;

/**
 * Worin ein offener Punkt bei seiner Erledigung überführt wurde — der
 * Nachweis, dass ein Klärpunkt nicht einfach verschwindet, sondern in ein
 * belastbares Artefakt (Risiko, Maßnahme, Szenario oder Test) übergeht.
 */
enum OpenItemConversion: string
{
    case Risk = 'risk';
    case Measure = 'measure';
    case Scenario = 'scenario';
    case Test = 'test';

    public function label(): string
    {
        return match ($this) {
            self::Risk => 'In Risiko überführt',
            self::Measure => 'In Maßnahme überführt',
            self::Scenario => 'In Szenario überführt',
            self::Test => 'In Test überführt',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Risk => 'Risiko',
            self::Measure => 'Maßnahme',
            self::Scenario => 'Szenario',
            self::Test => 'Test',
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
