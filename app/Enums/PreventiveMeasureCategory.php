<?php

namespace App\Enums;

enum PreventiveMeasureCategory: string
{
    case Backup = 'backup';
    case Redundancy = 'redundancy';
    case PatchManagement = 'patch_management';
    case Monitoring = 'monitoring';
    case AccessManagement = 'access_management';
    case Maintenance = 'maintenance';
    case Supplier = 'supplier';
    case Training = 'training';
    case Physical = 'physical';
    case TestExercise = 'test_exercise';

    public function label(): string
    {
        return match ($this) {
            self::Backup => 'Datensicherung & Wiederherstellung',
            self::Redundancy => 'Redundanz & Ausfallsicherheit',
            self::PatchManagement => 'Patch- & Schwachstellenmanagement',
            self::Monitoring => 'Monitoring & Frühwarnung',
            self::AccessManagement => 'Zugriffs- & Berechtigungsmanagement',
            self::Maintenance => 'Wartung & Instandhaltung',
            self::Supplier => 'Lieferanten & SLA',
            self::Training => 'Schulung & Awareness',
            self::Physical => 'Physische Sicherheit',
            self::TestExercise => 'Tests & Übungen',
        };
    }

    /**
     * Heroicon-Name für Flux-Badges/Listen.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Backup => 'circle-stack',
            self::Redundancy => 'server-stack',
            self::PatchManagement => 'shield-check',
            self::Monitoring => 'signal',
            self::AccessManagement => 'key',
            self::Maintenance => 'wrench-screwdriver',
            self::Supplier => 'truck',
            self::Training => 'academic-cap',
            self::Physical => 'building-office',
            self::TestExercise => 'beaker',
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
