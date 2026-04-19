<?php

namespace Database\Seeders;

use App\Models\GlobalScenario;
use Illuminate\Database\Seeder;

class GlobalScenariosSeeder extends Seeder
{
    /**
     * Seeds the six default scenarios into the global library. Idempotent.
     */
    public function run(): void
    {
        foreach (self::DEFAULTS as $index => $data) {
            $scenario = GlobalScenario::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'trigger' => $data['trigger'],
                    'is_active' => true,
                    'sort' => $index + 1,
                ],
            );

            if ($scenario->steps()->exists()) {
                continue;
            }

            foreach ($data['steps'] as $stepIndex => $step) {
                $scenario->steps()->create([
                    ...$step,
                    'sort' => $stepIndex + 1,
                ]);
            }
        }
    }

    /**
     * @var array<int, array{name: string, description: string, trigger: string, steps: array<int, array{title: string, description: string, responsible: string}>}>
     */
    private const DEFAULTS = [
        [
            'name' => 'Ransomware / Cyberangriff',
            'description' => 'Daten sind verschlüsselt, Lösegeldforderung oder verdächtige Aktivität auf mehreren Systemen.',
            'trigger' => 'Meldung durch Mitarbeiter, Alarmierung aus Monitoring, sichtbare Lösegeldforderung.',
            'steps' => [
                ['title' => 'Betroffene Geräte vom Netz trennen', 'description' => 'Netzwerkkabel ziehen bzw. WLAN deaktivieren. Geräte nicht ausschalten, Beweise erhalten.', 'responsible' => 'Erster reagierender Mitarbeiter'],
                ['title' => 'Geschäftsführung informieren', 'description' => 'Kurze, sachliche Info über Zeitpunkt, betroffene Systeme und bekannte Details.', 'responsible' => 'Hauptansprechpartner'],
                ['title' => 'IT-Dienstleister kontaktieren', 'description' => 'Notfall-Hotline anrufen. Vertragsnummer und Vorfallbeschreibung bereithalten.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Cyberversicherung benachrichtigen', 'description' => 'Policennummer, erste Einschätzung, Zeitstempel nennen.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'DSGVO-Uhr prüfen', 'description' => 'Wurden personenbezogene Daten betroffen? Dann läuft die 72-Stunden-Meldefrist.', 'responsible' => 'Datenschutzbeauftragter'],
                ['title' => 'Kommunikation an Mitarbeiter', 'description' => 'Was ist passiert, was dürfen Mitarbeiter tun, was nicht. Auch per SMS/Telefon, falls E-Mail aus.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Kundeninformation vorbereiten', 'description' => 'Sprachregelung abstimmen, bevor Kunden eigene Fragen stellen.', 'responsible' => 'Geschäftsführung'],
            ],
        ],
        [
            'name' => 'Serverausfall',
            'description' => 'Zentraler Server nicht erreichbar, Dienste wie Datei-Freigaben oder Warenwirtschaft stehen still.',
            'trigger' => 'Mitarbeiter melden Ausfall, Monitoring schlägt an, Dienste nicht erreichbar.',
            'steps' => [
                ['title' => 'Betroffene Systeme identifizieren', 'description' => 'Welche Dienste sind betroffen? Wer kann aktuell nicht arbeiten?', 'responsible' => 'IT-Ansprechpartner'],
                ['title' => 'IT-Dienstleister kontaktieren', 'description' => 'Notfall-Hotline, Ausfallbeschreibung, Zeitstempel.', 'responsible' => 'Hauptansprechpartner'],
                ['title' => 'Mitarbeiter informieren', 'description' => 'Was läuft nicht, was läuft weiter, Erreichbarkeit klarstellen.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Priorität festlegen', 'description' => 'Reihenfolge der Wiederherstellung anhand der System-Prioritäten.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Wiederanlauf dokumentieren', 'description' => 'Zeitpunkte, getroffene Maßnahmen, Verantwortliche protokollieren.', 'responsible' => 'IT-Ansprechpartner'],
            ],
        ],
        [
            'name' => 'Stromausfall',
            'description' => 'Vollständiger Stromausfall am Standort oder in der Region.',
            'trigger' => 'Keine Stromversorgung, USV piept, Beleuchtung aus.',
            'steps' => [
                ['title' => 'Dauer abschätzen', 'description' => 'Netzbetreiber anrufen oder öffentliche Kanäle prüfen.', 'responsible' => 'Hauptansprechpartner'],
                ['title' => 'USV-Laufzeit prüfen', 'description' => 'Wie lange hält die USV? Welche Systeme sollen zuerst sauber heruntergefahren werden?', 'responsible' => 'IT-Ansprechpartner'],
                ['title' => 'Mitarbeiter-Entscheidung', 'description' => 'Arbeit vor Ort, im Homeoffice oder Pause? Kommunikation per SMS/Telefon.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Kühlung prüfen', 'description' => 'Kühlräume, Serverräume: ab wann wird es kritisch?', 'responsible' => 'Facility Management'],
                ['title' => 'Kontrollierter Neustart', 'description' => 'Nach Rückkehr des Stroms in definierter Reihenfolge hochfahren.', 'responsible' => 'IT-Dienstleister'],
            ],
        ],
        [
            'name' => 'Internet- oder Telefonausfall',
            'description' => 'Kein Internet, keine Telefonie über die Anlage, VoIP tot.',
            'trigger' => 'Keine Internetverbindung, Telefonate nicht mehr möglich.',
            'steps' => [
                ['title' => 'Provider-Status prüfen', 'description' => 'Störungsmelder, Provider-Hotline.', 'responsible' => 'IT-Ansprechpartner'],
                ['title' => 'Mobile Hotspots aktivieren', 'description' => 'Handy-Tethering als Notbetrieb für kritische Arbeitsplätze.', 'responsible' => 'IT-Ansprechpartner'],
                ['title' => 'Alternative Rufnummer bekanntmachen', 'description' => 'Mobilnummer für Kunden-Hotline kommunizieren.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Mitarbeiter informieren', 'description' => 'Verfügbare Kanäle klarstellen (SMS, Messenger, Mobilnummern).', 'responsible' => 'Geschäftsführung'],
            ],
        ],
        [
            'name' => 'Datenpanne / Datenleck',
            'description' => 'Personenbezogene Daten sind abgeflossen oder unbefugten Zugriff gewährt worden.',
            'trigger' => 'Meldung durch Mitarbeiter/Externe, ungewöhnlicher Datenversand, verdächtiger Login.',
            'steps' => [
                ['title' => 'Vorfall dokumentieren', 'description' => 'Was genau ist bekannt? Welche Daten, ab wann, durch wen?', 'responsible' => 'Datenschutzbeauftragter'],
                ['title' => 'Geschäftsführung informieren', 'description' => 'Fakten, noch keine Spekulation.', 'responsible' => 'Hauptansprechpartner'],
                ['title' => 'DSGVO-Meldung prüfen', 'description' => '72-Stunden-Frist startet mit Kenntnis. Meldepflicht an Aufsichtsbehörde abwägen.', 'responsible' => 'Datenschutzbeauftragter'],
                ['title' => 'Betroffene informieren (wenn nötig)', 'description' => 'Klare, verständliche Information ohne Fachjargon.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Ursachenanalyse einleiten', 'description' => 'Gemeinsam mit IT-Dienstleister Ursache und Ausmaß klären.', 'responsible' => 'IT-Dienstleister'],
            ],
        ],
        [
            'name' => 'Ausfall wichtiger Dienstleister',
            'description' => 'Zahlungsanbieter, Hosting, SaaS-Dienst oder IT-Dienstleister nicht erreichbar.',
            'trigger' => 'Kein Zugriff auf externen Dienst, Antwortzeiten überschritten.',
            'steps' => [
                ['title' => 'Ausfall bestätigen', 'description' => 'Statusseiten prüfen, Kollegen fragen – ist wirklich der Dienstleister down?', 'responsible' => 'IT-Ansprechpartner'],
                ['title' => 'Ersatz-Workflow aktivieren', 'description' => 'Notfall-Prozess: Bestellungen manuell, Zahlungen über Alternativanbieter, etc.', 'responsible' => 'Fachbereichsleitung'],
                ['title' => 'Kommunikation an Mitarbeiter', 'description' => 'Was geht, was geht nicht, wie wird ersatzweise gearbeitet.', 'responsible' => 'Geschäftsführung'],
                ['title' => 'Kunden informieren (bei Bedarf)', 'description' => 'Transparent und sachlich, keine Schuldzuweisungen.', 'responsible' => 'Geschäftsführung'],
            ],
        ],
    ];
}
