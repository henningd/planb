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
