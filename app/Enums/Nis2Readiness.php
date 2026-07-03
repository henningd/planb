<?php

namespace App\Enums;

/**
 * Ampelstufen der NIS2-Bereitschaft aus dem öffentlichen Quick-Check.
 *
 * Aus der erreichten Punktzahl im Verhältnis zum Maximum wird eine von drei
 * Stufen abgeleitet (<40 % Kritisch, <80 % Aufbau, sonst Solide). Bewusst
 * eigenständig gehalten gegenüber {@see BcmsStage}, da der Quick-Check ein
 * marketingseitiges Selbst-Assessment ist und nicht das BSI-200-4-Modell des
 * internen Reifegrad-Tools abbildet.
 */
enum Nis2Readiness: string
{
    case Kritisch = 'kritisch';
    case Aufbau = 'aufbau';
    case Solide = 'solide';

    public function label(): string
    {
        return match ($this) {
            self::Kritisch => 'Kritischer Handlungsbedarf',
            self::Aufbau => 'Auf dem Weg',
            self::Solide => 'Solide aufgestellt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Kritisch => 'rose',
            self::Aufbau => 'amber',
            self::Solide => 'emerald',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Kritisch => 'Bei den NIS2-Grundpflichten bestehen erhebliche Lücken. Ohne strukturierte Vorbereitung drohen im Ernstfall Ausfälle – und für die Geschäftsführung persönliche Haftung.',
            self::Aufbau => 'Wichtige Bausteine sind vorhanden, aber noch nicht durchgängig NIS2-fest. Mit gezielten Schritten schließen Sie die verbleibenden Lücken.',
            self::Solide => 'Sie sind bei den zentralen NIS2-Anforderungen gut aufgestellt. Jetzt zählt es, den Stand nachweisbar zu dokumentieren und aktuell zu halten.',
        };
    }
}
