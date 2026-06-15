<?php

namespace App\Support\Prevention;

use App\Enums\PreventiveMeasureCategory;
use App\Enums\PreventiveMeasureInterval;
use App\Enums\SystemType;

/**
 * Kuratierter Vorschlagskatalog für Präventivmaßnahmen je Systemtyp.
 *
 * Statisch und versioniert wie der GuideCatalog: bewährte BCM-Maßnahmen, die
 * sich beim Anlegen eines Systems per Klick übernehmen lassen. Die App ist
 * dadurch vom ersten Tag an mit sinnvollen Vorschlägen gefüllt statt leer.
 *
 * @phpstan-type Suggestion array{title: string, category: PreventiveMeasureCategory, interval: PreventiveMeasureInterval|null, description: string}
 */
class PreventiveMeasureCatalog
{
    /**
     * Allgemeine Maßnahmen, die für jedes System sinnvoll sind.
     *
     * @return array<int, Suggestion>
     */
    public static function common(): array
    {
        return [
            [
                'title' => 'Datensicherung mit Rückspieltest',
                'category' => PreventiveMeasureCategory::Backup,
                'interval' => PreventiveMeasureInterval::Quarterly,
                'description' => 'Backup einrichten und durch testweises Zurückspielen prüfen – ein nie getestetes Backup ist nur eine Hoffnung.',
            ],
            [
                'title' => 'Monitoring & Frühwarnung aktiv',
                'category' => PreventiveMeasureCategory::Monitoring,
                'interval' => PreventiveMeasureInterval::Monthly,
                'description' => 'Überwachung auf Verfügbarkeit, Auslastung und Fehler einrichten und Alarmierung regelmäßig prüfen.',
            ],
            [
                'title' => 'Wiederanlauf-Plan üben',
                'category' => PreventiveMeasureCategory::TestExercise,
                'interval' => PreventiveMeasureInterval::Yearly,
                'description' => 'Wiederherstellung des Systems mindestens jährlich durchspielen und Erkenntnisse dokumentieren.',
            ],
        ];
    }

    /**
     * Zusätzliche, typspezifische Maßnahmen.
     *
     * @return array<int, Suggestion>
     */
    public static function forType(SystemType $type): array
    {
        return match ($type) {
            SystemType::Server => [
                [
                    'title' => 'Patch- & Update-Management',
                    'category' => PreventiveMeasureCategory::PatchManagement,
                    'interval' => PreventiveMeasureInterval::Monthly,
                    'description' => 'Sicherheitsupdates des Betriebssystems und der Dienste regelmäßig einspielen.',
                ],
                [
                    'title' => 'USV-Wartung & Test',
                    'category' => PreventiveMeasureCategory::Physical,
                    'interval' => PreventiveMeasureInterval::Yearly,
                    'description' => 'Unterbrechungsfreie Stromversorgung warten und Notstromfall testen.',
                ],
                [
                    'title' => 'Redundanz / Failover prüfen',
                    'category' => PreventiveMeasureCategory::Redundancy,
                    'interval' => PreventiveMeasureInterval::Biannually,
                    'description' => 'Ausfallsicherheit (Cluster, Spiegelung, Ersatzhardware) auf Funktion prüfen.',
                ],
            ],
            SystemType::Anwendung => [
                [
                    'title' => 'Schwachstellen-Scan',
                    'category' => PreventiveMeasureCategory::PatchManagement,
                    'interval' => PreventiveMeasureInterval::Monthly,
                    'description' => 'Anwendung und Abhängigkeiten auf bekannte Sicherheitslücken prüfen und patchen.',
                ],
                [
                    'title' => 'Berechtigungs-Review',
                    'category' => PreventiveMeasureCategory::AccessManagement,
                    'interval' => PreventiveMeasureInterval::Biannually,
                    'description' => 'Zugriffsrechte überprüfen und nicht mehr benötigte Konten entziehen (Least Privilege).',
                ],
            ],
            SystemType::Kommunikation => [
                [
                    'title' => 'Ausweichkommunikation bereithalten',
                    'category' => PreventiveMeasureCategory::Redundancy,
                    'interval' => PreventiveMeasureInterval::Biannually,
                    'description' => 'Alternativen Kommunikationsweg (z. B. Mobilfunk, externe Plattform) vorbereiten und testen.',
                ],
                [
                    'title' => 'Berechtigungs-Review',
                    'category' => PreventiveMeasureCategory::AccessManagement,
                    'interval' => PreventiveMeasureInterval::Biannually,
                    'description' => 'Zugänge zu Postfächern und Kommunikationsdiensten regelmäßig prüfen.',
                ],
            ],
            SystemType::Infrastruktur => [
                [
                    'title' => 'Klima- & Brandschutz-Check',
                    'category' => PreventiveMeasureCategory::Physical,
                    'interval' => PreventiveMeasureInterval::Yearly,
                    'description' => 'Kühlung, Brandfrüherkennung und Löschtechnik im Technikraum prüfen.',
                ],
                [
                    'title' => 'Wartungsvertrag & SLA prüfen',
                    'category' => PreventiveMeasureCategory::Supplier,
                    'interval' => PreventiveMeasureInterval::Yearly,
                    'description' => 'Reaktionszeiten und Geltung der Wartungs-/SLA-Verträge mit Dienstleistern überprüfen.',
                ],
            ],
        };
    }

    /**
     * Vollständige Vorschlagsliste für ein System eines bestimmten Typs.
     *
     * @return array<int, Suggestion>
     */
    public static function forSystemType(?SystemType $type): array
    {
        $suggestions = self::common();

        if ($type !== null) {
            $suggestions = [...$suggestions, ...self::forType($type)];
        }

        return $suggestions;
    }
}
