<?php

namespace App\Enums;

/**
 * Reifegrad-Stufen des BCMS nach dem BSI-200-4-Stufenmodell.
 */
enum BcmsStage: string
{
    case Reaktiv = 'reaktiv';
    case Aufbau = 'aufbau';
    case Standard = 'standard';

    public function label(): string
    {
        return match ($this) {
            self::Reaktiv => 'Reaktiv-BCMS',
            self::Aufbau => 'Aufbau-BCMS',
            self::Standard => 'Standard-BCMS',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reaktiv => 'rose',
            self::Aufbau => 'amber',
            self::Standard => 'emerald',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Reaktiv => 'Es wird erst im Ereignisfall reagiert; ein strukturiertes BCMS ist noch nicht etabliert.',
            self::Aufbau => 'Erste Prozesse und Pläne sind vorhanden, das BCMS befindet sich im Aufbau.',
            self::Standard => 'Ein vollständiges, gelebtes und kontinuierlich verbessertes BCMS ist etabliert.',
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
