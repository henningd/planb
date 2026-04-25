<?php

namespace App\Enums;

enum EmergencyResourceType: string
{
    case EmergencyCash = 'emergency_cash';
    case ReplacementHardware = 'replacement_hardware';
    case OfflineBackup = 'offline_backup';
    case EmergencySim = 'emergency_sim';
    case OfflineDocs = 'offline_docs';
    case PasswordSafe = 'password_safe';
    case GeneratorUps = 'generator_ups';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EmergencyCash => 'Notfallkasse (Bargeld)',
            self::ReplacementHardware => 'Ersatz-Hardware',
            self::OfflineBackup => 'Offline-Backup',
            self::EmergencySim => 'Notfall-SIM / Hotspot',
            self::OfflineDocs => 'Handbücher / Offline-Docs',
            self::PasswordSafe => 'Passwort-Safe (Offline)',
            self::GeneratorUps => 'Generator / USV',
            self::Other => 'Sonstiges',
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
