<?php

namespace App\Enums;

enum ServiceProviderType: string
{
    case ItMsp = 'it_msp';
    case InternetProvider = 'internet_provider';
    case Utility = 'utility';
    case DataProtectionAuthority = 'data_protection_authority';
    case BsiReportingOffice = 'bsi_reporting_office';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ItMsp => 'IT-Systemhaus / MSP',
            self::InternetProvider => 'Internet-Provider',
            self::Utility => 'Strom- / Netzbetreiber',
            self::DataProtectionAuthority => 'Datenschutz-Aufsichtsbehörde',
            self::BsiReportingOffice => 'BSI Meldestelle',
            self::Other => 'Sonstiger Dienstleister / Behörde',
        };
    }

    public function isAuthority(): bool
    {
        return in_array($this, [self::DataProtectionAuthority, self::BsiReportingOffice], true);
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
