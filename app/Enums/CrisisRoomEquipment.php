<?php

namespace App\Enums;

/**
 * Ausstattungsmerkmale eines Krisenraums / Lagezentrums. Als Mehrfachauswahl
 * (Checkboxen) am Firmenprofil hinterlegt und im Handbuch-PDF ausgewiesen.
 */
enum CrisisRoomEquipment: string
{
    case Phone = 'phone';
    case Screen = 'screen';
    case Whiteboard = 'whiteboard';
    case Printer = 'printer';
    case PaperBinder = 'paper_binder';
    case Power = 'power';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Telefon',
            self::Screen => 'Bildschirm',
            self::Whiteboard => 'Whiteboard',
            self::Printer => 'Drucker',
            self::PaperBinder => 'Papier-Notfallordner',
            self::Power => 'Strom / Netzwerk / LTE',
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

    /**
     * Robuste Label-Auflösung für gespeicherte Werte (überspringt Unbekanntes).
     *
     * @param  iterable<string>  $values
     * @return list<string>
     */
    public static function labelsFor(iterable $values): array
    {
        $labels = [];
        foreach ($values as $value) {
            $case = self::tryFrom((string) $value);
            if ($case !== null) {
                $labels[] = $case->label();
            }
        }

        return $labels;
    }
}
