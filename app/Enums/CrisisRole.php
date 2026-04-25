<?php

namespace App\Enums;

enum CrisisRole: string
{
    case EmergencyOfficer = 'emergency_officer';
    case ItLead = 'it_lead';
    case DataProtectionOfficer = 'dpo';
    case CommunicationsLead = 'communications_lead';
    case Management = 'management';

    public function label(): string
    {
        return match ($this) {
            self::EmergencyOfficer => 'Notfallbeauftragte/r',
            self::ItLead => 'IT-Verantwortliche/r',
            self::DataProtectionOfficer => 'Datenschutzbeauftragte/r',
            self::CommunicationsLead => 'Kommunikationsverantwortliche/r',
            self::Management => 'Geschäftsführung',
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
