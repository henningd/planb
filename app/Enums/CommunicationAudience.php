<?php

namespace App\Enums;

enum CommunicationAudience: string
{
    case Employees = 'employees';
    case Customers = 'customers';
    case Press = 'press';
    case Authorities = 'authorities';
    case ServiceProviders = 'service_providers';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Employees => 'Mitarbeiter',
            self::Customers => 'Kunden',
            self::Press => 'Presse',
            self::Authorities => 'Behörden',
            self::ServiceProviders => 'Dienstleister',
            self::Other => 'Sonstige',
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
