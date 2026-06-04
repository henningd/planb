<?php

namespace App\Enums;

/**
 * Bestimmt, wie die Ausfallkosten eines Systems in Summen einfließen:
 *  - Own                → nur die eigenen Kosten (Standard).
 *  - FromDependents     → eigene Kosten deaktiviert, Schaden ergibt sich aus den
 *                         (transitiv) abhängigen Systemen (z. B. Stromversorgung).
 *  - OwnPlusDependents  → eigene Kosten PLUS die der abhängigen Systeme.
 */
enum DowntimeCostMode: string
{
    case Own = 'own';
    case FromDependents = 'from_dependents';
    case OwnPlusDependents = 'own_plus_dependents';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Nur eigene Ausfallkosten',
            self::FromDependents => 'Aus abhängigen Systemen berechnen',
            self::OwnPlusDependents => 'Eigene Kosten + abhängige Systeme',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Own => 'Es zählen ausschließlich die hier hinterlegten Stundenkosten.',
            self::FromDependents => 'Die eigenen Kosten zählen nicht; der Schaden ergibt sich aus den abhängigen Systemen (verhindert Doppelzählung bei Träger-Systemen wie der Stromversorgung).',
            self::OwnPlusDependents => 'Die eigenen Kosten werden zu denen der abhängigen Systeme addiert.',
        };
    }

    /**
     * Zählen die eigenen Stundenkosten des Systems in Summen mit?
     */
    public function countsOwn(): bool
    {
        return $this !== self::FromDependents;
    }

    /**
     * Bezieht der Modus die abhängigen Systeme in die Kostenrechnung ein?
     */
    public function aggregatesDependents(): bool
    {
        return $this !== self::Own;
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ],
            self::cases(),
        );
    }
}
