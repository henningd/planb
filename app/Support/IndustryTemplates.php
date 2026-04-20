<?php

namespace App\Support;

use App\Enums\Industry;
use App\Enums\SystemCategory;

/**
 * Pre-built system templates per industry.
 * Priorities are referenced by name and resolved at import time
 * against the company's existing SystemPriority records.
 */
class IndustryTemplates
{
    /**
     * @var array<string, array{label: string, hint: string, systems: array<int, array{name: string, description: string, category: string, priority: ?string, rto_minutes: ?int, rpo_minutes: ?int}>}>
     */
    public const TEMPLATES = [
        Industry::Handwerk->value => [
            'label' => 'Handwerk',
            'hint' => 'Typische Systeme eines mittelständischen Handwerksbetriebs (z. B. Dachdecker, Tischler, Elektro).',
            'systems' => [
                ['name' => 'Stromversorgung / USV', 'description' => 'Netzstrom und Notstromversorgung für Büro und Werkstatt.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 15, 'rpo_minutes' => null],
                ['name' => 'Internetanschluss (Büro)', 'description' => 'Primäre Internetverbindung, Voraussetzung für VoIP, E-Mail und Cloud.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => null],
                ['name' => 'Telefonanlage / Mobilfunk', 'description' => 'Festnetz oder VoIP sowie Firmenhandys für die Baustellenkommunikation.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Büro-Server / Zentralrechner', 'description' => 'Zentraler Rechner mit Dateifreigaben und Branchensoftware.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],

                ['name' => 'Handwerkersoftware', 'description' => 'Aufträge, Kalkulation, Aufmaß (z. B. Streit V.1, pds, simsdata).', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Rechnungs- und Angebotserstellung', 'description' => 'Angebote, Rechnungen, Mahnwesen – oft Teil der Handwerkersoftware.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Baustellen- / Dispositions-App', 'description' => 'Tourenplanung, Bautagebuch, Baustellenfotos.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Buchhaltung', 'description' => 'Finanzbuchhaltung, Lohnabrechnung (z. B. DATEV, Lexware).', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Material- und Lagerverwaltung', 'description' => 'Bestand, Materialreservierung, Bestellwesen.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Zeiterfassung', 'description' => 'Mobile Erfassung von Arbeitszeit auf der Baustelle.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Online-Präsenz', 'description' => 'Firmenwebsite, Google-Business-Eintrag, Anfragenformular.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 4320],

                ['name' => 'E-Mail', 'description' => 'Outlook / Exchange / Microsoft 365.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 240],
                ['name' => 'Cloud-Speicher', 'description' => 'OneDrive, Dropbox o. Ä. – Pläne, Fotos, Dokumente.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 480, 'rpo_minutes' => 60],
                ['name' => 'Office-Paket', 'description' => 'Word/Excel für Angebote, Listen, Schriftverkehr.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Kalender / Terminplanung', 'description' => 'Termine, Urlaube, Baustellen-Kalender.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'GPS-Ortung / Fuhrpark-Telematik', 'description' => 'Fahrzeugortung, Fahrtenbuch, Kraftstoffkarten.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 4320, 'rpo_minutes' => 1440],
            ],
        ],

        Industry::Handel->value => [
            'label' => 'Handel',
            'hint' => 'Stationärer Einzelhandel und/oder Online-Shop mit Kassensystem und Warenwirtschaft.',
            'systems' => [
                ['name' => 'Stromversorgung / USV', 'description' => 'Netzstrom plus Absicherung für Kassen und Kühlung.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 15, 'rpo_minutes' => null],
                ['name' => 'Internetanschluss', 'description' => 'Primäre Verbindung für Shop, Zahlungsterminal und Warenwirtschaft.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Netzwerk / WLAN', 'description' => 'Internes Netzwerk für Kassen, Backoffice und mobile Geräte.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Telefonanlage', 'description' => 'Kundentelefon und interne Kommunikation.', 'category' => 'basisbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => null],

                ['name' => 'Kassensystem (POS)', 'description' => 'Hauptkassen am Point of Sale inkl. Bondrucker.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => 15],
                ['name' => 'Zahlungsabwicklung (Kartenterminal)', 'description' => 'EC-/Kreditkarten-Terminal, Providerverbindung.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Warenwirtschaft (ERP)', 'description' => 'Bestand, Einkauf, Lieferanten, Artikelstamm.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Online-Shop', 'description' => 'Webshop, Zahlungsanbieter, Shop-Backend.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Lagerverwaltung', 'description' => 'Bestandsführung, Kommissionierung.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Buchhaltung', 'description' => 'Finanzbuchhaltung inkl. Kassenbuch-Überleitung.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'CRM / Kundenverwaltung', 'description' => 'Bestandskunden, Newsletter, Kundenkarten.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],

                ['name' => 'E-Mail', 'description' => 'Geschäfts-E-Mail-Postfächer.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 240],
                ['name' => 'Cloud-Speicher', 'description' => 'Gemeinsame Dokumente, Belege, Produktbilder.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 480, 'rpo_minutes' => 60],
                ['name' => 'Alarmanlage / Videoüberwachung', 'description' => 'Objektsicherung, Kameras, Aufzeichnung.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
            ],
        ],

        Industry::Dienstleistung->value => [
            'label' => 'Dienstleistung',
            'hint' => 'Kanzleien, Agenturen, Beratungen – dokumentenlastig, kommunikationskritisch.',
            'systems' => [
                ['name' => 'Stromversorgung / USV', 'description' => 'Netzstrom und USV für Server, Arbeitsplätze.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 15, 'rpo_minutes' => null],
                ['name' => 'Internetanschluss', 'description' => 'Primäre Verbindung für VoIP, Cloud und E-Mail.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Telefonanlage / VoIP', 'description' => 'Erreichbarkeit für Mandanten/Kunden.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Datei-/Zentralserver', 'description' => 'Dateiablage, Benutzerverwaltung, interner Fileshare.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],

                ['name' => 'E-Mail (Exchange / M365)', 'description' => 'Geschäftskritisch für Mandantenkommunikation.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => 60],
                ['name' => 'Dokumentenmanagement (DMS)', 'description' => 'Akten, Verträge, Scans, Versionshistorie.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Branchensoftware', 'description' => 'Z. B. DATEV, Advoware, RA-MICRO, Agenturtools.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Zeit- / Leistungserfassung', 'description' => 'Abrechenbare Stunden, Projektzuordnung.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Buchhaltung', 'description' => 'Finanzbuchhaltung und Lohn.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'CRM', 'description' => 'Mandantendaten, Akquise, Angebote.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Online-Präsenz', 'description' => 'Website, Blog, Anfrage-Formulare.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 4320],

                ['name' => 'Videokonferenzen', 'description' => 'Teams, Zoom, Google Meet.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 1440],
                ['name' => 'Cloud-Speicher', 'description' => 'OneDrive, SharePoint, Dropbox, Nextcloud.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Office-Paket', 'description' => 'Word, Excel, PowerPoint für alltägliche Arbeit.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Kalender / Termine', 'description' => 'Gemeinsame Kalender, Raumbuchungen.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 1440],
            ],
        ],

        Industry::Produktion->value => [
            'label' => 'Produktion',
            'hint' => 'Fertigungsbetriebe mit ERP, Produktionssteuerung und Lagerlogistik.',
            'systems' => [
                ['name' => 'Stromversorgung / USV', 'description' => 'Absicherung für Serverräume und Produktion.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 15, 'rpo_minutes' => null],
                ['name' => 'Internetanschluss', 'description' => 'Primäre Verbindung mit Failover.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => null],
                ['name' => 'Netzwerk (LAN/WLAN)', 'description' => 'Internes Netzwerk, Produktions-VLAN.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Zentrale Server', 'description' => 'Datenbankserver, Domänencontroller.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Telefonanlage', 'description' => 'Erreichbarkeit für Kunden und Lieferanten.', 'category' => 'basisbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => null],

                ['name' => 'ERP-System', 'description' => 'Z. B. SAP Business One, Sage, Microsoft Dynamics.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Produktionssteuerung (MES)', 'description' => 'Aufträge, Maschinenanbindung, Produktionsdaten.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'CAD / CAM', 'description' => 'Konstruktion, Werkzeugbahnen, NC-Programme.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Lagerverwaltung (WMS)', 'description' => 'Ein- und Auslagerung, Bestandsführung.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Qualitätsmanagement', 'description' => 'QS-Daten, Prüfprotokolle, Reklamationen.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Buchhaltung', 'description' => 'Finanzbuchhaltung, Lohn, Controlling.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'CRM', 'description' => 'Kundenstammdaten, Angebote, Vertrieb.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],

                ['name' => 'E-Mail', 'description' => 'Geschäfts-E-Mail, Lieferantenkommunikation.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 240],
                ['name' => 'Cloud-Speicher', 'description' => 'Zeichnungen, Zertifikate, geteilte Dokumente.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 480, 'rpo_minutes' => 60],
                ['name' => 'Office-Paket', 'description' => 'Word, Excel, PowerPoint.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Zutrittssystem / Alarmanlage', 'description' => 'Zugangskontrolle, Objektsicherung.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
            ],
        ],
    ];

    /**
     * Typical contact roles for each industry – seeded as placeholder
     * contacts so the user only has to add names and phone numbers.
     *
     * @var array<string, array<int, array{role: string, type: string}>>
     */
    public const CONTACT_ROLES = [
        Industry::Handwerk->value => [
            ['role' => 'Geschäftsführung / Inhaber', 'type' => 'intern'],
            ['role' => 'Werkstatt- / Betriebsleitung', 'type' => 'intern'],
            ['role' => 'Baustellenleitung (Rufbereitschaft)', 'type' => 'intern'],
            ['role' => 'Büro / Auftragsabwicklung', 'type' => 'intern'],
            ['role' => 'IT-Dienstleister', 'type' => 'extern'],
            ['role' => 'Steuerberater / Buchhaltung', 'type' => 'extern'],
            ['role' => 'Datenschutzbeauftragter', 'type' => 'extern'],
        ],
        Industry::Handel->value => [
            ['role' => 'Geschäftsführung / Inhaber', 'type' => 'intern'],
            ['role' => 'Filial- / Marktleitung', 'type' => 'intern'],
            ['role' => 'Verantwortlicher Kassensystem', 'type' => 'intern'],
            ['role' => 'IT-Beauftragter', 'type' => 'intern'],
            ['role' => 'IT-Dienstleister', 'type' => 'extern'],
            ['role' => 'Zahlungsdienstleister / Kartenterminal', 'type' => 'extern'],
            ['role' => 'Shop-/Warenwirtschafts-Support', 'type' => 'extern'],
            ['role' => 'Datenschutzbeauftragter', 'type' => 'extern'],
        ],
        Industry::Dienstleistung->value => [
            ['role' => 'Geschäftsführung / Partner', 'type' => 'intern'],
            ['role' => 'IT-Beauftragter', 'type' => 'intern'],
            ['role' => 'Sekretariat / Empfang', 'type' => 'intern'],
            ['role' => 'IT-Dienstleister', 'type' => 'extern'],
            ['role' => 'Datenschutzbeauftragter', 'type' => 'extern'],
            ['role' => 'Branchensoftware-Support', 'type' => 'extern'],
            ['role' => 'Steuerberater / Buchhaltung', 'type' => 'extern'],
        ],
        Industry::Produktion->value => [
            ['role' => 'Geschäftsführung', 'type' => 'intern'],
            ['role' => 'Werk- / Produktionsleitung', 'type' => 'intern'],
            ['role' => 'IT-Leitung', 'type' => 'intern'],
            ['role' => 'Sicherheitsbeauftragter', 'type' => 'intern'],
            ['role' => 'Instandhaltung / Betriebstechnik', 'type' => 'intern'],
            ['role' => 'IT-Dienstleister', 'type' => 'extern'],
            ['role' => 'ERP/MES-Support', 'type' => 'extern'],
            ['role' => 'Datenschutzbeauftragter', 'type' => 'extern'],
        ],
    ];

    /**
     * @return array<string, array{label: string, hint: string, count: int}>
     */
    public static function catalog(): array
    {
        $catalog = [];
        foreach (self::TEMPLATES as $key => $tpl) {
            $catalog[$key] = [
                'label' => $tpl['label'],
                'hint' => $tpl['hint'],
                'count' => count($tpl['systems']),
            ];
        }

        return $catalog;
    }

    /**
     * @return array<int, array{role: string, type: string}>
     */
    public static function contactRolesFor(string $industryValue): array
    {
        return self::CONTACT_ROLES[$industryValue] ?? [];
    }

    /**
     * @return array<string, array{label: string, count: int}>
     */
    public static function contactCatalog(): array
    {
        $catalog = [];
        foreach (self::CONTACT_ROLES as $key => $roles) {
            $catalog[$key] = [
                'label' => self::TEMPLATES[$key]['label'] ?? $key,
                'count' => count($roles),
            ];
        }

        return $catalog;
    }

    /**
     * @return array<int, array{name: string, description: string, category: string, priority: ?string, rto_minutes: ?int, rpo_minutes: ?int}>|null
     */
    public static function systemsFor(string $industryValue): ?array
    {
        return self::TEMPLATES[$industryValue]['systems'] ?? null;
    }

    public static function has(string $industryValue): bool
    {
        return isset(self::TEMPLATES[$industryValue]);
    }

    /**
     * Pick a reasonable default template key for a given company industry.
     */
    public static function defaultFor(?Industry $industry): ?string
    {
        if ($industry === null || $industry === Industry::Sonstiges) {
            return Industry::Handwerk->value;
        }

        return self::has($industry->value) ? $industry->value : null;
    }

    public static function categoryIsValid(string $category): bool
    {
        return in_array($category, array_column(SystemCategory::cases(), 'value'), true);
    }
}
