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
     * @var array<string, array{label: string, hint: string, systems: array<int, array{name: string, description: string, category: string, priority: ?string, rto_minutes: ?int, rpo_minutes: ?int}>, scenarios?: array<int, array{name: string, description: string, trigger: string, steps: array<int, array{title: string, description: string, responsible: string}>}>}>
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

        Industry::OeffentlicheEinrichtung->value => [
            'label' => 'Öffentliche Einrichtung',
            'hint' => 'Kommunen, Behörden, Schulen, Eigenbetriebe – Fachverfahren, Bürgerdienste (OZG), E-Akte. NIS2-/KRITIS-relevant.',
            'systems' => [
                ['name' => 'Stromversorgung / USV', 'description' => 'Netzstrom und Notstromversorgung für Rechenzentrum und Arbeitsplätze.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 15, 'rpo_minutes' => null],
                ['name' => 'Internetanschluss', 'description' => 'Primäre Verbindung, Voraussetzung für Online-Dienste und Fachverfahren.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => null],
                ['name' => 'Netzwerk / WLAN', 'description' => 'Internes Verwaltungsnetz, Anbindung der Liegenschaften.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Zentrale Server / Rechenzentrum', 'description' => 'Datenbank- und Verzeichnisdienste, Virtualisierung.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Telefonanlage / VoIP', 'description' => 'Erreichbarkeit für Bürgerinnen und Bürger.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],

                ['name' => 'Fachverfahren', 'description' => 'Fachanwendungen (z. B. Einwohnerwesen, Kfz-Zulassung, Sozialwesen).', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'DMS / E-Akte', 'description' => 'Elektronische Aktenführung, Vorgangsbearbeitung, Archiv.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Finanz- / Haushalts- und Kassensystem', 'description' => 'Haushaltsbewirtschaftung, Buchung, Zahlungsverkehr.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Bürgerportal / Online-Dienste (OZG)', 'description' => 'Online-Anträge, Servicekonto, Bezahldienste.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Ratsinformationssystem', 'description' => 'Sitzungsmanagement, Vorlagen, Beschlüsse.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'GIS / Geoinformationssystem', 'description' => 'Liegenschafts- und Kartendaten, Planauskunft.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],

                ['name' => 'E-Mail', 'description' => 'Dienstliche Postfächer, Posteingang Verwaltung.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 240],
                ['name' => 'Cloud- / Dateispeicher', 'description' => 'Gemeinsame Ablage, Dokumente, Vorlagen.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 480, 'rpo_minutes' => 60],
                ['name' => 'Office-Paket', 'description' => 'Textverarbeitung, Tabellen, Schriftgut.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Website / CMS', 'description' => 'Amtliche Bekanntmachungen, Informationen.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 4320],
                ['name' => 'Zutritts- / Alarmanlage', 'description' => 'Zugangskontrolle und Objektsicherung der Liegenschaften.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
            ],
            'scenarios' => [
                [
                    'name' => 'Ransomware / Cyberangriff auf die Verwaltung',
                    'description' => 'Verschlüsselte Rechner, gesperrte Fachverfahren oder eine Lösegeldforderung — der Verwaltungsbetrieb ist akut bedroht.',
                    'trigger' => 'Lösegeldforderung auf dem Bildschirm, Fachverfahren oder E-Akte plötzlich nicht mehr erreichbar, Warnung aus dem Monitoring oder vom Rechenzentrum.',
                    'steps' => [
                        ['title' => 'Betroffene Rechner vom Netz trennen', 'description' => 'Netzwerkkabel ziehen bzw. WLAN deaktivieren, betroffene Segmente isolieren. Geräte NICHT ausschalten — Beweise und Spuren erhalten.', 'responsible' => 'IT'],
                        ['title' => 'Leitung informieren und Krisenstab einberufen', 'description' => 'Kurze Lagemeldung an Bürgermeister/in bzw. Amtsleitung: Zeitpunkt, betroffene Systeme, was bereits getan wurde. Krisenstab zusammenrufen.', 'responsible' => 'IT'],
                        ['title' => 'IT-Dienstleister / kommunales Rechenzentrum alarmieren', 'description' => 'Notfall-Hotline anrufen, Vorfall schildern, gemeinsame Sofortmaßnahmen abstimmen (Zugänge sperren, Backups prüfen).', 'responsible' => 'IT'],
                        ['title' => 'Backups sichern und Umfang feststellen', 'description' => 'Backup-Systeme sofort vom Netz nehmen bzw. schreibgeschützt stellen. Klären: Welche Fachverfahren, Postfächer und Ablagen sind betroffen?', 'responsible' => 'IT'],
                        ['title' => 'Meldewege starten: Landes-CERT und Datenschutz', 'description' => 'Vorfall an das CERT des Bundeslandes melden (empfohlen: unverzüglich). Datenschutzbeauftragte/n einbinden — bei personenbezogenen Daten läuft die 72-Stunden-Frist (DSGVO). Anzeige bei der Polizei (ZAC) prüfen.', 'responsible' => 'Leitung'],
                        ['title' => 'Kommunalaufsicht und Versicherung informieren', 'description' => 'Rechts-/Kommunalaufsicht zeitnah über Lage und Maßnahmen informieren. Cyberversicherung mit Policennummer und Zeitstempel benachrichtigen.', 'responsible' => 'Leitung'],
                        ['title' => 'Beschäftigte informieren', 'description' => 'Klare Verhaltensregeln: keine Anmeldungen an betroffenen Systemen, keine Auskünfte nach außen, Sprachregelung beachten. Notfalls per Telefonkette oder Aushang, falls E-Mail ausgefallen ist.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Bürgerinnen und Bürger informieren', 'description' => 'Hinweis auf Website und am Eingang: eingeschränkter Service, alternative Erreichbarkeit, Terminverschiebungen. Presseanfragen nur über eine abgestimmte Sprachregelung beantworten.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Alle Schritte dokumentieren', 'description' => 'Zeitpunkte, Entscheidungen, Meldungen und Ansprechpartner fortlaufend protokollieren — Grundlage für Meldepflichten, Versicherung und die spätere Auswertung.', 'responsible' => 'Leitung'],
                    ],
                ],
                [
                    'name' => 'Stromausfall im Rathaus',
                    'description' => 'Das Verwaltungsgebäude ist ohne Strom — Arbeitsplätze, Server, Telefonie und Publikumsverkehr sind betroffen.',
                    'trigger' => 'Beleuchtung und Rechner fallen aus, USV meldet Batteriebetrieb, Störungsmeldung des Netzbetreibers.',
                    'steps' => [
                        ['title' => 'Umfang und voraussichtliche Dauer klären', 'description' => 'Nur das Gebäude oder der ganze Ortsteil? Netzbetreiber bzw. Stadtwerke kontaktieren, Störungskarte prüfen.', 'responsible' => 'IT'],
                        ['title' => 'USV-Laufzeit prüfen und Server geordnet herunterfahren', 'description' => 'Restlaufzeit der USV feststellen. Reicht sie nicht: Server und Fachverfahren kontrolliert herunterfahren, um Datenverlust zu vermeiden.', 'responsible' => 'IT'],
                        ['title' => 'Leitung informieren und Lagebild erstellen', 'description' => 'Kurzmeldung an die Verwaltungsleitung: Umfang, erwartete Dauer, betroffene Bereiche. Entscheidung über die nächsten Schritte vorbereiten.', 'responsible' => 'Leitung'],
                        ['title' => 'Gebäude- und Personensicherheit herstellen', 'description' => 'Aufzüge kontrollieren (eingeschlossene Personen?), Fluchtwege und Notbeleuchtung prüfen, elektrische Türen und Schranken in Handbetrieb nehmen.', 'responsible' => 'Facility Management'],
                        ['title' => 'Über Publikumsverkehr entscheiden', 'description' => 'Bürgerbüro offen lassen (Notbetrieb mit Papier) oder schließen? Termine des Tages sichten und priorisieren.', 'responsible' => 'Leitung'],
                        ['title' => 'Bürgerinnen und Bürger informieren', 'description' => 'Aushang am Eingang, Hinweis auf der Website (über Mobilfunk pflegbar), Ansage/Umleitung der Telefonzentrale, ggf. Social-Media-Kanäle.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Kritische Außenstellen prüfen', 'description' => 'Sind angeschlossene Einrichtungen betroffen (z. B. Kitas, Bauhof, Feuerwehrhaus)? Rückmeldungen einsammeln und Unterstützung koordinieren.', 'responsible' => 'Leitung'],
                        ['title' => 'Wiederanlauf nach Stromrückkehr', 'description' => 'Systeme in der definierten Reihenfolge (Recovery-Zeitplan) hochfahren, Funktionstest der Fachverfahren, Vorfall und Ausfallzeit dokumentieren.', 'responsible' => 'IT'],
                    ],
                ],
                [
                    'name' => 'Ausfall Fachverfahren / Notbetrieb Bürgerbüro',
                    'description' => 'Ein zentrales Fachverfahren (z. B. Einwohnerwesen, Kfz-Zulassung) fällt aus — das Bürgerbüro muss in den Notbetrieb.',
                    'trigger' => 'Fachverfahren startet nicht oder meldet Fehler, Störungsmeldung des Verfahrensherstellers oder des Rechenzentrums, Mitarbeitende können Anliegen nicht bearbeiten.',
                    'steps' => [
                        ['title' => 'Störung eingrenzen', 'description' => 'Welches Verfahren ist betroffen, seit wann, welche Fehlermeldung? Liegt es am Verfahren selbst, am Netz oder am Rechenzentrum?', 'responsible' => 'IT'],
                        ['title' => 'Hersteller bzw. Rechenzentrum kontaktieren', 'description' => 'Störungsticket mit Priorität eröffnen, Fehlerbild und Zeitstempel übermitteln, Rückrufzeit vereinbaren.', 'responsible' => 'IT'],
                        ['title' => 'Leitung und betroffene Bereiche informieren', 'description' => 'Bürgerbüro und Fachämter wissen lassen: Was geht nicht, was geht weiter, voraussichtliche Dauer (sofern bekannt).', 'responsible' => 'Leitung'],
                        ['title' => 'Notbetrieb im Bürgerbüro starten', 'description' => 'Auf Papierformulare und Ersatzprozesse umstellen, nicht dringende Anliegen auf neue Termine verschieben, dringende Fälle priorisieren.', 'responsible' => 'Leitung'],
                        ['title' => 'Wartende und Online-Kanäle informieren', 'description' => 'Hinweis im Wartebereich und auf der Website: welche Dienstleistungen aktuell nicht verfügbar sind und welche Alternativen es gibt.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Fristenrelevante Vorgänge sichern', 'description' => 'Anliegen mit gesetzlichen Fristen (z. B. Meldefristen, Anträge) handschriftlich bzw. formulargestützt aufnehmen und mit Eingangsdatum dokumentieren.', 'responsible' => 'Leitung'],
                        ['title' => 'Nacherfassung vorbereiten', 'description' => 'Gesammelte Papiervorgänge strukturiert ablegen, damit sie nach Rückkehr des Verfahrens zügig und vollständig nachgetragen werden können.', 'responsible' => 'IT'],
                        ['title' => 'Ausfall dokumentieren und auswerten', 'description' => 'Ausfallzeit, Ursache und Auswirkungen festhalten. Bei längerem oder wiederholtem Ausfall: Gespräch mit dem Hersteller bzw. Rechenzentrum über Konsequenzen.', 'responsible' => 'Leitung'],
                    ],
                ],
                [
                    'name' => 'Hochwasser / Unwetter am Verwaltungsstandort',
                    'description' => 'Starkregen, Hochwasser oder Sturm bedrohen das Verwaltungsgebäude — Menschen, Technik und Akten müssen geschützt werden.',
                    'trigger' => 'Amtliche Unwetterwarnung, steigende Pegel, Wassereintritt im Gebäude, Sturmschäden am Standort.',
                    'steps' => [
                        ['title' => 'Personen in Sicherheit bringen', 'description' => 'Gefährdete Bereiche (Keller, Erdgeschoss) räumen, Beschäftigte und Besucher warnen. Personenschutz geht vor Sachschutz.', 'responsible' => 'Leitung'],
                        ['title' => 'Lage mit Feuerwehr und Katastrophenschutz abstimmen', 'description' => 'Kontakt zur Einsatzleitung halten, Prognose einholen, eigene Maßnahmen mit den Einsatzkräften koordinieren.', 'responsible' => 'Leitung'],
                        ['title' => 'IT-Technik schützen', 'description' => 'Server und Netzwerktechnik aus gefährdeten Räumen entfernen oder höher lagern, Strom in bedrohten Bereichen gezielt abschalten, laufende Systeme geordnet herunterfahren.', 'responsible' => 'IT'],
                        ['title' => 'Akten und Wertgegenstände sichern', 'description' => 'Papierakten, Siegel, Kassenbestände und wichtige Unterlagen aus Keller und Erdgeschoss in obere Stockwerke bringen.', 'responsible' => 'Facility Management'],
                        ['title' => 'Gebäude sichern', 'description' => 'Fenster und Türen schließen, mobile Hochwassersperren bzw. Sandsäcke setzen, Außenanlagen sichern (lose Gegenstände, Baustellen).', 'responsible' => 'Facility Management'],
                        ['title' => 'Ausweich-Arbeitsplätze organisieren', 'description' => 'Homeoffice ermöglichen, Ausweichstandort (z. B. anderes städtisches Gebäude) vorbereiten, Umleitung von Telefon und Post veranlassen.', 'responsible' => 'IT'],
                        ['title' => 'Beschäftigte informieren', 'description' => 'Wer arbeitet wo, wer bleibt zu Hause, wer wird vor Ort gebraucht? Erreichbarkeiten und Ansprechpartner klar kommunizieren.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Bürgerinnen und Bürger informieren', 'description' => 'Geschlossene Dienststellen, alternative Erreichbarkeit und Notfallkontakte über Website, Presse und Aushänge bekanntgeben.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Schäden dokumentieren und Wiederanlauf planen', 'description' => 'Fotos und Schadenslisten für die Versicherung erstellen, Trocknung und Instandsetzung beauftragen, Rückkehr in den Normalbetrieb planen.', 'responsible' => 'Leitung'],
                    ],
                ],
                [
                    'name' => 'Evakuierung eines Verwaltungsgebäudes',
                    'description' => 'Das Gebäude muss geräumt werden — etwa wegen Brandalarm, Bombendrohung, Gasgeruch oder eines Gefahrstoffaustritts.',
                    'trigger' => 'Brandmeldeanlage löst aus, Anordnung der Einsatzkräfte, Drohung oder wahrnehmbare Gefahr (Rauch, Gasgeruch).',
                    'steps' => [
                        ['title' => 'Alarm auslösen und Räumung starten', 'description' => 'Hausalarm betätigen, Räumung über die festgelegten Fluchtwege einleiten, Notruf 112 absetzen (falls noch nicht geschehen).', 'responsible' => 'Leitung'],
                        ['title' => 'Sammelplatz ansteuern und Vollzähligkeit prüfen', 'description' => 'Etagen-/Räumungsverantwortliche melden ihre Bereiche als geräumt. Anwesenheit am Sammelplatz mit Besucher- und Anwesenheitslisten abgleichen, Vermisste sofort den Einsatzkräften melden.', 'responsible' => 'Leitung'],
                        ['title' => 'Besucher und mobilitätseingeschränkte Personen unterstützen', 'description' => 'Publikum aus Wartebereichen mitnehmen, Personen mit Einschränkungen über die vorgesehenen Rettungswege begleiten bzw. den Einsatzkräften melden.', 'responsible' => 'Facility Management'],
                        ['title' => 'Einsatzkräfte einweisen', 'description' => 'Feuerwehr am Zugang empfangen: Lagepläne, Schlüssel, Gefahrstoffliste und Informationen zu vermissten Personen übergeben.', 'responsible' => 'Facility Management'],
                        ['title' => 'Telefon- und Erreichbarkeitsumleitung aktivieren', 'description' => 'Zentrale Rufnummern auf Mobiltelefone oder einen Ausweichstandort umleiten, damit die Verwaltung erreichbar bleibt.', 'responsible' => 'IT'],
                        ['title' => 'Beschäftigte über das weitere Vorgehen informieren', 'description' => 'Am Sammelplatz und über Messenger/Telefonkette: Wartezeit, Heimweg oder Ausweichstandort — klare Anweisungen geben.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Bürgerinnen und Bürger informieren', 'description' => 'Schließung und Terminausfälle über Website, Telefonansage und Aushang kommunizieren, Ausweichtermine anbieten.', 'responsible' => 'Kommunikation'],
                        ['title' => 'Rückkehr erst nach Freigabe und Dokumentation', 'description' => 'Gebäude erst nach Freigabe durch die Einsatzkräfte wieder betreten. Ablauf, Zeiten und Erkenntnisse dokumentieren und in die Nachbereitung (Lessons Learned) übernehmen.', 'responsible' => 'Leitung'],
                    ],
                ],
            ],
        ],

        Industry::Sonstiges->value => [
            'label' => 'Allgemein (sonstige Branche)',
            'hint' => 'Branchenneutrale Basis-Systeme für kleine und mittlere Unternehmen – als Ausgangspunkt zum Anpassen.',
            'systems' => [
                ['name' => 'Stromversorgung / USV', 'description' => 'Netzstrom und Notstromabsicherung für IT und Büro.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 15, 'rpo_minutes' => null],
                ['name' => 'Internetanschluss', 'description' => 'Primäre Internetverbindung für Cloud, E-Mail, VoIP.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => null],
                ['name' => 'Netzwerk / WLAN', 'description' => 'Internes Netzwerk, Switches, Access Points.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 60, 'rpo_minutes' => null],
                ['name' => 'Server / Zentralrechner', 'description' => 'Dateiablage, Benutzerverwaltung, zentrale Anwendungen.', 'category' => 'basisbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Telefonanlage / Mobilfunk', 'description' => 'Erreichbarkeit für Kunden und Partner.', 'category' => 'basisbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => null],

                ['name' => 'Branchensoftware / Kernanwendung', 'description' => 'Die zentrale Anwendung des Geschäftsbetriebs.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Kritisch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Rechnungs- und Angebotswesen', 'description' => 'Angebote, Rechnungen, Mahnwesen.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 60],
                ['name' => 'Buchhaltung', 'description' => 'Finanzbuchhaltung und Lohn (z. B. DATEV, Lexware).', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'CRM / Kundenverwaltung', 'description' => 'Kundenstammdaten, Kontakte, Vertrieb.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Hoch', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Online-Präsenz / Website', 'description' => 'Firmenwebsite, Anfrageformulare, Google-Business.', 'category' => 'geschaeftsbetrieb', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 4320],

                ['name' => 'E-Mail', 'description' => 'Geschäfts-E-Mail-Postfächer.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 240, 'rpo_minutes' => 240],
                ['name' => 'Cloud-Speicher', 'description' => 'Gemeinsame Dokumente, Belege, Dateien.', 'category' => 'unterstuetzend', 'priority' => 'Hoch', 'rto_minutes' => 480, 'rpo_minutes' => 60],
                ['name' => 'Office-Paket', 'description' => 'Word/Excel für Schriftverkehr und Listen.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
                ['name' => 'Kalender / Terminplanung', 'description' => 'Termine, Urlaube, gemeinsame Kalender.', 'category' => 'unterstuetzend', 'priority' => 'Normal', 'rto_minutes' => 1440, 'rpo_minutes' => 1440],
            ],
        ],
    ];

    /**
     * @return array<string, array{label: string, hint: string, count: int, scenario_count: int}>
     */
    public static function catalog(): array
    {
        $catalog = [];
        foreach (self::TEMPLATES as $key => $tpl) {
            $catalog[$key] = [
                'label' => $tpl['label'],
                'hint' => $tpl['hint'],
                'count' => count($tpl['systems']),
                'scenario_count' => count($tpl['scenarios'] ?? []),
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

    /**
     * Szenario-Vorlagen des Templates (z. B. kommunale Notfall-Playbooks
     * beim Verwaltungs-Template). Leeres Array, wenn das Template keine
     * eigenen Szenarien mitbringt.
     *
     * @return array<int, array{name: string, description: string, trigger: string, steps: array<int, array{title: string, description: string, responsible: string}>}>
     */
    public static function scenariosFor(string $industryValue): array
    {
        return self::TEMPLATES[$industryValue]['scenarios'] ?? [];
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
        if ($industry === null) {
            return Industry::Sonstiges->value;
        }

        return self::has($industry->value) ? $industry->value : null;
    }

    public static function categoryIsValid(string $category): bool
    {
        return in_array($category, array_column(SystemCategory::cases(), 'value'), true);
    }
}
