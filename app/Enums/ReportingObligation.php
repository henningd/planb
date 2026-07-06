<?php

namespace App\Enums;

/**
 * Catalog of reporting obligations relevant for SMBs in Germany.
 * Values are stable string keys; the deadline is expressed in hours
 * from incident awareness.
 */
enum ReportingObligation: string
{
    case DsgvoNotification = 'dsgvo_72h';
    case Nis2EarlyWarning = 'nis2_24h';
    case Nis2InitialReport = 'nis2_72h';
    case CyberInsurance = 'insurance';
    case EmployeeNotification = 'employees';
    case CertLandNotification = 'cert_land';
    case KommunalaufsichtNotification = 'kommunalaufsicht';

    public function label(): string
    {
        return match ($this) {
            self::DsgvoNotification => 'DSGVO-Meldung an Aufsichtsbehörde',
            self::Nis2EarlyWarning => 'NIS2 Frühwarnung',
            self::Nis2InitialReport => 'NIS2 Erstmeldung',
            self::CyberInsurance => 'Cyberversicherung benachrichtigen',
            self::EmployeeNotification => 'Mitarbeiter informieren',
            self::CertLandNotification => 'Meldung an Landes-CERT (empfohlen: unverzüglich)',
            self::KommunalaufsichtNotification => 'Kommunal-/Rechtsaufsicht informieren (empfohlen: zeitnah)',
        };
    }

    /**
     * Deadline in hours from incident awareness. Null = „unverzüglich".
     */
    public function deadlineHours(): ?int
    {
        return match ($this) {
            self::DsgvoNotification => 72,
            self::Nis2EarlyWarning => 24,
            self::Nis2InitialReport => 72,
            self::CyberInsurance => null,
            self::EmployeeNotification => null,
            self::CertLandNotification => null,
            self::KommunalaufsichtNotification => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DsgvoNotification => 'Meldung an die zuständige Datenschutzaufsicht bei Risiko für Betroffene. Frist: 72 Stunden nach Kenntniserlangung.',
            self::Nis2EarlyWarning => 'Frühwarnung an die zuständige Behörde (BSI / CSIRT), wenn NIS2-relevante Systeme betroffen sind.',
            self::Nis2InitialReport => 'Erstmeldung mit detaillierter Lagebeschreibung innerhalb von 72 Stunden.',
            self::CyberInsurance => 'Unverzügliche Meldung an die Cyberversicherung. Versicherungsnummer und Vorfallzeitpunkt bereithalten.',
            self::EmployeeNotification => 'Interne Kommunikation über Lage, Sprachregelung und Verhaltensregeln.',
            self::CertLandNotification => 'Meldung des IT-Sicherheitsvorfalls an das CERT des Bundeslandes (z. B. CERT NRW, BayernCERT). Es gibt keine einheitliche gesetzliche Frist — empfohlen ist eine unverzügliche Meldung. Kontaktdaten des zuständigen Landes-CERT bereithalten.',
            self::KommunalaufsichtNotification => 'Information der zuständigen Kommunal- bzw. Rechtsaufsicht über den Vorfall und die getroffenen Maßnahmen. Empfohlen: zeitnah, sobald ein belastbares Lagebild vorliegt.',
        };
    }

    /**
     * True für Meldewege, die nur für Kommunen und andere öffentliche
     * Einrichtungen relevant sind.
     */
    public function isMunicipal(): bool
    {
        return match ($this) {
            self::CertLandNotification, self::KommunalaufsichtNotification => true,
            default => false,
        };
    }

    /**
     * Obligations that apply for a given incident type. Municipal reporting
     * channels (Landes-CERT, Kommunal-/Rechtsaufsicht) are only included
     * when the company's industry is „Öffentliche Einrichtung".
     *
     * @return array<self>
     */
    public static function applicableFor(string $incidentType, ?Industry $industry = null): array
    {
        $obligations = match ($incidentType) {
            'data_breach' => [self::DsgvoNotification, self::CyberInsurance, self::EmployeeNotification],
            'cyber_attack' => [self::DsgvoNotification, self::Nis2EarlyWarning, self::Nis2InitialReport, self::CyberInsurance, self::EmployeeNotification],
            'outage' => [self::CyberInsurance, self::EmployeeNotification],
            default => [self::DsgvoNotification, self::Nis2EarlyWarning, self::Nis2InitialReport, self::CyberInsurance, self::EmployeeNotification],
        };

        if ($industry !== Industry::OeffentlicheEinrichtung) {
            return $obligations;
        }

        // Landes-CERT nur bei sicherheitsrelevanten Vorfällen — ein reiner
        // Systemausfall ist nicht zwingend ein IT-Sicherheitsvorfall.
        if ($incidentType !== 'outage') {
            $obligations[] = self::CertLandNotification;
        }

        $obligations[] = self::KommunalaufsichtNotification;

        return $obligations;
    }
}
