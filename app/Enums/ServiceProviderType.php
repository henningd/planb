<?php

namespace App\Enums;

enum ServiceProviderType: string
{
    // IT / Kommunikation
    case ItMsp = 'it_msp';
    case InternetProvider = 'internet_provider';
    case CloudSaas = 'cloud_saas';
    case CyberInsurance = 'cyber_insurance';
    case ItForensics = 'it_forensics';
    case LegalDataProtection = 'legal_data_protection';

    // Gebäude / Technik
    case BuildingServices = 'building_services';
    case Utility = 'utility';
    case Hvac = 'hvac';
    case Elevator = 'elevator';
    case FireProtection = 'fire_protection';
    case SecurityService = 'security_service';
    case MachineService = 'machine_service';

    // Versorgung / Lieferkette
    case Logistics = 'logistics';
    case Supplier = 'supplier';
    case Cleaning = 'cleaning';
    case Catering = 'catering';

    // Versicherung / Behörden
    case Insurance = 'insurance';
    case Authority = 'authority';
    case DataProtectionAuthority = 'data_protection_authority';
    case BsiReportingOffice = 'bsi_reporting_office';

    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ItMsp => 'IT-Systemhaus / MSP',
            self::InternetProvider => 'Internet / Telekommunikation',
            self::CloudSaas => 'Cloud / SaaS',
            self::CyberInsurance => 'Cyberversicherung',
            self::ItForensics => 'IT-Forensik',
            self::LegalDataProtection => 'Rechtsberatung / Datenschutz',
            self::BuildingServices => 'Gebäudetechnik',
            self::Utility => 'Elektro / Energie',
            self::Hvac => 'Heizung / Klima / Lüftung',
            self::Elevator => 'Aufzugtechnik',
            self::FireProtection => 'Brandschutz / Brandmeldeanlage',
            self::SecurityService => 'Sicherheitsdienst',
            self::MachineService => 'Maschinenservice',
            self::Logistics => 'Logistik / Spedition',
            self::Supplier => 'Lieferant / kritischer Zulieferer',
            self::Cleaning => 'Reinigung / Hygiene',
            self::Catering => 'Catering / Versorgung',
            self::Insurance => 'Versicherung allgemein',
            self::Authority => 'Behörde / Meldestelle',
            self::DataProtectionAuthority => 'Datenschutz-Aufsichtsbehörde',
            self::BsiReportingOffice => 'BSI-Meldestelle',
            self::Other => 'Sonstige',
        };
    }

    public function isAuthority(): bool
    {
        return in_array($this, [
            self::Authority,
            self::DataProtectionAuthority,
            self::BsiReportingOffice,
        ], true);
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
