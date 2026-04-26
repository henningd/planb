<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class ElektrobetriebTemplate implements Contract
{
    public function name(): string
    {
        return 'Elektrobetrieb (klein, 5–15 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Handwerk;
    }

    public function description(): string
    {
        return 'Kleiner Elektroinstallationsbetrieb mit Werkstatt + Lager. Schwerpunkt Hausinstallation, Smart-Home, Photovoltaik. Enthält: 9 Mitarbeiter mit Krisenrollen, 2 Standorte, 10 Systeme inkl. RTO/RPO, 6 Dienstleister, Versicherungen, Notfallvorlagen, Testplan.';
    }

    public function sort(): int
    {
        return 10;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $hauptsitz = Helpers::uuid();
        $werkstatt = Helpers::uuid();

        $emp = [
            'gf' => Helpers::uuid(),
            'prokura' => Helpers::uuid(),
            'buero' => Helpers::uuid(),
            'meister' => Helpers::uuid(),
            'it' => Helpers::uuid(),
            'dsb' => Helpers::uuid(),
            'marketing' => Helpers::uuid(),
            'buchhaltung' => Helpers::uuid(),
            'geselle' => Helpers::uuid(),
        ];

        $prov = [
            'it' => Helpers::uuid(),
            'isp' => Helpers::uuid(),
            'utility' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
            'bsi' => Helpers::uuid(),
            'kanzlei' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'netzwerk' => Helpers::uuid(),
            'telefon' => Helpers::uuid(),
            'server' => Helpers::uuid(),
            'email' => Helpers::uuid(),
            'cloud' => Helpers::uuid(),
            'erp' => Helpers::uuid(),
            'pos' => Helpers::uuid(),
            'alarm' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Elektro Mustermann GmbH',
                'industry' => 'handwerk',
                'employee_count' => 9,
                'locations_count' => 2,
                'review_cycle_months' => 12,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'not_affected',
                'cyber_insurance_deductible' => '1.500 €',
                'budget_it_lead' => 500,
                'budget_emergency_officer' => 2000,
                'budget_management' => 20000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
            ]],

            'locations' => [
                [
                    'id' => $hauptsitz, 'name' => 'Hauptsitz', 'street' => 'Musterstraße 1',
                    'postal_code' => '70173', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 1234567',
                    'notes' => 'Geschäftsführung, Büro, Empfang, Server-Schrank.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
                [
                    'id' => $werkstatt, 'name' => 'Werkstatt + Lager', 'street' => 'Industriestraße 42',
                    'postal_code' => '70565', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 0, 'phone' => '0711 2345678',
                    'notes' => 'Werkstatt, Materiallager, Auslieferung, Werkstatt-Büro.',
                    'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['gf'], 'first_name' => 'Max', 'last_name' => 'Mustermann', 'position' => 'Geschäftsführer', 'department' => 'Geschäftsführung', 'work_phone' => null, 'mobile_phone' => '0171 1234567', 'private_phone' => '07154 555666', 'email' => 'max@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['prokura'], 'first_name' => 'Sabine', 'last_name' => 'Mustermann', 'position' => 'Prokuristin', 'department' => 'Geschäftsführung', 'work_phone' => null, 'mobile_phone' => '0171 1234568', 'private_phone' => null, 'email' => 'sabine@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buero'], 'first_name' => 'Anna', 'last_name' => 'Beispiel', 'position' => 'Büroleitung', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0171 2345678', 'private_phone' => '0711 7778899', 'email' => 'anna@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['meister'], 'first_name' => 'Bernd', 'last_name' => 'Schneider', 'position' => 'Werkstattleitung / Meister', 'department' => 'Werkstatt', 'work_phone' => null, 'mobile_phone' => '0171 3456789', 'private_phone' => null, 'email' => 'bernd@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $werkstatt, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['it'], 'first_name' => 'Dieter', 'last_name' => 'Klein', 'position' => 'IT-Beauftragter (intern)', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0171 4567890', 'private_phone' => null, 'email' => 'dieter@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb'], 'first_name' => 'Carla', 'last_name' => 'Wagner', 'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 5678901', 'private_phone' => null, 'email' => 'wagner@datenschutz-extern.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['marketing'], 'first_name' => 'Eva', 'last_name' => 'Kommer', 'position' => 'Kommunikation / Web', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0171 6789012', 'private_phone' => null, 'email' => 'eva@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['buero'], 'is_key_personnel' => 0, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung'], 'first_name' => 'Tobias', 'last_name' => 'Fischer', 'position' => 'Buchhaltung', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0171 7890123', 'private_phone' => null, 'email' => 'tobias@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['buero'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['geselle'], 'first_name' => 'Jonas', 'last_name' => 'Müller', 'position' => 'Geselle', 'department' => 'Werkstatt', 'work_phone' => null, 'mobile_phone' => '0171 8901234', 'private_phone' => null, 'email' => 'jonas@elektro-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['meister'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $werkstatt, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['it'], 'name' => 'IT-Service GmbH', 'type' => 'it_msp', 'contact_name' => 'Peter Techniker', 'hotline' => '0800 1234567', 'email' => 'support@it-service.example', 'contract_number' => 'K-4711', 'sla' => 'Mo-Fr 8-18, Notfall 24/7', 'notes' => 'Server, Netzwerk, Arbeitsplätze. Hotline 24/7.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['isp'], 'name' => 'TelCo Deutschland AG', 'type' => 'internet_provider', 'contact_name' => 'Störungsstelle', 'hotline' => '0800 3300000', 'email' => 'stoerung@telco.example', 'contract_number' => 'GK-998877', 'sla' => '24/7', 'notes' => 'Glasfaser 200/100, statische IP.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['utility'], 'name' => 'Stadtwerke Stuttgart', 'type' => 'utility', 'contact_name' => 'Entstörungsdienst', 'hotline' => '0711 289-2222', 'email' => null, 'contract_number' => 'Z-87654', 'sla' => '24/7', 'notes' => 'Strom + Gas. Bei Ausfall öffentlichen Störungsmelder prüfen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['bsi'], 'name' => 'BSI Meldestelle', 'type' => 'bsi_reporting_office', 'contact_name' => 'Bürgertelefon', 'hotline' => '0228 99 9582-0', 'email' => 'meldestelle@bsi.bund.de', 'contract_number' => null, 'sla' => 'Mo-Fr 9-15', 'notes' => 'Sicherheitsvorfälle nach NIS2 / IT-SiG.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['kanzlei'], 'name' => 'Kanzlei Recht & Co.', 'type' => 'other', 'contact_name' => 'RA Hoffmann', 'hotline' => '0711 9988770', 'email' => 'hoffmann@recht-co.example', 'contract_number' => 'M-2025-12', 'sla' => 'Mo-Fr 9-17', 'notes' => 'Wirtschafts- und IT-Recht. Bei Datenpanne / Ransomware sofort.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Hauptsitz', 'description' => 'Hausanschluss + Verteilung. USV nur für Server.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'USV überbrückt 30 Min.; Generator anwerfen oder kontrollierter Shutdown.', 'runbook_reference' => 'Runbook „Stromausfall" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang Hauptsitz', 'description' => 'Glasfaser GK 200/100 mit fester IP.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Mobile Hotspots aus Notfall-SIM aktivieren; LTE-Router an kritischen Arbeitsplätzen.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['netzwerk'], 'name' => 'Netzwerk / WLAN', 'description' => 'LAN + WLAN, Switch im Server-Schrank.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Switch-Reset; Backup-Switch im IT-Schrank einsetzen.', 'runbook_reference' => 'Runbook „Netzwerk-Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefon'], 'name' => 'Telefonanlage (VoIP)', 'description' => 'Cloud-Telefonanlage, Festnetz-Bündel.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 50, 'fallback_process' => 'Notfall-Rufnummer (Mobil GF) als Kunden-Hotline kommunizieren.', 'runbook_reference' => 'Runbook „VoIP-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['server'], 'name' => 'Büro-Server / Zentralrechner', 'description' => 'Lokaler Server für Datei-Ablage, Druck, AD.', 'category' => 'basisbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'Manueller Betrieb auf Papier; Zugriff auf Offline-Backup-USB für Stammdaten.', 'runbook_reference' => 'Runbook „Server-Restore" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['email'], 'name' => 'E-Mail (M365)', 'description' => 'Microsoft 365 Business Standard.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'M365-Webmail (mobil) als Fallback; kritische Mails per SMS bestätigen.', 'runbook_reference' => 'Runbook „M365 Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['cloud'], 'name' => 'OneDrive / Cloud-Ablage', 'description' => 'Geteilte Projekt- und Vertragsablage.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 60, 'fallback_process' => 'Lokale Kopien auf Offline-Backup-USB; OneDrive Web-Login als Fallback.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['erp'], 'name' => 'Handwerkersoftware (Auftragsabwicklung)', 'description' => 'Branchenlösung: Auftrag → Stunden → Rechnung.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'Auftragsannahme telefonisch + Papier-Auftragsblock; Nacherfassung nach Wiederanlauf.', 'runbook_reference' => 'Runbook „Handwerkersoftware" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['pos'], 'name' => 'Kartenterminal / Zahlung', 'description' => 'EC-Kartenterminal an der Annahme.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 30, 'fallback_process' => 'Hinweisschild „Nur Bargeld"; Anbieter-Hotline kontaktieren.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['alarm'], 'name' => 'Alarm- und Videoanlage', 'description' => 'Einbruchmeldeanlage + Videoüberwachung Werkstatt.', 'category' => 'sicherheit', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Manuelle Sichtkontrolle; Wachdienst informieren.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['netzwerk'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['server'], 'depends_on_system_id' => $sys['netzwerk'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['email'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cloud'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['erp'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['pos'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['alarm'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'CyberSchutz24 AG', 'policy_number' => 'CY-2026-4711', 'hotline' => '0800 8765432', 'email' => 'schaden@cyberschutz24.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Frau Hartmann', 'notes' => 'Deckung 500.000 €.', 'deductible' => '1.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-99887', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 30 Tage.', 'deductible' => '500 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Erstmeldung Mitarbeiter (SMS)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'Wichtig: Bei {{ firma }} liegt aktuell eine Störung vor. Bitte keine E-Mails / Logins versuchen, keine USB-Sticks anstecken. Weisungen folgen über Anna Beispiel (0171 2345678). Stand: {{ zeitpunkt }}.', 'fallback' => 'Aushang im Empfangsbereich + Werkstatt.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Kunden-Information (E-Mail)', 'audience' => 'customers', 'channel' => 'email', 'subject' => 'Kurzfristige Einschränkung der Erreichbarkeit', 'body' => "Sehr geehrte Damen und Herren,\n\naufgrund einer technischen Störung sind wir bei {{ firma }} aktuell eingeschränkt erreichbar.\n\nUnter der Notfallnummer 0171 1234567 sind wir für Sie da.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Anruf an Bestandskunden.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Aufsichtsbehörde', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit melden wir gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten.\n\nVerantwortlicher: {{ firma }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'emergency_cash', 'name' => 'Notfallkasse Empfang', 'description' => '500 € in Scheinen + Kleingeld.', 'location' => 'Tresor Empfang, Hauptsitz', 'access_holders' => 'Anna Beispiel, Max Mustermann', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Ersatz-Notebook', 'description' => 'Lenovo ThinkPad mit Standard-Image.', 'location' => 'IT-Schrank, Werkstatt', 'access_holders' => 'Dieter Klein, IT-Service GmbH', 'last_check_at' => Helpers::date(-50), 'next_check_at' => Helpers::date(40), 'notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Offline-Backup (USB)', 'description' => 'Wöchentliches Offline-Backup, 2x 4TB rotierend.', 'location' => 'Tresor GF + Bankschließfach', 'access_holders' => 'Max Mustermann, Anna Beispiel', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentliche Rotation Mo morgens.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Prepaid-SIM mit Hotspot', 'description' => 'Telekom Prepaid 50GB. Hotspot-fähig.', 'location' => 'GF-Schreibtisch', 'access_holders' => 'Max Mustermann, Sabine Mustermann', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion inkl. Telefonliste, Playbooks.', 'location' => '1× GF, 1× Werkstatt, 1× Privat GF', 'access_holders' => 'GF, Notfallbeauftragte/r, IT-Lead', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste', 'description' => 'Erreichbarkeit aller Krisenrollen + Vertretungen prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['buero'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Ransomware', 'description' => 'Schreibtisch-Übung Ransomware-Befall, Versicherungs-Meldung.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(265), 'responsible_employee_id' => $emp['buero'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Büro-Server', 'description' => 'Voll-Restore aus Offline-Backup auf Test-Server.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['it'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'SMS-Notfallkette', 'description' => 'Test-SMS an alle Mitarbeiter, Antwort innerhalb 30 Min.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(305), 'responsible_employee_id' => $emp['buero'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'USV-Akku-Test', 'description' => 'Last simulieren, Laufzeit dokumentieren.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'Failover auf Mobil-Hotspot prüfen', 'description' => 'Kabel ziehen, Failover messen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['server'], 'title' => 'Restore-Test Offline-Backup', 'description' => 'Voll-Restore auf Test-Server.', 'due_date' => Helpers::date(180), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['email'], 'title' => 'Phishing-Awareness-Übung', 'description' => 'Gefälschte Mail an Belegschaft, Klickraten auswerten.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['cloud'], 'title' => 'Zugangsrechte-Review', 'description' => 'Wer hat noch Zugang? Ehemalige entfernen.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['erp'], 'title' => 'Versions-Update einspielen', 'description' => 'Release Notes lesen, Sandbox-Test, Produktiv-Update.', 'due_date' => Helpers::date(-7), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
