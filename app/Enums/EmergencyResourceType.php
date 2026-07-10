<?php

namespace App\Enums;

enum EmergencyResourceType: string
{
    case EmergencyCash = 'emergency_cash';
    case ReplacementHardware = 'replacement_hardware';
    case Communication = 'communication';
    case EmergencySim = 'emergency_sim';
    case OfflineDocs = 'offline_docs';
    case PasswordSafe = 'password_safe';
    case KeysAccess = 'keys_access';
    case AlternateWorkplace = 'alternate_workplace';
    case NotebookPool = 'notebook_pool';
    case SparePrinter = 'spare_printer';
    case GeneratorUps = 'generator_ups';
    case LightingPowerbank = 'lighting_powerbank';
    case Evacuation = 'evacuation';
    case SafetyEquipment = 'safety_equipment';
    case Consumables = 'consumables';
    case OfflineBackup = 'offline_backup';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EmergencyCash => 'Notfallkasse / Kreditkarte',
            self::ReplacementHardware => 'Ersatzhardware',
            self::Communication => 'Kommunikationsmittel',
            self::EmergencySim => 'LTE-/5G-Backup / Hotspot',
            self::OfflineDocs => 'Papier-Notfallordner / Offline-Docs',
            self::PasswordSafe => 'Notfallzugänge / Break-Glass',
            self::KeysAccess => 'Schlüssel / Zutritt',
            self::AlternateWorkplace => 'Ausweicharbeitsplatz',
            self::NotebookPool => 'Notfallarbeitsplatz / Notebook-Pool',
            self::SparePrinter => 'Ersatzdrucker / Druckmaterial',
            self::GeneratorUps => 'Strom / USV / Akkus',
            self::LightingPowerbank => 'Beleuchtung / Powerbanks',
            self::Evacuation => 'Evakuierungsmaterial',
            self::SafetyEquipment => 'Sicherheitsausstattung',
            self::Consumables => 'Verbrauchsmaterial',
            self::OfflineBackup => 'Offline-Backup',
            self::Other => 'Sonstige Ressource',
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
