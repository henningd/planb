<?php

namespace App\Enums;

use App\Models\AuthorityContact;

/**
 * Art einer Behörde, Meldestelle oder externen Einrichtung, die im Ernst-/
 * Meldefall zu kontaktieren ist. Branchenübergreifend gehalten; die konkreten
 * Stellen pflegt jeder Mandant selbst (siehe {@see AuthorityContact}).
 */
enum AuthorityContactType: string
{
    // Allgemein / branchenübergreifend
    case DataProtection = 'data_protection';
    case Bsi = 'bsi';
    case Police = 'police';
    case FireRescue = 'fire_rescue';
    case DispatchCenter = 'dispatch_center';
    case Legal = 'legal';
    case Insurance = 'insurance';

    // Pflege / Gesundheit
    case CareSupervision = 'care_supervision';
    case HealthOffice = 'health_office';
    case MedicalService = 'medical_service';
    case Pharmacy = 'pharmacy';
    case FoodSafety = 'food_safety';

    // Industrie / Produktion
    case EmployersLiability = 'employers_liability';
    case OccupationalSafety = 'occupational_safety';
    case Environment = 'environment';
    case Water = 'water';
    case TradeSupervision = 'trade_supervision';
    case Chamber = 'chamber';

    // Kommune / öffentliche Einrichtung
    case DisasterControl = 'disaster_control';
    case MunicipalIt = 'municipal_it';
    case SupervisoryAuthority = 'supervisory_authority';
    case StateAuthority = 'state_authority';

    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DataProtection => 'Datenschutzaufsicht',
            self::Bsi => 'BSI-Meldestelle',
            self::Police => 'Polizei / Cybercrime (ZAC)',
            self::FireRescue => 'Feuerwehr / Rettungsdienst',
            self::DispatchCenter => 'Rettungs-/Kreisleitstelle',
            self::Legal => 'Rechtsberatung / Datenschutz',
            self::Insurance => 'Versicherung',
            self::CareSupervision => 'Heimaufsicht',
            self::HealthOffice => 'Gesundheitsamt',
            self::MedicalService => 'Medizinischer Dienst / Qualitätsprüfung',
            self::Pharmacy => 'Apothekenkontakt',
            self::FoodSafety => 'Lebensmittelüberwachung',
            self::EmployersLiability => 'Berufsgenossenschaft',
            self::OccupationalSafety => 'Arbeitsschutzbehörde',
            self::Environment => 'Umweltbehörde',
            self::Water => 'Wasserbehörde',
            self::TradeSupervision => 'Gewerbeaufsicht',
            self::Chamber => 'IHK / Kammer',
            self::DisasterControl => 'Katastrophenschutz',
            self::MunicipalIt => 'CERT / kommunaler IT-Dienstleister',
            self::SupervisoryAuthority => 'Aufsichtsbehörde',
            self::StateAuthority => 'Landesbehörde',
            self::Other => 'Sonstige / branchenspezifisch',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DataProtection, self::Bsi, self::MunicipalIt => 'indigo',
            self::Police, self::FireRescue, self::DispatchCenter, self::DisasterControl => 'red',
            self::Legal, self::Insurance => 'amber',
            self::CareSupervision, self::HealthOffice, self::MedicalService, self::Pharmacy, self::FoodSafety => 'emerald',
            self::EmployersLiability, self::OccupationalSafety, self::Environment, self::Water, self::TradeSupervision, self::Chamber => 'sky',
            self::SupervisoryAuthority, self::StateAuthority => 'zinc',
            self::Other => 'zinc',
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
