<?php

namespace App\Enums;

/**
 * Verantwortungs-Kategorien auf System-Ebene (statt RACI).
 *
 * RACI ist für Aufgaben/Aktionen gedacht; ein System ist ein Zustand
 * mit klaren Eigentums-Rollen: wer entscheidet (Owner), wer betreibt
 * (Operator) und wer fachlich auskunftsfähig ist (ContactPerson).
 * Aufgaben behalten weiterhin ihr klassisches RACI.
 */
enum SystemOwnership: string
{
    case Owner = 'owner';
    case Operator = 'operator';
    case ContactPerson = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'System-Eigentümer',
            self::Operator => 'Administrator / Operator',
            self::ContactPerson => 'Fachlicher Ansprechpartner',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Owner => 'Eigentümer',
            self::Operator => 'Operator',
            self::ContactPerson => 'Ansprechpartner',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Trägt die Eigentumsverantwortung: entscheidet über Investitionen, Beschaffung, Außerbetriebnahme. Genau eine Hauptperson empfohlen.',
            self::Operator => 'Betreibt das System operativ: Konfiguration, Wartung, Wiederanlauf, Patches. Kann mehrere Hauptpersonen haben.',
            self::ContactPerson => 'Kennt fachliche Zusammenhänge und Nutzerbedarfe. Wird bei Änderungen konsultiert.',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Owner => 'rose',
            self::Operator => 'amber',
            self::ContactPerson => 'sky',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Owner => 'key',
            self::Operator => 'wrench-screwdriver',
            self::ContactPerson => 'chat-bubble-left-right',
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
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [self::Owner, self::Operator, self::ContactPerson];
    }
}
