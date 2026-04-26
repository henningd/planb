<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class HeizungSanitaerTemplate implements Contract
{
    public function name(): string
    {
        return 'Heizung-/Sanitärbetrieb (8–20 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Handwerk;
    }

    public function description(): string
    {
        return 'Mittelständischer SHK-Betrieb (Heizung, Sanitär, Klima) mit Hauptsitz und Außenlager. Schwerpunkt Kundendienst, Heizungsmodernisierung, 24/7-Bereitschaft. Enthält: 11 Mitarbeiter mit Krisenrollen, 2 Standorte, 11 Systeme inkl. RTO/RPO (Telefon-Hotline kritisch wegen Bereitschaftspflicht), 7 Dienstleister inkl. SHK-Großhandel, Versicherungen, Notfallvorlagen für Heizungsausfall und Lieferanten-Eskalation, Testplan.';
    }

    public function sort(): int
    {
        return 20;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $hauptsitz = Helpers::uuid();
        $aussenlager = Helpers::uuid();

        $emp = [
            'gf' => Helpers::uuid(),
            'prokura' => Helpers::uuid(),
            'buero' => Helpers::uuid(),
            'meister' => Helpers::uuid(),
            'kdleitung' => Helpers::uuid(),
            'monteur1' => Helpers::uuid(),
            'monteur2' => Helpers::uuid(),
            'monteur3' => Helpers::uuid(),
            'lager' => Helpers::uuid(),
            'dsb' => Helpers::uuid(),
            'buchhaltung' => Helpers::uuid(),
        ];

        $prov = [
            'it' => Helpers::uuid(),
            'isp' => Helpers::uuid(),
            'utility' => Helpers::uuid(),
            'shk' => Helpers::uuid(),
            'hersteller' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
            'kanzlei' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'netzwerk' => Helpers::uuid(),
            'telefon' => Helpers::uuid(),
            'server' => Helpers::uuid(),
            'email' => Helpers::uuid(),
            'branchensw' => Helpers::uuid(),
            'dispo' => Helpers::uuid(),
            'tablets' => Helpers::uuid(),
            'lager' => Helpers::uuid(),
            'alarm' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Müller Heizung & Sanitär GmbH',
                'industry' => 'handwerk',
                'employee_count' => 14,
                'locations_count' => 2,
                'review_cycle_months' => 12,
                'legal_form' => 'gmbh',
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
                    'id' => $hauptsitz, 'name' => 'Hauptsitz', 'street' => 'Heizungsweg 7',
                    'postal_code' => '70565', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 4567890',
                    'notes' => 'Geschäftsführung, Büro, Disposition, kleine Werkstatt, Empfangs-Tresen Kundendienst.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
                [
                    'id' => $aussenlager, 'name' => 'Außenlager / Zentrallager', 'street' => 'Industriestraße 88',
                    'postal_code' => '70794', 'city' => 'Filderstadt', 'country' => 'DE',
                    'is_headquarters' => 0, 'phone' => '0711 4567899',
                    'notes' => 'Zentrales Materiallager (Heizkessel, Heizkörper, Rohrmaterial), Anlieferung, Monteur-Treffpunkt morgens.',
                    'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['gf'], 'first_name' => 'Thomas', 'last_name' => 'Müller', 'position' => 'Geschäftsführer / Heizungsbaumeister', 'department' => 'Geschäftsführung', 'work_phone' => null, 'mobile_phone' => '0171 4561111', 'private_phone' => '0711 6677889', 'email' => 'thomas.mueller@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['prokura'], 'first_name' => 'Birgit', 'last_name' => 'Müller', 'position' => 'Prokuristin / kfm. Leitung', 'department' => 'Geschäftsführung', 'work_phone' => null, 'mobile_phone' => '0171 4561112', 'private_phone' => null, 'email' => 'birgit.mueller@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buero'], 'first_name' => 'Sandra', 'last_name' => 'Hartmann', 'position' => 'Büro-/Disposition', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0171 4562223', 'private_phone' => '0711 7766554', 'email' => 'hartmann@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['prokura'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Hauptansprechpartnerin Disposition + Bereitschaftsplan.', 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['meister'], 'first_name' => 'Andreas', 'last_name' => 'Becker', 'position' => 'Werkstattleitung / Heizungsbauer-Meister', 'department' => 'Werkstatt', 'work_phone' => null, 'mobile_phone' => '0171 4563334', 'private_phone' => null, 'email' => 'becker@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['kdleitung'], 'first_name' => 'Markus', 'last_name' => 'Schäfer', 'position' => 'Leitung Kundendienst', 'department' => 'Kundendienst', 'work_phone' => null, 'mobile_phone' => '0171 4564445', 'private_phone' => '07154 998877', 'email' => 'schaefer@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Koordiniert Kundendienst-Touren + IT-Themen (Tablets, Software).', 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['monteur1'], 'first_name' => 'Stefan', 'last_name' => 'Vogel', 'position' => 'Kundendienst-Monteur', 'department' => 'Kundendienst', 'work_phone' => null, 'mobile_phone' => '0171 4565556', 'private_phone' => null, 'email' => 'vogel@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['kdleitung'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'Bereitschaft Wochenende A.', 'location_id' => $aussenlager, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['monteur2'], 'first_name' => 'Patrick', 'last_name' => 'Weber', 'position' => 'Kundendienst-Monteur', 'department' => 'Kundendienst', 'work_phone' => null, 'mobile_phone' => '0171 4566667', 'private_phone' => null, 'email' => 'weber@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['kdleitung'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'Bereitschaft Wochenende B.', 'location_id' => $aussenlager, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['monteur3'], 'first_name' => 'Daniel', 'last_name' => 'Krause', 'position' => 'Anlagenmechaniker SHK', 'department' => 'Werkstatt', 'work_phone' => null, 'mobile_phone' => '0171 4567778', 'private_phone' => null, 'email' => 'krause@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['meister'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $aussenlager, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['lager'], 'first_name' => 'Heiko', 'last_name' => 'Lange', 'position' => 'Lagerleitung', 'department' => 'Lager', 'work_phone' => null, 'mobile_phone' => '0171 4568889', 'private_phone' => null, 'email' => 'lange@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['prokura'], 'is_key_personnel' => 1, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Kontakt zu SHK-Großhandel, Bestellannahme, Wareneingang.', 'location_id' => $aussenlager, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb'], 'first_name' => 'Carla', 'last_name' => 'Wagner', 'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 4569990', 'private_phone' => null, 'email' => 'wagner@datenschutz-extern.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung'], 'first_name' => 'Petra', 'last_name' => 'Roth', 'position' => 'Buchhaltung', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0171 4560001', 'private_phone' => null, 'email' => 'roth@mueller-shk.de', 'emergency_contact' => null, 'manager_id' => $emp['prokura'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hauptsitz, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['it'], 'name' => 'HandwerksIT Süd GmbH', 'type' => 'it_msp', 'contact_name' => 'Frank Berger', 'hotline' => '0711 9988770', 'email' => 'support@handwerks-it.example', 'contract_number' => 'WV-2024-188', 'sla' => 'Mo-Fr 7-19, Notfall 24/7 (Aufpreis)', 'notes' => 'Server, Tablets, Branchensoftware. Auf Handwerk spezialisiert.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['isp'], 'name' => 'TelCo Deutschland AG', 'type' => 'internet_provider', 'contact_name' => 'Störungsstelle Geschäftskunden', 'hotline' => '0800 3300000', 'email' => 'stoerung-gk@telco.example', 'contract_number' => 'GK-554433', 'sla' => '24/7 (4h Reaktion)', 'notes' => 'Glasfaser 250/100, statische IP, kritisch wegen Bereitschaftsdienst-Hotline.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['utility'], 'name' => 'Stadtwerke Stuttgart', 'type' => 'utility', 'contact_name' => 'Entstörungsdienst', 'hotline' => '0711 289-2222', 'email' => null, 'contract_number' => 'Z-44556', 'sla' => '24/7', 'notes' => 'Strom + Gas. Bei Ausfall öffentlichen Störungsmelder prüfen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['shk'], 'name' => 'GC-Gruppe SHK-Großhandel', 'type' => 'other', 'contact_name' => 'Niederlassungsleitung Stuttgart', 'hotline' => '0711 7711330', 'email' => 'stuttgart@gc-gruppe.example', 'contract_number' => 'KD-77665', 'sla' => 'Mo-Fr 6-18, Sa 7-12', 'notes' => 'KRITISCHER Lieferant: Heizkessel, Heizkörper, Rohrmaterial. Bei Lieferengpass Eskalation an Niederlassungsleitung. Notfall-Abholung möglich.', 'direct_order_limit' => 10000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['hersteller'], 'name' => 'Viessmann Service-Hotline', 'type' => 'other', 'contact_name' => 'Werkskundendienst', 'hotline' => '0800 8484200', 'email' => 'service@viessmann.example', 'contract_number' => 'PARTNER-22118', 'sla' => '24/7', 'notes' => 'Hersteller-Servicestelle für Heizungstechnik (Brennwert, Wärmepumpe). Ersatzteile, Garantieabwicklung, Eskalation Werkskundendienst.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['kanzlei'], 'name' => 'Kanzlei Recht & Co.', 'type' => 'other', 'contact_name' => 'RA Hoffmann', 'hotline' => '0711 9988770', 'email' => 'hoffmann@recht-co.example', 'contract_number' => 'M-2025-44', 'sla' => 'Mo-Fr 9-17', 'notes' => 'Wirtschafts- und IT-Recht. Bei Datenpanne / Ransomware sofort.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Hauptsitz', 'description' => 'Hausanschluss + Verteilung. USV nur für Server + Telefon.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'USV überbrückt 30 Min.; bei längerem Ausfall Bereitschafts-Hotline auf Mobilnummer umleiten.', 'runbook_reference' => 'Runbook „Stromausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang Hauptsitz', 'description' => 'Glasfaser GK 250/100 mit fester IP. Zwingend für VoIP-Hotline.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Mobiler LTE-Router an Telefonanlage; Bereitschafts-Hotline auf Mobilnummer Kundendienstleitung umleiten.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['netzwerk'], 'name' => 'Netzwerk / WLAN', 'description' => 'LAN + WLAN, Switch im Server-Schrank, eigenes Gäste-WLAN.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Switch-Reset; Backup-Switch im IT-Schrank einsetzen.', 'runbook_reference' => 'Runbook „Netzwerk-Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefon'], 'name' => 'Telefon-Hotline / VoIP-Anlage', 'description' => 'Cloud-Telefonanlage mit Bereitschafts-Routing (Tag/Nacht/Wochenende).', 'category' => 'basisbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'KRITISCH wegen Bereitschaftspflicht: Sofort Rufumleitung auf Bereitschafts-Mobiltelefon (siehe Notfall-Ressourcen) + Ansage „Im Notfall xy anrufen" auf Anrufbeantworter umstellen.', 'runbook_reference' => 'Runbook „Telefon-Hotline-Ausfall" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['server'], 'name' => 'Büro-Server / Zentralrechner', 'description' => 'Lokaler Server für Datei-Ablage, Druck, AD, Branchen-DB.', 'category' => 'basisbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'Manueller Betrieb auf Papier-Auftragsblock; Zugriff auf Offline-Backup-USB für Stammdaten.', 'runbook_reference' => 'Runbook „Server-Restore" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['email'], 'name' => 'E-Mail (M365)', 'description' => 'Microsoft 365 Business Standard.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'M365-Webmail (mobil) als Fallback; kritische Mails per SMS bestätigen.', 'runbook_reference' => 'Runbook „M365 Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['branchensw'], 'name' => 'Branchensoftware (pds / streit V.1)', 'description' => 'SHK-Branchenlösung: Aufmaß, Angebot, Auftrag, Stunden, Rechnung, Wartungsverträge.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Auftragsannahme telefonisch + Papier-Auftragsblock; Nacherfassung nach Wiederanlauf.', 'runbook_reference' => 'Runbook „Branchensoftware" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['dispo'], 'name' => 'Disposition / Tourenplanung', 'description' => 'Tourenplanungs-Software (Modul der Branchensoftware): Tages-Touren der Monteure, Auftragsverteilung.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 180, 'fallback_process' => 'Manuelle Tagesplanung am Whiteboard; Monteure morgens am Außenlager briefen.', 'runbook_reference' => 'Runbook „Dispo-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['tablets'], 'name' => 'Mobile Tablets (Monteure)', 'description' => 'Android-Tablets mit Branchen-App: Auftragsdetails, Aufmaß, Foto-Doku, Kunden-Unterschrift.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Papier-Auftragsblock + Foto mit privatem Smartphone; Reserve-Tablet aus Notfall-Ressourcen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['lager'], 'name' => 'Lagerverwaltung Außenlager', 'description' => 'Bestandsführung Materiallager (Heizkessel, Rohre, Fittinge), Barcode-Scanner.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 720, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 60, 'fallback_process' => 'Manuelle Bestandsführung auf Papier-Liste; Großhandel-Lieferung direkt zur Baustelle.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['alarm'], 'name' => 'Alarm- und Videoanlage', 'description' => 'Einbruchmeldeanlage Hauptsitz + Außenlager (Materiallager).', 'category' => 'unterstuetzend', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Manuelle Sichtkontrolle; Wachdienst informieren.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['netzwerk'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Bereitschafts-Hotline ist auf VoIP angewiesen.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['server'], 'depends_on_system_id' => $sys['netzwerk'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['email'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['branchensw'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['dispo'], 'depends_on_system_id' => $sys['branchensw'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['tablets'], 'depends_on_system_id' => $sys['branchensw'], 'sort' => 0, 'note' => 'Tablets synchronisieren via Internet mit Branchen-DB.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['lager'], 'depends_on_system_id' => $sys['branchensw'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['alarm'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'CyberSchutz24 AG', 'policy_number' => 'CY-2026-7788', 'hotline' => '0800 8765432', 'email' => 'schaden@cyberschutz24.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Frau Hartmann', 'notes' => 'Deckung 750.000 €. Inkludiert Forensik + BU bis 14 Tage.', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-66554', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 60 Tage, deckt auch Bereitschaftsdienst-Ausfälle.', 'deductible' => '1.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Erstmeldung Mitarbeiter (SMS)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'Wichtig: Bei {{ firma }} liegt aktuell eine Störung vor. Bitte keine E-Mails / Logins versuchen, keine USB-Sticks anstecken. Monteure bleiben bis auf Weiteres bei laufenden Aufträgen, neue Aufträge nur per Telefon von Sandra Hartmann (0171 4562223). Stand: {{ zeitpunkt }}.', 'fallback' => 'Aushang im Empfangsbereich + Außenlager.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Kunden-Info Heizungsausfall (Telefon-Skript)', 'audience' => 'customers', 'channel' => 'phone', 'subject' => null, 'body' => "Sehr geehrte/r Frau/Herr {{ name }},\n\nIhre Heizungsstörung ist bei uns angekommen. Aktuell haben wir bei {{ firma }} selbst eine technische Störung in der Disposition. Wir können Ihnen daher noch keinen festen Termin nennen.\n\nIm Notfall (Heizung im Winter komplett aus, Wasserschaden) bitte unsere Bereitschaftsnummer 0171 4564445 (Markus Schäfer) direkt anrufen — wir kommen vorbei, sobald wir können.\n\nIn allen anderen Fällen melden wir uns spätestens morgen mit einem Termin.\n\nMit freundlichen Grüßen", 'fallback' => 'SMS an Kundennummer aus Auftragsbuch.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Lieferanten-Eskalation SHK-Großhandel', 'audience' => 'service_providers', 'channel' => 'email', 'subject' => 'Eskalation Lieferengpass – Kundennr. {{ kundennummer }}', 'body' => "Sehr geehrte Damen und Herren,\n\nwir melden hiermit einen Lieferengpass mit erheblicher Auswirkung auf unseren Kundendienst.\n\nFirma: {{ firma }}\nKundennummer: {{ kundennummer }}\nBetroffenes Material: {{ material }}\nBenötigt für: laufenden Notfall-Kundendienst (Heizungsausfall)\nGewünschte Notfall-Abholung: {{ abholtermin }}\n\nBitte Rückmeldung an die Lagerleitung Heiko Lange (0171 4568889).\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Telefonische Eskalation an Niederlassungsleitung.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Aufsichtsbehörde', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit melden wir gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten.\n\nVerantwortlicher: {{ firma }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Bereitschafts-Umleitung (Aushang)', 'audience' => 'customers', 'channel' => 'notice', 'subject' => 'Bereitschaftsdienst aktuell unter Mobilnummer erreichbar', 'body' => "Liebe Kundinnen und Kunden,\n\nwegen einer aktuellen Störung unserer Telefonanlage erreichen Sie unseren Bereitschaftsdienst unter:\n\n0171 4564445 (Markus Schäfer)\n\nWir bitten um Ihr Verständnis.\n\nIhr Team {{ firma }}", 'fallback' => 'Aushang an Eingangstür Hauptsitz + Außenlager.', 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'emergency_cash', 'name' => 'Notfallkasse Empfang', 'description' => '750 € in Scheinen + Kleingeld für Material-Direktkauf bei Großhandel.', 'location' => 'Tresor Empfang, Hauptsitz', 'access_holders' => 'Sandra Hartmann, Thomas Müller, Birgit Müller', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Notfall-Werkzeugkoffer', 'description' => 'Komplett-Werkzeugkoffer SHK mit Standard-Reparaturteilen (Dichtungen, Fittings, Ventile, Heizungs-Notlauf-Set).', 'location' => 'Außenlager, Regalplatz Bereitschaft', 'access_holders' => 'Andreas Becker, Markus Schäfer, Bereitschaftsmonteur', 'last_check_at' => Helpers::date(-20), 'next_check_at' => Helpers::date(70), 'notes' => 'Wird nach jedem Bereitschafts-Wochenende kontrolliert + aufgefüllt.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Bereitschafts-Mobiltelefon', 'description' => 'Dediziertes Diensthandy für Bereitschafts-Hotline-Umleitung. Wird wöchentlich an den Bereitschaftsmonteur übergeben.', 'location' => 'Sandra Hartmann (Übergabe Mo morgens)', 'access_holders' => 'jeweiliger Bereitschaftsmonteur, Sandra Hartmann', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Akku + Ladekabel jeden Montag prüfen, Rufnummer ist auf Aushang Bereitschaftsplan vermerkt.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Offline-Backup (USB)', 'description' => 'Wöchentliches Offline-Backup, 2x 4TB rotierend.', 'location' => 'Tresor GF + Bankschließfach', 'access_holders' => 'Thomas Müller, Birgit Müller', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentliche Rotation Mo morgens.', 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Prepaid-SIM mit Hotspot (LTE-Router)', 'description' => 'Telekom Prepaid 50GB. Hotspot-fähig — wird im Internet-Ausfall an die VoIP-Anlage gehängt.', 'location' => 'IT-Schrank Hauptsitz', 'access_holders' => 'Markus Schäfer, Thomas Müller', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion inkl. Telefonliste, Bereitschaftsplan, Hersteller-Hotlines, Playbooks.', 'location' => '1× GF, 1× Empfang Hauptsitz, 1× Außenlager, 1× Privat GF', 'access_holders' => 'GF, Notfallbeauftragte/r, Kundendienstleitung', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste + Bereitschaftsplan', 'description' => 'Erreichbarkeit aller Krisenrollen + Vertretungen + Bereitschaftsmonteure prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['buero'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Ransomware', 'description' => 'Schreibtisch-Übung Ransomware-Befall, Versicherungs-Meldung, Eskalation an SHK-Großhandel.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(265), 'responsible_employee_id' => $emp['buero'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'recovery', 'name' => 'Wiederanlauf-Test Bereitschafts-Hotline-Umleitung', 'description' => 'Telefon-Hotline manuell abschalten, Umleitung auf Bereitschafts-Mobiltelefon scharf schalten, Test-Anruf.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-90), 'next_due_at' => Helpers::date(90), 'responsible_employee_id' => $emp['kdleitung'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Büro-Server', 'description' => 'Voll-Restore aus Offline-Backup auf Test-Server.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['kdleitung'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'SMS-Notfallkette', 'description' => 'Test-SMS an alle Mitarbeiter inkl. Monteure im Außendienst, Antwort innerhalb 60 Min.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(305), 'responsible_employee_id' => $emp['buero'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'USV-Akku-Test', 'description' => 'Last simulieren, Laufzeit dokumentieren.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'Failover auf LTE-Router prüfen', 'description' => 'Glasfaser-Kabel ziehen, LTE-Router an VoIP-Anlage hängen, Test-Anruf auf Bereitschafts-Hotline.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['telefon'], 'title' => 'Bereitschafts-Routing-Test', 'description' => 'Tag/Nacht/Wochenende-Routing der VoIP-Anlage durchspielen, Ansagen prüfen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['server'], 'title' => 'Restore-Test Offline-Backup', 'description' => 'Voll-Restore auf Test-Server, Branchen-DB-Konsistenz prüfen.', 'due_date' => Helpers::date(180), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['email'], 'title' => 'Phishing-Awareness-Übung', 'description' => 'Gefälschte Lieferanten-Mail an Belegschaft, Klickraten auswerten.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['branchensw'], 'title' => 'Versions-Update Branchensoftware', 'description' => 'Release Notes lesen, Sandbox-Test, Produktiv-Update; vorab Backup ziehen.', 'due_date' => Helpers::date(-7), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['dispo'], 'title' => 'Whiteboard-Notfall-Plan auffrischen', 'description' => 'Vorlage „Manuelle Tagesplanung" am Whiteboard im Außenlager auffrischen, Stifte prüfen.', 'due_date' => Helpers::date(40), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['tablets'], 'title' => 'Tablet-Inventur + Reserve-Tablet prüfen', 'description' => 'Alle Monteur-Tablets auf Software-Stand prüfen, Reserve-Tablet im Notfall-Werkzeugkoffer aktivieren.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['lager'], 'title' => 'Mindestbestand Bereitschafts-Material', 'description' => 'Bestand Notfall-Reparaturteile (Dichtungen, Fittings, Heizungs-Notlauf-Set) prüfen, ggf. nachbestellen.', 'due_date' => Helpers::date(20), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['alarm'], 'title' => 'Funktionsprüfung Alarmanlage Außenlager', 'description' => 'Sensoren testen, Wachdienst-Verbindung prüfen.', 'due_date' => Helpers::date(120), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
