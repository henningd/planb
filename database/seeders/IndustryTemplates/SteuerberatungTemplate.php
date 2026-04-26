<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class SteuerberatungTemplate implements Contract
{
    public function name(): string
    {
        return 'Steuerberatung (Kanzlei, 5–15 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Dienstleistung;
    }

    public function description(): string
    {
        return 'Kleine bis mittlere Steuerberater-Kanzlei mit DATEV-Kern. Schwerpunkt Mandantenbetreuung, Lohn, Finanzbuchführung, Jahresabschluss. Enthält: 10 Mitarbeiter mit Krisenrollen, 2 Standorte, 11 Systeme inkl. RTO/RPO, 6 Dienstleister, Versicherungen, Notfallvorlagen, Testplan.';
    }

    public function sort(): int
    {
        return 50;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $kanzlei = Helpers::uuid();
        $zweigstelle = Helpers::uuid();

        $emp = [
            'partner1' => Helpers::uuid(),
            'partner2' => Helpers::uuid(),
            'stb_angestellt' => Helpers::uuid(),
            'fachangestellt1' => Helpers::uuid(),
            'fachangestellt2' => Helpers::uuid(),
            'fachangestellt3' => Helpers::uuid(),
            'lohn' => Helpers::uuid(),
            'sekretariat' => Helpers::uuid(),
            'it_extern' => Helpers::uuid(),
            'dsb' => Helpers::uuid(),
        ];

        $prov = [
            'datev' => Helpers::uuid(),
            'msp' => Helpers::uuid(),
            'isp' => Helpers::uuid(),
            'kammer' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
            'shred' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'datev_arbeitsplatz' => Helpers::uuid(),
            'datev_cloud' => Helpers::uuid(),
            'datev_komm' => Helpers::uuid(),
            'belegerfassung' => Helpers::uuid(),
            'mandantenportal' => Helpers::uuid(),
            'telefon' => Helpers::uuid(),
            'drucker' => Helpers::uuid(),
            'fileserver' => Helpers::uuid(),
            'alarm' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Schmidt & Partner Steuerberatung mbB',
                'industry' => 'dienstleistung',
                'employee_count' => 10,
                'locations_count' => 2,
                'review_cycle_months' => 12,
                'legal_form' => 'sonstiges',
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
                    'id' => $kanzlei, 'name' => 'Kanzlei Stuttgart', 'street' => 'Königstraße 27',
                    'postal_code' => '70173', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 2244660',
                    'notes' => 'Hauptsitz mit Empfang, Besprechungsräumen, Server-Schrank, Tresor.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
                [
                    'id' => $zweigstelle, 'name' => 'Zweigstelle Ludwigsburg', 'street' => 'Marktplatz 12',
                    'postal_code' => '71634', 'city' => 'Ludwigsburg', 'country' => 'DE',
                    'is_headquarters' => 0, 'phone' => '07141 998877',
                    'notes' => 'Außenstelle, 3 Arbeitsplätze, Mandantengespräche.',
                    'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['partner1'], 'first_name' => 'Andreas', 'last_name' => 'Schmidt', 'position' => 'Partner / Steuerberater', 'department' => 'Geschäftsführung', 'work_phone' => '0711 2244661', 'mobile_phone' => '0171 5566778', 'private_phone' => '0711 4445566', 'email' => 'a.schmidt@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['partner2'], 'first_name' => 'Birgit', 'last_name' => 'Lehmann', 'position' => 'Partnerin / Steuerberaterin', 'department' => 'Geschäftsführung', 'work_phone' => '0711 2244662', 'mobile_phone' => '0171 5566779', 'private_phone' => null, 'email' => 'b.lehmann@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => 'Schwerpunkt Lohn + Finanzbuchführung.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['stb_angestellt'], 'first_name' => 'Christoph', 'last_name' => 'Vogel', 'position' => 'Steuerberater (angestellt)', 'department' => 'Beratung', 'work_phone' => '0711 2244663', 'mobile_phone' => '0171 5566780', 'private_phone' => null, 'email' => 'c.vogel@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => $emp['partner1'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fachangestellt1'], 'first_name' => 'Daniela', 'last_name' => 'Hoffmann', 'position' => 'Steuerfachangestellte', 'department' => 'Bearbeitung', 'work_phone' => '0711 2244664', 'mobile_phone' => '0171 5566781', 'private_phone' => null, 'email' => 'd.hoffmann@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => $emp['stb_angestellt'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => 'DATEV-Power-User, kennt Mandanten-Stammdaten.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fachangestellt2'], 'first_name' => 'Erik', 'last_name' => 'Bauer', 'position' => 'Steuerfachangestellter', 'department' => 'Bearbeitung', 'work_phone' => '0711 2244665', 'mobile_phone' => '0171 5566782', 'private_phone' => null, 'email' => 'e.bauer@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => $emp['partner2'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fachangestellt3'], 'first_name' => 'Franziska', 'last_name' => 'Krüger', 'position' => 'Steuerfachangestellte', 'department' => 'Bearbeitung', 'work_phone' => '07141 998878', 'mobile_phone' => '0171 5566783', 'private_phone' => null, 'email' => 'f.krueger@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => $emp['stb_angestellt'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'In Zweigstelle Ludwigsburg.', 'location_id' => $zweigstelle, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['lohn'], 'first_name' => 'Gerd', 'last_name' => 'Neumann', 'position' => 'Lohnsachbearbeiter', 'department' => 'Lohn', 'work_phone' => '0711 2244666', 'mobile_phone' => '0171 5566784', 'private_phone' => null, 'email' => 'g.neumann@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => $emp['partner2'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'Kennt LODAS / Lohn-und-Gehalt-Workflow.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['sekretariat'], 'first_name' => 'Heike', 'last_name' => 'Weber', 'position' => 'Sekretariat / Empfang', 'department' => 'Verwaltung', 'work_phone' => '0711 2244660', 'mobile_phone' => '0171 5566785', 'private_phone' => null, 'email' => 'h.weber@schmidt-partner-stb.de', 'emergency_contact' => null, 'manager_id' => $emp['partner1'], 'is_key_personnel' => 0, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Erste Anlaufstelle für Mandanten-Anrufe.', 'location_id' => $kanzlei, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['it_extern'], 'first_name' => 'Ingo', 'last_name' => 'Reinhardt', 'position' => 'IT-Betreuung (extern)', 'department' => 'IT', 'work_phone' => null, 'mobile_phone' => '0171 5566786', 'private_phone' => null, 'email' => 'reinhardt@kanzlei-it.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Vor-Ort innerhalb 4h, sonst remote.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb'], 'first_name' => 'Julia', 'last_name' => 'Sommer', 'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 5566787', 'private_phone' => null, 'email' => 'sommer@datenschutz-stb.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => 'Spezialisiert auf StB-Kanzleien (Verschwiegenheit + DSGVO).', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['datev'], 'name' => 'DATEV eG', 'type' => 'other', 'contact_name' => 'DATEV-Service', 'hotline' => '0911 319-0', 'email' => 'info@datev.de', 'contract_number' => 'BNR-9988776', 'sla' => 'Mo-Fr 7-20, Notfall 24/7', 'notes' => 'KRITISCHER Anbieter. Notfall-Hotline-Karte am Empfang. Berater-Nummer im Tresor.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['msp'], 'name' => 'Kanzlei-IT Reinhardt e.K.', 'type' => 'it_msp', 'contact_name' => 'Ingo Reinhardt', 'hotline' => '0711 5544330', 'email' => 'support@kanzlei-it.example', 'contract_number' => 'WAR-2025-08', 'sla' => 'Mo-Fr 8-18, Notfall 24/7', 'notes' => 'Server, Backup, Endpoints, DATEV-Arbeitsplatz-Pflege.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['isp'], 'name' => 'TelCo Deutschland AG', 'type' => 'internet_provider', 'contact_name' => 'Geschäftskunden-Service', 'hotline' => '0800 3300000', 'email' => 'gk-stoerung@telco.example', 'contract_number' => 'GK-558899', 'sla' => '24/7', 'notes' => 'Glasfaser 500/200 mit fester IP, redundante LTE-Backup-Box.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['kammer'], 'name' => 'Steuerberaterkammer Stuttgart', 'type' => 'other', 'contact_name' => 'Geschäftsstelle', 'hotline' => '0711 619480', 'email' => 'info@stbk-stuttgart.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Berufsaufsicht. Bei berufsrechtlich relevantem Datenvorfall einbinden.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['shred'], 'name' => 'AktenSchredder GmbH', 'type' => 'other', 'contact_name' => 'Disposition', 'hotline' => '0711 7766550', 'email' => 'service@aktenschredder.example', 'contract_number' => 'V-2025-77', 'sla' => 'Abholung 14-tägig', 'notes' => 'Sichere Aktenvernichtung gem. DIN 66399 Schutzklasse 3.', 'direct_order_limit' => 500, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Kanzlei', 'description' => 'Hausanschluss + USV für Server, Telefonanlage, Empfang.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'USV überbrückt 30 Min.; bei längerem Ausfall kontrollierter Shutdown + Mandanteninfo.', 'runbook_reference' => 'Runbook „Stromausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang + LTE-Backup', 'description' => 'Glasfaser GK 500/200 + LTE-Backup-Box.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 300, 'fallback_process' => 'Automatischer Failover auf LTE-Box; manuell prüfen ob DATEV-Cloud erreichbar.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['datev_arbeitsplatz'], 'name' => 'DATEV-Arbeitsplatz (lokal)', 'description' => 'DATEV-Programme auf jedem Arbeitsplatz, lokale Installation + Cache.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Reserve-Notebook mit DATEV-Image im IT-Schrank; sonst MSP-Hotline.', 'runbook_reference' => 'Runbook „DATEV-Arbeitsplatz neu aufsetzen" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['datev_cloud'], 'name' => 'DATEV-Rechenzentrum-Anbindung (Cloud)', 'description' => 'Mandantenbestände im DATEV-Rechenzentrum, lokal nur Arbeits-Cache.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'DATEV-Status-Seite prüfen; DATEV-Notfall-Hotline anrufen; Mandanten informieren, dass Bearbeitung pausiert.', 'runbook_reference' => 'Runbook „DATEV-RZ-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['datev_komm'], 'name' => 'DATEV E-Mail (DATEVnet/Komm)', 'description' => 'Geschäftliche E-Mail über DATEV.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'DATEV-Webmail; in Ausnahmefällen private Mobilnummer der Partner für eilige Mandanten.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['belegerfassung'], 'name' => 'Belegerfassungs-Scanner', 'description' => 'Hochleistungs-Scanner Empfang + zwei Tisch-Scanner; Anbindung DATEV Belege online.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Belege physisch sammeln, Mandanten informieren über verzögerte Erfassung; Smartphone-App als Notlösung.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['mandantenportal'], 'name' => 'DATEV Mandanten-Portal (Unternehmen online)', 'description' => 'Online-Plattform für Belegaustausch mit Mandanten.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 120, 'fallback_process' => 'Mandanten per Mail / Telefon informieren; verschlüsselter USB-Stick-Austausch im Empfang.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefon'], 'name' => 'Telefonanlage (VoIP)', 'description' => 'Cloud-Telefonanlage + Durchwahlen, Anrufweiterleitung auf Mobil.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Anrufweiterleitung Hauptnummer auf Mobil Sekretariat; Notfall-Mobilnummer auf Webseite.', 'runbook_reference' => 'Runbook „VoIP-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['drucker'], 'name' => 'Drucker / Multifunktionsgeräte', 'description' => 'MFP Empfang + 2 Etagen-Drucker, Folgekosten-Vertrag.', 'category' => 'basisbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 30, 'fallback_process' => 'Reserve-Drucker im Lager; Hersteller-Hotline.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['fileserver'], 'name' => 'Datei-Server / Mandanten-Laufwerk', 'description' => 'Lokaler Server für Mandanten-Dokumente außerhalb DATEV (PDFs, Schriftverkehr).', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Restore aus Offline-Backup-USB; Mandanten-Briefe pausieren.', 'runbook_reference' => 'Runbook „Server-Restore" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['alarm'], 'name' => 'Alarm- und Zutrittsanlage', 'description' => 'Einbruchmeldeanlage + Zutritts-Codes Kanzlei + Tresor.', 'category' => 'sicherheit', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Wachdienst beauftragen; Schließanlage manuell.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['datev_arbeitsplatz'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['datev_cloud'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'DATEV-RZ ohne Internet nicht erreichbar.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['datev_arbeitsplatz'], 'depends_on_system_id' => $sys['datev_cloud'], 'sort' => 1, 'note' => 'Programmstart prüft Lizenz/RZ-Verbindung.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['datev_komm'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['belegerfassung'], 'depends_on_system_id' => $sys['datev_cloud'], 'sort' => 0, 'note' => 'Belege landen in Belege online (DATEV-RZ).', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['mandantenportal'], 'depends_on_system_id' => $sys['datev_cloud'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['fileserver'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['alarm'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'professional_liability', 'insurer' => 'HDI Versicherung AG', 'policy_number' => 'STB-2026-11223', 'hotline' => '0511 6450', 'email' => 'schaden@hdi.example', 'reporting_window' => 'unverzüglich, spätestens 7 Tage', 'contact_name' => 'Frau Konrad', 'notes' => 'Berufshaftpflicht Steuerberater, Deckung 2 Mio. €.', 'deductible' => '5.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'CyberSchutz24 AG', 'policy_number' => 'CY-2026-7788', 'hotline' => '0800 8765432', 'email' => 'schaden@cyberschutz24.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Herr Lindner', 'notes' => 'Erweiterung um Verschwiegenheits-Klausel. Deckung 1 Mio. €.', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-44556', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 60 Tage, inkl. Mehrkosten Ersatz-Arbeitsplätze.', 'deductible' => '1.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Mandanten-Info bei Systemausfall (E-Mail)', 'audience' => 'customers', 'channel' => 'email', 'subject' => 'Vorübergehende Bearbeitungsverzögerung in unserer Kanzlei', 'body' => "Sehr geehrte Mandantin, sehr geehrter Mandant,\n\naufgrund einer technischen Störung kann es bei {{ firma }} aktuell zu Verzögerungen in der Bearbeitung Ihrer Belege und Anfragen kommen.\n\nSie erreichen uns weiterhin telefonisch unter 0711 2244660 oder im Notfall per Mobil 0171 5566778.\n\nBitte senden Sie keine vertraulichen Unterlagen als unverschlüsselte E-Mail.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Wenn DATEV-Komm betroffen: Versand über private GMX-Mail des Partners NICHT zulässig — stattdessen Anruf.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DATEV-Störungsmeldung (Telefon-Skript)', 'audience' => 'service_providers', 'channel' => 'phone', 'subject' => null, 'body' => "Skript für Anruf bei DATEV-Notfall-Hotline (0911 319-0):\n\n1. Berater-Nummer und Mitgliedsnummer nennen.\n2. Symptom kurz schildern (z. B. \"Mandantenbestand X öffnet nicht, Fehlermeldung Y\").\n3. Erfragen: Ticket-Nummer, geschätzte Lösungszeit, ob bekannt-betroffene Komponente.\n4. Ticket-Nummer im DATEV-Service-Portal nachverfolgen.\n5. Ergebnis im Notfall-Logbuch dokumentieren.", 'fallback' => 'Berater-Nummer hängt am Empfang + liegt im Tresor.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung an LfDI', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit meldet {{ firma }} gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten.\n\nVerantwortlicher: {{ firma }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\nBetroffene Datenkategorien: Mandantenstammdaten / steuerliche Unterlagen.\n\nGesondert beachten wir die berufsständische Verschwiegenheitspflicht nach § 57 StBerG.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Mitarbeiter-Erstmeldung (SMS)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'Wichtig: Bei {{ firma }} liegt aktuell eine technische Störung vor. Keine USB-Sticks anstecken, keine verdächtigen Mails öffnen, Mandanten zunächst auf morgen vertrösten. Weitere Anweisungen über Christoph Vogel (0171 5566780). Stand: {{ zeitpunkt }}.', 'fallback' => 'Aushang am Empfang.', 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Meldung an Steuerberaterkammer', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Information zu sicherheitsrelevantem Vorfall – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nim Zuge unserer berufsrechtlichen Sorgfaltspflichten informieren wir Sie hiermit über einen sicherheitsrelevanten Vorfall in unserer Kanzlei.\n\nKanzlei: {{ firma }}\nZeitpunkt: {{ zeitpunkt }}\nKurzbeschreibung: {{ vorfall }}\nGetroffene Maßnahmen: Notfallplan aktiviert, Mandanten informiert, IT-Forensik beauftragt.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'DATEV-Notfall-Hotline-Karte', 'description' => 'Laminierte Karte mit Berater-Nr., Mitgliedsnr., Hotline-Nummer.', 'location' => 'Empfang (sichtbar) + Tresor (Backup)', 'access_holders' => 'Andreas Schmidt, Birgit Lehmann, Heike Weber', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => 'Bei Personalwechsel sofort prüfen.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Verschlüsselter USB-Stick (Mandantenstammdaten)', 'description' => 'BitLocker-verschlüsselter USB-Stick mit Mandanten-Stammdaten + Vollmachten als PDF.', 'location' => 'Tresor Kanzlei + Bankschließfach Volksbank', 'access_holders' => 'Andreas Schmidt, Birgit Lehmann', 'last_check_at' => Helpers::date(-21), 'next_check_at' => Helpers::date(70), 'notes' => 'Quartalsweise Aktualisierung. Passwort separat im Tresor.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Reserve-Notebook mit DATEV-Image', 'description' => 'Lenovo ThinkPad mit lauffähigem DATEV-Arbeitsplatz-Image.', 'location' => 'IT-Schrank Kanzlei', 'access_holders' => 'Ingo Reinhardt, Christoph Vogel', 'last_check_at' => Helpers::date(-45), 'next_check_at' => Helpers::date(45), 'notes' => 'Quartalsweise Updates über MSP.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_cash', 'name' => 'Notfallkasse Empfang', 'description' => '1.000 € in Scheinen für Express-Drucker, Kurier, Schloss-Notdienst.', 'location' => 'Tresor Empfang', 'access_holders' => 'Andreas Schmidt, Heike Weber', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion mit Telefonliste, Playbooks, DATEV-Zugangsweg.', 'location' => '1× Empfang, 1× Partner-Büro, 1× Privat Schmidt', 'access_holders' => 'Partner, Notfallbeauftragter, IT-Lead', 'last_check_at' => Helpers::date(-10), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Prepaid-SIM mit Hotspot', 'description' => 'Telekom Prepaid 100GB für Notfall-Tethering DATEV-Cloud.', 'location' => 'Schreibtisch Sekretariat', 'access_holders' => 'Heike Weber, Ingo Reinhardt', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste', 'description' => 'Erreichbarkeit aller Krisenrollen + DATEV-Hotline + MSP prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['sekretariat'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop „DATEV-RZ-Ausfall"', 'description' => 'Schreibtisch-Übung: 4h DATEV-RZ nicht erreichbar in der Lohn-Hochphase.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-200), 'next_due_at' => Helpers::date(165), 'responsible_employee_id' => $emp['stb_angestellt'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Datei-Server', 'description' => 'Voll-Restore aus Offline-Backup auf Test-Server.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['it_extern'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Phishing-Awareness Mandanten-Mail', 'description' => 'Gefälschte „Bitte um Vollmacht"-Mail an Belegschaft, Klick + Reaktionszeit messen.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-90), 'next_due_at' => Helpers::date(275), 'responsible_employee_id' => $emp['it_extern'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Notfallkette Mandanteninformation', 'description' => 'Test: Innerhalb von 2h sind die Top-20 Mandanten per Mail/Telefon informiert.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(305), 'responsible_employee_id' => $emp['sekretariat'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'USV-Akku-Test', 'description' => 'Last simulieren, Laufzeit dokumentieren.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'LTE-Failover scharf testen', 'description' => 'Glasfaser physisch trennen, prüfen ob DATEV-Cloud erreichbar bleibt.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['datev_arbeitsplatz'], 'title' => 'DATEV-Updates auf Reserve-Notebook einspielen', 'description' => 'Notebook hochfahren, alle DATEV-Updates ziehen, Funktions-Smoke-Test.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['datev_cloud'], 'title' => 'DATEV-Berater-Nr. + Hotline-Karte verifizieren', 'description' => 'Karte am Empfang prüfen, Tresor-Backup prüfen, Eintrag im Handbuch abgleichen.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['datev_komm'], 'title' => 'Phishing-Awareness DATEV-Mails', 'description' => 'Gefälschte „DATEV-Update"-Mail an Belegschaft, Reaktion messen.', 'due_date' => Helpers::date(75), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['mandantenportal'], 'title' => 'Berechtigungs-Review DATEV Unternehmen online', 'description' => 'Welche Mandanten / interne Mitarbeiter haben welche Rechte? Ehemalige entfernen.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['fileserver'], 'title' => 'Restore-Test Mandanten-Laufwerk', 'description' => 'Stichproben-Restore von 10 Dateien aus Offline-Backup.', 'due_date' => Helpers::date(180), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['telefon'], 'title' => 'Anrufweiterleitung Sekretariat-Mobil testen', 'description' => 'Hauptnummer anrufen, Weiterleitung auf Sekretariats-Mobil prüfen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
