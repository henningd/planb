<?php

namespace App\Enums;

enum ComplianceCategory: string
{
    case Organisation = 'organisation';
    case Systeme = 'systeme';
    case Tests = 'tests';
    case Dokumentation = 'dokumentation';

    public function label(): string
    {
        return match ($this) {
            self::Organisation => 'Organisation',
            self::Systeme => 'Systeme & Abhängigkeiten',
            self::Tests => 'Tests & Übungen',
            self::Dokumentation => 'Dokumentation & Vorlagen',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Organisation => 'Pflichtrollen, Vertretungen und Erreichbarkeit der Verantwortlichen.',
            self::Systeme => 'Erfassung, Klassifizierung und Verknüpfung kritischer Systeme.',
            self::Tests => 'Geplante und durchgeführte Tests, Übungen und Wiederanlauftests.',
            self::Dokumentation => 'Notfallhandbuch, Kommunikationsvorlagen und Versicherungsnachweise.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Organisation => 'sky',
            self::Systeme => 'violet',
            self::Tests => 'amber',
            self::Dokumentation => 'emerald',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Organisation => 'users',
            self::Systeme => 'server-stack',
            self::Tests => 'beaker',
            self::Dokumentation => 'document-text',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::Organisation => '#0ea5e9',
            self::Systeme => '#8b5cf6',
            self::Tests => '#f59e0b',
            self::Dokumentation => '#10b981',
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Organisation,
            self::Systeme,
            self::Tests,
            self::Dokumentation,
        ];
    }
}
