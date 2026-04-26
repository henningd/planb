<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class AnwaltskanzleiTemplate implements Contract
{
    public function name(): string
    {
        return 'Anwaltskanzlei (5–25 MA, mit beA)';
    }

    public function industry(): Industry
    {
        return Industry::Dienstleistung;
    }

    public function description(): string
    {
        return 'Mittelständische Rechtsanwalts-Partnerschaft mit Berufsträgern, ReNo-Fachangestellten und Sekretariat. Schwerpunkt Kanzleisoftware (RA-Micro/Advoware/DATEV Anwalt) und beA-Pflichtanbindung an Gerichte. Enthält: 11 Mitarbeiter, 2 Standorte, 11 Systeme inkl. RTO/RPO, 6 Dienstleister (inkl. BRAK), Berufshaftpflicht, Kommunikationsvorlagen für beA-Störung, Notfall-Briefpapier und Tabletop-Tests.';
    }

    public function sort(): int
    {
        return 100;
    }

    public function payload(): array
    {
        $kanzlei = Helpers::uuid();
        $zweigstelle = Helpers::uuid();

        $emp = [
            'partner1' => Helpers::uuid(),
            'partner2' => Helpers::uuid(),
            'anwalt1' => Helpers::uuid(),
            'anwalt2' => Helpers::uuid(),
            'reno1' => Helpers::uuid(),
            'reno2' => Helpers::uuid(),
            'sekretariat' => Helpers::uuid(),
            'buero' => Helpers::uuid(),
            'it_extern' => Helpers::uuid(),
            'buchhaltung_extern' => Helpers::uuid(),
            'dsb_extern' => Helpers::uuid(),
        ];

        $prov = [
            'brak' => Helpers::uuid(),
            'kanzleisoftware' => Helpers::uuid(),
            'it_msp' => Helpers::uuid(),
            'steuer' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
            'isp' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'server' => Helpers::uuid(),
            'kanzleisoftware' => Helpers::uuid(),
            'bea' => Helpers::uuid(),
            'dms' => Helpers::uuid(),
            'diktat' => Helpers::uuid(),
            'm365' => Helpers::uuid(),
            'telefon' => Helpers::uuid(),
            'drucker' => Helpers::uuid(),
            'backup' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Kessler & Partner Rechtsanwälte mbB',
                'industry' => 'dienstleistung',
                'employee_count' => 11,
                'locations_count' => 2,
                'review_cycle_months' => 12,
                'legal_form' => 'partg',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'not_affected',
                'cyber_insurance_deductible' => '2.500 €',
                'budget_it_lead' => 1000,
                'budget_emergency_officer' => 3000,
                'budget_management' => 25000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
            ]],

            'locations' => [
                [
                    'id' => $kanzlei, 'name' => 'Kanzlei Stuttgart', 'street' => 'Königstraße 28',
                    'postal_code' => '70173', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 5566778',
                    'notes' => 'Hauptkanzlei mit Empfang, Besprechungsräumen, Server-Schrank im Keller.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
                [
                    'id' => $zweigstelle, 'name' => 'Zweigstelle Esslingen', 'street' => 'Bahnhofsplatz 4',
                    'postal_code' => '73728', 'city' => 'Esslingen', 'country' => 'DE',
                    'is_headquarters' => 0, 'phone' => '0711 5566779',
                    'notes' => 'Zweigstelle, 1 Anwalt + 1 ReNo, VPN zum Hauptserver.',
                    'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['partner1'], 'first_name' => 'Dr. Friedrich', 'last_name' => 'Kessler', 'position' => 'Partner / Rechtsanwalt', 'department' => 'Geschäftsführung', 'work_phone' => '0711 5566701', 'mobile_phone' => '0171 4455661', 'private_phone' => '0711 7788990', 'email' => 'kessler@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => 'Gründer, Schwerpunkt Wirtschaftsrecht.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['partner2'], 'first_name' => 'Annika', 'last_name' => 'Hartmann', 'position' => 'Partnerin / Fachanwältin Arbeitsrecht', 'department' => 'Geschäftsführung', 'work_phone' => '0711 5566702', 'mobile_phone' => '0171 4455662', 'private_phone' => null, 'email' => 'hartmann@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['anwalt1'], 'first_name' => 'Tobias', 'last_name' => 'Werner', 'position' => 'Angestellter Rechtsanwalt', 'department' => 'Mandate', 'work_phone' => '0711 5566711', 'mobile_phone' => '0171 4455663', 'private_phone' => null, 'email' => 'werner@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => $emp['partner1'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'Schwerpunkt Vertragsrecht.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['anwalt2'], 'first_name' => 'Carla', 'last_name' => 'Brandt', 'position' => 'Angestellte Rechtsanwältin', 'department' => 'Mandate', 'work_phone' => '0711 5566712', 'mobile_phone' => '0171 4455664', 'private_phone' => null, 'email' => 'brandt@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => $emp['partner2'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'Sitz Zweigstelle Esslingen.', 'location_id' => $zweigstelle, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['reno1'], 'first_name' => 'Sabine', 'last_name' => 'Müller', 'position' => 'ReNo-Fachangestellte', 'department' => 'Mandate', 'work_phone' => '0711 5566721', 'mobile_phone' => '0171 4455665', 'private_phone' => null, 'email' => 'mueller@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => $emp['partner1'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'beA-Verwaltung, Fristenkalender.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['reno2'], 'first_name' => 'Janine', 'last_name' => 'Schulze', 'position' => 'ReNo-Fachangestellte', 'department' => 'Mandate', 'work_phone' => '0711 5566722', 'mobile_phone' => '0171 4455666', 'private_phone' => null, 'email' => 'schulze@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => $emp['partner2'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => 'Vertretung Fristenkalender.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['sekretariat'], 'first_name' => 'Petra', 'last_name' => 'Lindner', 'position' => 'Sekretariat / Empfang', 'department' => 'Verwaltung', 'work_phone' => '0711 5566700', 'mobile_phone' => '0171 4455667', 'private_phone' => null, 'email' => 'empfang@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => $emp['partner1'], 'is_key_personnel' => 0, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buero'], 'first_name' => 'Marlene', 'last_name' => 'Krause', 'position' => 'Büroleitung', 'department' => 'Verwaltung', 'work_phone' => '0711 5566703', 'mobile_phone' => '0171 4455668', 'private_phone' => null, 'email' => 'krause@kessler-partner.example', 'emergency_contact' => null, 'manager_id' => $emp['partner1'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Operative Notfall-Koordination.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['it_extern'], 'first_name' => 'Stefan', 'last_name' => 'Behrens', 'position' => 'IT-Dienstleister (extern)', 'department' => 'IT', 'work_phone' => '0711 9988770', 'mobile_phone' => '0171 4455669', 'private_phone' => null, 'email' => 'behrens@kanzlei-it.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Spezialisierter Kanzlei-MSP.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung_extern'], 'first_name' => 'Helga', 'last_name' => 'Vogt', 'position' => 'Buchhaltung (extern, Steuerberater)', 'department' => 'Verwaltung', 'work_phone' => '0711 4433221', 'mobile_phone' => null, 'private_phone' => null, 'email' => 'vogt@stb-vogt.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb_extern'], 'first_name' => 'Dr. Marion', 'last_name' => 'Albrecht', 'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 4455670', 'private_phone' => null, 'email' => 'albrecht@dsb-anwalt.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => 'Spezialisiert auf Berufsgeheimnisträger (DSGVO + BRAO §43a).', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['brak'], 'name' => 'BRAK – Bundesrechtsanwaltskammer (beA)', 'type' => 'other', 'contact_name' => 'beA-Anwendersupport', 'hotline' => '030 21786714', 'email' => 'servicedesk@bea-brak.de', 'contract_number' => 'beA-Pflicht', 'sla' => 'Mo-Fr 8-20', 'notes' => 'Betreiber des besonderen elektronischen Anwaltspostfachs. Bei Ausfall sofort Servicedesk und Gerichts-Faxzugang prüfen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['kanzleisoftware'], 'name' => 'RA-MICRO Software AG', 'type' => 'other', 'contact_name' => 'Hotline Anwendersupport', 'hotline' => '030 4357 1717', 'email' => 'support@ra-micro.de', 'contract_number' => 'RAM-2024-3344', 'sla' => 'Mo-Fr 8-18', 'notes' => 'Kanzleisoftware (Akten, Buchhaltung, Termine, Fristen, beA-Schnittstelle).', 'direct_order_limit' => 2000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['it_msp'], 'name' => 'Kanzlei-IT Süd GmbH', 'type' => 'it_msp', 'contact_name' => 'Stefan Behrens', 'hotline' => '0711 9988770', 'email' => 'support@kanzlei-it.example', 'contract_number' => 'MSP-2026-001', 'sla' => 'Mo-Fr 8-18, Notfall 24/7', 'notes' => 'Server, Backup, Netzwerk, M365. Spezialisiert auf Kanzleien.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['steuer'], 'name' => 'Steuerberatung Vogt', 'type' => 'tax_advisor', 'contact_name' => 'Helga Vogt', 'hotline' => '0711 4433221', 'email' => 'vogt@stb-vogt.example', 'contract_number' => 'STB-2025', 'sla' => 'Mo-Fr 9-17', 'notes' => 'Buchhaltung, Lohn, Jahresabschluss.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen. Achtung: §43a BRAO bleibt vorrangig.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['isp'], 'name' => 'TelCo Deutschland AG', 'type' => 'internet_provider', 'contact_name' => 'Geschäftskunden-Störung', 'hotline' => '0800 3300000', 'email' => 'stoerung@telco.example', 'contract_number' => 'GK-554433', 'sla' => '24/7', 'notes' => 'Glasfaser 500/200, statische IP. Zweitleitung LTE als Failover.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Kanzlei', 'description' => 'Hausanschluss + USV im Server-Raum.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'USV überbrückt 30 Min.; kontrollierter Shutdown der Server, Mandanten-Termine telefonisch absagen.', 'runbook_reference' => 'Runbook „Stromausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang Kanzlei', 'description' => 'Glasfaser 500/200 + LTE-Failover.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'LTE-Router automatisch aktivieren; beA-Versand vom Mobilfunk-Hotspot möglich.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['server'], 'name' => 'Akten-Server (lokal)', 'description' => 'Windows-Server mit Akten, AD, Druck, Datei-Freigaben.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'Restore aus Offline-Backup (USB), parallel Papier-Handakten aus Tresor verwenden.', 'runbook_reference' => 'Runbook „Server-Restore" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['kanzleisoftware'], 'name' => 'Kanzleisoftware RA-MICRO', 'description' => 'Akten-, Termin-, Fristen-, Abrechnungssoftware. Pflichtsystem.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 500, 'fallback_process' => 'Fristenkalender auf Papier weiterführen; Hotline RA-MICRO einbinden; Aktenarbeit aus DMS-Web-Client.', 'runbook_reference' => 'Runbook „RA-MICRO Recovery" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['bea'], 'name' => 'beA – besonderes elektronisches Anwaltspostfach', 'description' => 'Pflicht-Schnittstelle zu Gerichten (Schriftsätze, Fristen). Ausfall = sofortige Eskalation.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'Fax / EGVP-Postfach als Notfallweg, sofort Fristverlängerung beim Gericht beantragen, beA-Störung dokumentieren (Screenshot von beA-Statusseite).', 'runbook_reference' => 'Runbook „beA-Störung" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['dms'], 'name' => 'Aktenverwaltung / DMS', 'description' => 'Digitale Aktenablage, gescannte Dokumente, Verträge.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'Papier-Handakten (kopiert) aus Tresor; verschlüsselter USB-Stick mit aktuellem Akten-Snapshot.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['diktat'], 'name' => 'Diktiersystem (Olympus / Speech Live)', 'description' => 'Digitales Diktiersystem mit Cloud-Synchronisation.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Direktdiktat ans Sekretariat; Smartphone-Aufnahme als temporärer Ersatz.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['m365'], 'name' => 'Microsoft 365 (E-Mail, Office, Teams)', 'description' => 'M365 Business Premium für Mail, Kalender, Office.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'Webmail über Mobilfunk; kritische Mandanten-Mails per SMS bestätigen.', 'runbook_reference' => 'Runbook „M365 Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefon'], 'name' => 'Telefonanlage (VoIP)', 'description' => 'Cloud-Telefonanlage, alle Durchwahlen.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Notfall-Mobilrufnummer Sekretariat als Kanzlei-Hotline kommunizieren.', 'runbook_reference' => 'Runbook „VoIP-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['drucker'], 'name' => 'Drucker / Wertpapierdruck', 'description' => 'Multifunktionsgeräte mit gesondertem Wertpapier-Briefpapier-Schacht.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 60, 'fallback_process' => 'Notfall-Briefpapier mit Briefkopf liegt im Tresor; Druckdienstleister um die Ecke (Copy-Shop) als Backup.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['backup'], 'name' => 'Backup-System', 'description' => '3-2-1-Backup: lokal NAS, Cloud, Offline-USB im Bankschließfach.', 'category' => 'sicherheit', 'rto_minutes' => 60, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Manuelles Backup auf USB durch IT-Lead; tägliche Snapshots manuell prüfen.', 'runbook_reference' => 'Runbook „Backup-Restore" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['server'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['kanzleisoftware'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => 'Datenbank liegt auf Akten-Server.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['bea'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Ohne Internet keine beA-Kommunikation mit Gerichten.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['bea'], 'depends_on_system_id' => $sys['kanzleisoftware'], 'sort' => 1, 'note' => 'beA-Schnittstelle liegt in Kanzleisoftware.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['dms'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['diktat'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Cloud-Sync.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['m365'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['drucker'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => 'Druckwarteschlange auf Server.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['backup'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'professional_liability', 'insurer' => 'HDI Versicherung AG', 'policy_number' => 'BHV-2026-RA-887766', 'hotline' => '0511 645-0', 'email' => 'anwalt@hdi.example', 'reporting_window' => 'unverzüglich', 'contact_name' => 'Schadenstelle Anwälte', 'notes' => 'Berufshaftpflicht (Pflicht nach §51 BRAO). Deckung 5 Mio €.', 'deductible' => '5.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'CyberSchutz24 AG', 'policy_number' => 'CY-2026-RA-998877', 'hotline' => '0800 8765432', 'email' => 'schaden@cyberschutz24.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Frau Hartmann', 'notes' => 'Deckung 1 Mio €, inkl. forensische Hilfe und Mandanteninformation.', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-RA-554433', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 60 Tage.', 'deductible' => '1.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'beA-Störungsmeldung an Gericht (Fristverlängerung)', 'audience' => 'authorities', 'channel' => 'fax', 'subject' => 'Antrag auf Fristverlängerung wegen beA-Störung', 'body' => "An das {{ gericht }}\nGeschäftszeichen: {{ aktenzeichen }}\n\nSehr geehrte Damen und Herren,\n\nwir zeigen an, dass im laufenden Verfahren eine fristgebundene Schriftsatzeingabe vorgesehen ist. Aufgrund einer aktuellen Störung des besonderen elektronischen Anwaltspostfachs (beA), dokumentiert seit {{ zeitpunkt }} unter https://bea-brak.de/, ist die elektronische Übermittlung derzeit nicht möglich.\n\nWir bitten um Verlängerung der Frist um angemessene Zeit, ersatzweise um Annahme dieses Schriftsatzes per Telefax gemäß §130d Satz 2 ZPO.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Telefonische Eskalation Gerichts-Geschäftsstelle.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Mandanten-Information bei Kanzlei-Ausfall', 'audience' => 'customers', 'channel' => 'email', 'subject' => 'Vorübergehende Einschränkung unserer Erreichbarkeit', 'body' => "Sehr geehrte Mandantin, sehr geehrter Mandant,\n\naufgrund einer technischen Störung in unserer Kanzlei sind wir aktuell eingeschränkt erreichbar. Ihre Mandate werden selbstverständlich weiter bearbeitet, alle Fristen sind gesichert.\n\nIn dringenden Angelegenheiten erreichen Sie uns über die Notfallnummer 0171 4455667 (Sekretariat) oder direkt über Ihren betreuenden Berufsträger.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}\nKessler & Partner Rechtsanwälte mbB", 'fallback' => 'Persönlicher Anruf bei Mandanten mit laufenden Eilsachen.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Eilantrag bei Fristproblem', 'audience' => 'authorities', 'channel' => 'fax', 'subject' => 'Eilantrag / Wiedereinsetzung in den vorigen Stand', 'body' => "An das {{ gericht }}\nGeschäftszeichen: {{ aktenzeichen }}\n\nNamens und im Auftrag der Mandantschaft beantragen wir Wiedereinsetzung in den vorigen Stand gemäß §233 ZPO bezüglich der versäumten Frist {{ frist }}.\n\nGrund: technische Störung der elektronischen Übermittlung am {{ zeitpunkt }} (beA-Störung dokumentiert).\n\nWir versichern anwaltlich die Richtigkeit des Vortrags.\n\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Aufsichtsbehörde', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – Kessler & Partner', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit melden wir gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten.\n\nVerantwortlicher: Kessler & Partner Rechtsanwälte mbB\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\n\nHinweis: Die Berufsverschwiegenheitspflicht nach §43a BRAO bleibt unberührt; Mandantengeheimnisse werden nicht offengelegt.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Erstmeldung Mitarbeiter (SMS)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'Wichtig: Aktuell technische Störung in der Kanzlei. Keine Mails versenden, keine USB-Sticks anstecken, keine beA-Versuche. Anweisungen über Marlene Krause (0171 4455668). Stand: {{ zeitpunkt }}.', 'fallback' => 'Aushang Empfang + Telefonkette über Sekretariat.', 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfall-Briefpapier mit Briefkopf', 'description' => 'Vorgedrucktes Wertpapier-Briefpapier (200 Blatt) für Schriftsätze ohne Drucker.', 'location' => 'Tresor Empfang, Kanzlei Stuttgart', 'access_holders' => 'Marlene Krause, Petra Lindner, beide Partner', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Verschlüsselte USB-Sticks (Akten-Snapshot)', 'description' => 'Wöchentlicher verschlüsselter Akten-Snapshot, 3× 2TB rotierend.', 'location' => 'Tresor Partner + Bankschließfach', 'access_holders' => 'Dr. Friedrich Kessler, Annika Hartmann, IT-Lead extern', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentliche Rotation Mo morgens.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfall-Telefonliste Mandanten (Eilsachen)', 'description' => 'Papierliste der laufenden Eil-/Fristsachen mit Mandanten-Telefon und betreuendem Berufsträger.', 'location' => 'Tresor Empfang + Privatadresse Sekretariat', 'access_holders' => 'Marlene Krause, Petra Lindner, beide Partner', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(75), 'notes' => 'Quartalsweise aktualisieren.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Papier-Handakten (Eilsachen)', 'description' => 'Papierabzüge aller laufenden Eil- und Fristsachen.', 'location' => 'Aktenraum Kanzlei (feuerfester Schrank)', 'access_holders' => 'ReNo-Fachangestellte, Berufsträger', 'last_check_at' => Helpers::date(-20), 'next_check_at' => Helpers::date(70), 'notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Prepaid-SIM mit Hotspot (beA-Notbetrieb)', 'description' => 'Telekom Prepaid 50GB, Hotspot-fähig. Für mobilen beA-Versand.', 'location' => 'Schreibtisch Büroleitung', 'access_holders' => 'Marlene Krause, Stefan Behrens (IT)', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion des Handbuchs inkl. Telefonkette, Playbooks, beA-Eskalation.', 'location' => '1× Empfang, 1× Privatadresse Partner Kessler, 1× Privatadresse Hartmann', 'access_holders' => 'Beide Partner, Büroleitung, IT-Lead', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste', 'description' => 'Erreichbarkeit aller Krisenrollen + externer DSB + IT-MSP prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-90), 'next_due_at' => Helpers::date(90), 'responsible_employee_id' => $emp['buero'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop beA-Ausfall mit Fristproblem', 'description' => 'Schreibtisch-Übung: beA fällt am Tag der Berufungseinlegung aus. Wer macht was, welche Fax-Eskalation, wer schreibt Wiedereinsetzungsantrag.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-120), 'next_due_at' => Helpers::date(245), 'responsible_employee_id' => $emp['partner1'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Ransomware mit Mandantenakten', 'description' => 'Übung Ransomware-Befall: Akten-Server verschlüsselt. §43a BRAO + DSGVO Art. 33 in Kombination.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['partner2'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Akten-Server', 'description' => 'Voll-Restore aus verschlüsseltem USB-Backup auf Test-Server. Prüfen, dass Akten lesbar.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['it_extern'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Test Notfall-Briefpapier-Kette', 'description' => 'Stichprobe: Schriftsatz auf Notfall-Briefpapier, Versand über Fax bei beA-Ausfall.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-80), 'next_due_at' => Helpers::date(285), 'responsible_employee_id' => $emp['reno1'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['bea'], 'title' => 'beA-Karten-PIN-Test', 'description' => 'Alle beA-Karten der Berufsträger einmal mit PIN testen, Verfallsdatum prüfen.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['bea'], 'title' => 'beA-Vertretungsregelung prüfen', 'description' => 'Anwalts-Vertretung im beA hinterlegt, Postfach-Berechtigungen ReNo aktuell.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['kanzleisoftware'], 'title' => 'RA-MICRO-Update einspielen', 'description' => 'Release Notes lesen, im Sandbox-Mandant testen, Produktiv-Update.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['server'], 'title' => 'Restore-Test Akten-Server', 'description' => 'Voll-Restore vom Offline-USB auf Test-VM.', 'due_date' => Helpers::date(180), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['m365'], 'title' => 'Phishing-Awareness mit Anwaltsfokus', 'description' => 'Simulierte Phishing-Mail (Mandantenkommunikation), Klickraten auswerten.', 'due_date' => Helpers::date(75), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['dms'], 'title' => 'Aktenrechte-Review (Mandatswechsel)', 'description' => 'Wer hat Zugriff auf welche Akte? Ehemalige Mitarbeiter entfernen.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['backup'], 'title' => 'Bankschließfach-Rotation prüfen', 'description' => 'Letzten Offline-Stick aus Bankschließfach holen, Lesetest, Rotation.', 'due_date' => Helpers::date(14), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['drucker'], 'title' => 'Wertpapier-Briefpapier nachbestellen', 'description' => 'Bestand im Tresor prüfen, ggf. nachbestellen.', 'due_date' => Helpers::date(-3), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
