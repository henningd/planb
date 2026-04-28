<?php

namespace App\Enums;

enum OnboardingStep: string
{
    case CompanyProfile = 'company_profile';
    case IndustryTemplate = 'industry_template';
    case Locations = 'locations';
    case CrisisRoles = 'crisis_roles';
    case Employees = 'employees';
    case Systems = 'systems';
    case ServiceProviders = 'service_providers';
    case EmergencyResources = 'emergency_resources';
    case HandbookRelease = 'handbook_release';

    public function label(): string
    {
        return match ($this) {
            self::CompanyProfile => 'Firmenprofil',
            self::IndustryTemplate => 'Branchen-Template anwenden',
            self::Locations => 'Standorte erfassen',
            self::CrisisRoles => 'Pflichtrollen besetzen',
            self::Employees => 'Mitarbeiter erfassen',
            self::Systems => 'Systeme klassifizieren',
            self::ServiceProviders => 'Dienstleister hinterlegen',
            self::EmergencyResources => 'Sofortmittel pflegen',
            self::HandbookRelease => 'Erste Handbuch-Version freigeben',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CompanyProfile => 'Name, Branche, Rechtsform, Mitarbeiterzahl. Grundlage für alle Vorlagen und Reports.',
            self::IndustryTemplate => 'Optional. Übernehmen Sie typische Systeme, Rollen und Szenarien für Ihre Branche – das spart 80 % der Tipparbeit.',
            self::Locations => 'Mindestens ein Standort, idealerweise mit Hauptsitz-Markierung. Wird für Wiederanlauf-Pläne und Aushänge gebraucht.',
            self::CrisisRoles => 'Notfallbeauftragte/r, IT-Leitung, Datenschutz, Kommunikation, Geschäftsführung — jede Rolle mit einer Hauptperson besetzt.',
            self::Employees => 'Mindestens drei Mitarbeiter mit Kontaktdaten — sonst bleiben Telefonliste und RACI-Zuordnungen leer.',
            self::Systems => 'Mindestens drei Geschäfts-/IT-Systeme erfassen und klassifizieren (Notfall-Level zugeordnet).',
            self::ServiceProviders => 'Mindestens ein externer IT-Dienstleister mit Hotline und SLA-Zeitfenster.',
            self::EmergencyResources => 'Mindestens eine Notfall-Ressource (Notebook-Pool, USV, Schlüssel, Bargeld …).',
            self::HandbookRelease => 'Erste freigegebene Handbuch-Version erzeugen. Damit beginnt die offizielle Versionshistorie.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CompanyProfile => 'building-office-2',
            self::IndustryTemplate => 'rectangle-stack',
            self::Locations => 'map-pin',
            self::CrisisRoles => 'identification',
            self::Employees => 'user-group',
            self::Systems => 'server-stack',
            self::ServiceProviders => 'wrench-screwdriver',
            self::EmergencyResources => 'briefcase',
            self::HandbookRelease => 'document-check',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::CompanyProfile => 'company.edit',
            self::IndustryTemplate => 'company.edit',
            self::Locations => 'locations.index',
            self::CrisisRoles => 'roles.index',
            self::Employees => 'employees.index',
            self::Systems => 'systems.index',
            self::ServiceProviders => 'service-providers.index',
            self::EmergencyResources => 'emergency-resources.index',
            self::HandbookRelease => 'handbook-versions.index',
        };
    }

    public function isOptional(): bool
    {
        return $this === self::IndustryTemplate;
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::CompanyProfile,
            self::IndustryTemplate,
            self::Locations,
            self::Employees,
            self::CrisisRoles,
            self::ServiceProviders,
            self::Systems,
            self::EmergencyResources,
            self::HandbookRelease,
        ];
    }
}
