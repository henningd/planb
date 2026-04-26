<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class EinzelhandelTemplate implements Contract
{
    public function name(): string
    {
        return 'Einzelhandel (Filiale + Lager, 10–25 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Handel;
    }

    public function description(): string
    {
        return 'Stationärer Einzelhandel mit Filiale (Kasse, Verkaufsfläche) und angeschlossenem Zentrallager. Schwerpunkt Wohnaccessoires und Möbel. Enthält: 10 Mitarbeiter mit Krisenrollen, 2 Standorte, 11 Systeme inkl. Kassen/TSE und Kartenterminal, 6 Dienstleister, Versicherungen, Notfallvorlagen, Testplan.';
    }

    public function sort(): int
    {
        return 30;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $filiale = Helpers::uuid();
        $lager = Helpers::uuid();

        $emp = [
            'gf' => Helpers::uuid(),
            'filiale' => Helpers::uuid(),
            'stellv' => Helpers::uuid(),
            'verkauf1' => Helpers::uuid(),
            'verkauf2' => Helpers::uuid(),
            'lagerist' => Helpers::uuid(),
            'buchhaltung' => Helpers::uuid(),
            'it' => Helpers::uuid(),
            'dsb' => Helpers::uuid(),
            'azubi' => Helpers::uuid(),
        ];

        $prov = [
            'pos' => Helpers::uuid(),
            'payment' => Helpers::uuid(),
            'lieferant' => Helpers::uuid(),
            'msp' => Helpers::uuid(),
            'isp' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'wlan' => Helpers::uuid(),
            'kasse' => Helpers::uuid(),
            'terminal' => Helpers::uuid(),
            'wawi' => Helpers::uuid(),
            'edi' => Helpers::uuid(),
            'etiketten' => Helpers::uuid(),
            'email' => Helpers::uuid(),
            'alarm' => Helpers::uuid(),
            'webshop' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Schöngeist Wohnen GmbH',
                'industry' => 'handel',
                'employee_count' => 14,
                'locations_count' => 2,
                'review_cycle_months' => 12,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'not_affected',
                'cyber_insurance_deductible' => '2.500 €',
                'budget_it_lead' => 800,
                'budget_emergency_officer' => 2500,
                'budget_management' => 25000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
            ]],

            'locations' => [
                [
                    'id' => $filiale, 'name' => 'Filiale Innenstadt', 'street' => 'Königstraße 24',
                    'postal_code' => '70173', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 2233440',
                    'notes' => 'Verkaufsfläche, 2 Kassenplätze, kleines Backoffice, GF-Büro.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
                [
                    'id' => $lager, 'name' => 'Zentrallager', 'street' => 'Heilbronner Straße 220',
                    'postal_code' => '70469', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 0, 'phone' => '0711 2233460',
                    'notes' => 'Wareneingang, Lager, Kommissionierung, Versand an Filiale.',
                    'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['gf'], 'first_name' => 'Carolin', 'last_name' => 'Wendler', 'position' => 'Geschäftsführerin', 'department' => 'Geschäftsführung', 'work_phone' => null, 'mobile_phone' => '0172 4455660', 'private_phone' => '07151 887766', 'email' => 'wendler@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $filiale, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['filiale'], 'first_name' => 'Stefanie', 'last_name' => 'Brenner', 'position' => 'Filialleitung', 'department' => 'Verkauf', 'work_phone' => null, 'mobile_phone' => '0172 4455661', 'private_phone' => null, 'email' => 'brenner@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $filiale, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['stellv'], 'first_name' => 'Markus', 'last_name' => 'Hoffmann', 'position' => 'stellv. Filialleitung', 'department' => 'Verkauf', 'work_phone' => null, 'mobile_phone' => '0172 4455662', 'private_phone' => null, 'email' => 'hoffmann@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => $emp['filiale'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $filiale, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['verkauf1'], 'first_name' => 'Lisa', 'last_name' => 'Krämer', 'position' => 'Verkäuferin', 'department' => 'Verkauf', 'work_phone' => null, 'mobile_phone' => '0172 4455663', 'private_phone' => null, 'email' => 'kraemer@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => $emp['filiale'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $filiale, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['verkauf2'], 'first_name' => 'Sven', 'last_name' => 'Adler', 'position' => 'Verkäufer', 'department' => 'Verkauf', 'work_phone' => null, 'mobile_phone' => '0172 4455664', 'private_phone' => null, 'email' => 'adler@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => $emp['filiale'], 'is_key_personnel' => 0, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Zuständig für Kunden-Aushänge und Social-Media-Posts.', 'location_id' => $filiale, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['lagerist'], 'first_name' => 'Daniel', 'last_name' => 'Pohl', 'position' => 'Lagerleitung', 'department' => 'Lager', 'work_phone' => null, 'mobile_phone' => '0172 4455665', 'private_phone' => null, 'email' => 'pohl@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $lager, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung'], 'first_name' => 'Angela', 'last_name' => 'Schuster', 'position' => 'Buchhaltung', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0172 4455666', 'private_phone' => null, 'email' => 'schuster@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $filiale, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['it'], 'first_name' => 'Mario', 'last_name' => 'Engel', 'position' => 'IT-Beauftragter (extern, Teilzeit)', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0172 4455667', 'private_phone' => null, 'email' => 'engel@itservice-stuttgart.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Externer Dienstleister, 1 Tag/Woche vor Ort.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb'], 'first_name' => 'Petra', 'last_name' => 'Voigt', 'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0172 4455668', 'private_phone' => null, 'email' => 'voigt@datenschutz-extern.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['azubi'], 'first_name' => 'Jana', 'last_name' => 'Reichert', 'position' => 'Auszubildende Kauffrau im Einzelhandel', 'department' => 'Verkauf', 'work_phone' => null, 'mobile_phone' => '0172 4455669', 'private_phone' => null, 'email' => 'reichert@schoengeist-wohnen.de', 'emergency_contact' => null, 'manager_id' => $emp['filiale'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $filiale, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['pos'], 'name' => 'Vectron Systems AG', 'type' => 'other', 'contact_name' => 'Vectron Service-Hotline', 'hotline' => '0800 8328766', 'email' => 'service@vectron.example', 'contract_number' => 'POS-2025-1144', 'sla' => 'Mo-Sa 8-20, Notdienst Wochenende', 'notes' => 'Kassensystem inkl. TSE-Wartung. Vor-Ort-Service binnen 4h.', 'direct_order_limit' => 2000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['payment'], 'name' => 'ConCardis GmbH', 'type' => 'other', 'contact_name' => 'Händler-Hotline', 'hotline' => '069 7922-2000', 'email' => 'support@concardis.example', 'contract_number' => 'CC-998877', 'sla' => '24/7', 'notes' => 'Payment-Provider für Kartenterminals (Girocard, V-Pay, Kreditkarten). Ersatzterminal binnen 24h.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lieferant'], 'name' => 'Wohnaccessoires Süd GmbH', 'type' => 'other', 'contact_name' => 'Vertrieb / Frau Gerlach', 'hotline' => '07142 998800', 'email' => 'bestellung@wohn-sued.example', 'contract_number' => 'LIEF-77', 'sla' => 'Lieferung 3-5 Werktage, Express 24h', 'notes' => 'Hauptlieferant Kerngeschäft, EDI-Anbindung.', 'direct_order_limit' => 8000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['msp'], 'name' => 'IT-Service Stuttgart GmbH', 'type' => 'it_msp', 'contact_name' => 'Support-Team', 'hotline' => '0711 7766550', 'email' => 'support@itservice-stuttgart.example', 'contract_number' => 'MSP-2026-08', 'sla' => 'Mo-Fr 8-18, Notfall 24/7', 'notes' => 'Netzwerk, WLAN, Arbeitsplätze, Backup.', 'direct_order_limit' => 4000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['isp'], 'name' => 'Vodafone Business', 'type' => 'internet_provider', 'contact_name' => 'Geschäftskunden-Service', 'hotline' => '0800 1721212', 'email' => 'business@vodafone.example', 'contract_number' => 'VF-GK-553311', 'sla' => '24/7 Entstörung', 'notes' => 'Kabel 500/50 mit LTE-Backup-SIM (im Router).', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Filiale', 'description' => 'Hausanschluss + Verteilung Verkaufsfläche, Kassen, Beleuchtung.', 'category' => 'basisbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 350, 'fallback_process' => 'USV überbrückt Kassen + Router 20 Min. Bei längerem Ausfall: Filiale schließen, Hinweis an Kunden.', 'runbook_reference' => 'Runbook „Stromausfall Filiale" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang Filiale', 'description' => 'Vodafone Kabel 500/50 mit LTE-Backup-SIM im Router.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'LTE-Backup im Router springt automatisch ein. Sonst Mobil-Hotspot Filialleitung.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['wlan'], 'name' => 'Filial-WLAN + Netzwerk', 'description' => 'Internes WLAN für Kassen, Etikettendrucker, Tablets; getrenntes Gäste-WLAN.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'Kassen per LAN umstecken (Reserve-Patchkabel im IT-Schrank). Reserve-Access-Point vorhanden.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['kasse'], 'name' => 'Kassensystem inkl. TSE', 'description' => 'Vectron-Kassen (2 Plätze) mit zertifizierter TSE (KassenSichV).', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'Papier-Kassenbuch + Aushang „Nur Bargeld". TSE-Ausfall sofort dokumentieren (Zeitraum nachweisbar). Vectron-Hotline anrufen.', 'runbook_reference' => 'Runbook „Kassen-/TSE-Ausfall" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['terminal'], 'name' => 'Kartenterminals (Payment)', 'description' => '2 ConCardis-Terminals (Girocard, Kreditkarten, kontaktlos).', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 300, 'fallback_process' => 'Mobiles Zweit-Terminal (im Tresor) aktivieren oder Aushang „Nur Bargeld". ConCardis-Hotline 24/7.', 'runbook_reference' => 'Runbook „Kartenzahlung-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['wawi'], 'name' => 'Warenwirtschaft (WaWi)', 'description' => 'Branchenlösung Möbelhandel: Bestand, Bestellung, Auslieferung.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'Manuelle Bestandsführung auf Papier; Nacherfassung nach Wiederanlauf. Kassen können autark verkaufen.', 'runbook_reference' => 'Runbook „WaWi-Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['edi'], 'name' => 'EDI-Bestellsystem Lieferanten', 'description' => 'Elektronische Bestellschnittstelle zu Hauptlieferanten.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Notbestellung per E-Mail / Telefon beim Lieferanten (Vertriebs-Hotline).', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['etiketten'], 'name' => 'Etiketten- + Bondrucker', 'description' => 'Preisetiketten Verkaufsfläche, Bonrolle Kasse, Versandetiketten Lager.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 60, 'fallback_process' => 'Ersatz-Drucker im Lager (gleiche Bauart). Handgeschriebene Bons übergangsweise (TSE-Hinweis beachten).', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['email'], 'name' => 'E-Mail (M365 Business)', 'description' => 'Microsoft 365 Business Standard, 14 Postfächer.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'M365-Webmail (mobil) als Fallback; dringende Sachen per Telefon.', 'runbook_reference' => 'Runbook „M365 Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['alarm'], 'name' => 'Einbruchmelde- + Videoanlage', 'description' => 'EMA + Videoüberwachung Filiale und Lager, Aufschaltung Wachdienst.', 'category' => 'sicherheit', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Wachdienst informieren, manuelle Streifen anfordern. Bei Bedarf Filiale früher schließen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['webshop'], 'name' => 'Mini-Webshop / Webseite', 'description' => 'Kleiner Shopify-Shop für Online-Auftritt + Produkthighlights, geringer Umsatzanteil.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 30, 'fallback_process' => 'Banner „Wartung" über Shopify-Backend; Bestellungen telefonisch annehmen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['wlan'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['wlan'], 'depends_on_system_id' => $sys['internet'], 'sort' => 1, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['kasse'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['kasse'], 'depends_on_system_id' => $sys['wlan'], 'sort' => 1, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['terminal'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Online-Autorisierung der Karten.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['wawi'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['wawi'], 'depends_on_system_id' => $sys['kasse'], 'sort' => 1, 'note' => 'Bons fließen aus Kasse in WaWi.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['edi'], 'depends_on_system_id' => $sys['wawi'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['etiketten'], 'depends_on_system_id' => $sys['wlan'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['email'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['alarm'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['webshop'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'CyberSchutz24 AG', 'policy_number' => 'CY-2026-7711', 'hotline' => '0800 8765432', 'email' => 'schaden@cyberschutz24.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Frau Hartmann', 'notes' => 'Deckung 750.000 €, inkl. Forensik und Krisenkommunikation.', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2026-44551', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'Betriebsunterbrechung bis 60 Tage, inkl. Mehrkosten.', 'deductible' => '1.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Kunden-Aushang Kassenausfall', 'audience' => 'customers', 'channel' => 'on_site', 'subject' => null, 'body' => "Liebe Kundinnen und Kunden,\n\naufgrund einer technischen Störung können wir aktuell leider nur Bargeld annehmen. Wir bitten um Ihr Verständnis und arbeiten mit Hochdruck an einer Lösung.\n\nIhr Team von {{ firma }}", 'fallback' => 'Aushang Eingangstür + Kassenbereich, A4 laminiert (Notfallbox Kasse).', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Erstmeldung Mitarbeiter (SMS)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'Wichtig: Bei {{ firma }} liegt aktuell eine Störung vor. Bitte keine E-Mails / Logins versuchen, keine USB-Sticks anstecken. Weisungen folgen über Stefanie Brenner (0172 4455661). Stand: {{ zeitpunkt }}.', 'fallback' => 'Aushang im Backoffice und Pausenraum.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Lieferanten-Notbestellung (E-Mail)', 'audience' => 'service_providers', 'channel' => 'email', 'subject' => 'Notbestellung – EDI-Ausfall – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\naufgrund eines technischen Ausfalls können wir aktuell nicht über EDI bestellen. Wir bitten um manuelle Erfassung folgender Notbestellung:\n\n{{ artikelliste }}\n\nLieferanschrift: Zentrallager, Heilbronner Straße 220, 70469 Stuttgart\n\nBitte um kurze Bestätigung per E-Mail oder Anruf.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Telefonische Bestellung über die hinterlegte Vertriebs-Hotline.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Aufsichtsbehörde', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit melden wir gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten (Kunden- und Kassendaten).\n\nVerantwortlicher: {{ firma }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'emergency_cash', 'name' => 'Wechselgeld-Reserve Tresor', 'description' => '1.500 € in Scheinen + 200 € in Münzrollen für Kassenstart und Wechselgeld bei Terminal-Ausfall.', 'location' => 'Tresor Backoffice Filiale', 'access_holders' => 'Carolin Wendler, Stefanie Brenner, Markus Hoffmann', 'last_check_at' => Helpers::date(-15), 'next_check_at' => Helpers::date(75), 'notes' => 'Bestand monatlich prüfen, bei Unterschreitung 1.000 € auffüllen.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Mobiles Zweit-Kartenterminal', 'description' => 'ConCardis Reserve-Terminal, SIM-basiert (autark vom Filial-WLAN).', 'location' => 'Tresor Backoffice', 'access_holders' => 'Filialleitung + Stellvertretung', 'last_check_at' => Helpers::date(-25), 'next_check_at' => Helpers::date(65), 'notes' => 'Quartalsweise Test-Transaktion durchführen, SIM-Datenvolumen prüfen.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Papier-Kassenbuch + Bon-Vordrucke', 'description' => 'Vorgedruckte Bon-Vordrucke und Kassenbuch für Notbetrieb bei Kassen-/TSE-Ausfall (KassenSichV-konform dokumentiert).', 'location' => 'Schublade unter Kasse 1, Reserve im Tresor', 'access_holders' => 'Filialleitung, Verkäuferinnen', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => 'Vorrat 200 Bons; bei Unterschreitung 50 nachbestellen.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Offline-Backup WaWi (USB)', 'description' => 'Wöchentliches Offline-Backup der Warenwirtschaft, 2x 2TB rotierend.', 'location' => 'Tresor GF + Bankschließfach', 'access_holders' => 'Carolin Wendler, Mario Engel', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentliche Rotation Mo morgens.', 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Prepaid-Hotspot-SIM', 'description' => 'Telekom Prepaid 50GB. Hotspot-fähig, falls Vodafone-LTE-Backup ausfällt.', 'location' => 'IT-Schrank Filiale', 'access_holders' => 'Mario Engel, Filialleitung', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion inkl. Telefonliste, Playbooks, Lieferanten-Notnummern.', 'location' => '1× GF, 1× Filialleitung, 1× Lager', 'access_holders' => 'GF, Notfallbeauftragte/r, IT-Lead', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste', 'description' => 'Erreichbarkeit aller Krisenrollen + Vertretungen prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['filiale'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Kassen- und TSE-Ausfall', 'description' => 'Schreibtisch-Übung: TSE fällt während Stoßzeit aus, Wechsel auf Notbetrieb mit Papier-Kassenbuch.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-90), 'next_due_at' => Helpers::date(275), 'responsible_employee_id' => $emp['filiale'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Warenwirtschaft', 'description' => 'Voll-Restore der WaWi-DB aus Offline-Backup auf Test-System.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['it'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Test Zweit-Kartenterminal', 'description' => 'Halbjährliche Test-Transaktion über das Reserve-Terminal aus dem Tresor.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(120), 'responsible_employee_id' => $emp['stellv'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'SMS-Notfallkette', 'description' => 'Test-SMS an alle Mitarbeiter, Antwort innerhalb 30 Min.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(305), 'responsible_employee_id' => $emp['filiale'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'USV-Akku-Test Filiale', 'description' => 'Last simulieren, Laufzeit Kasse + Router dokumentieren.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'LTE-Failover Router prüfen', 'description' => 'Kabelmodem trennen, Failover auf SIM messen, Datenvolumen prüfen.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['kasse'], 'title' => 'TSE-Zertifikat-Frist prüfen', 'description' => 'Restlaufzeit der TSE-Zertifikate prüfen, ggf. Verlängerung mit Vectron koordinieren.', 'due_date' => Helpers::date(20), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['kasse'], 'title' => 'TSE-Export-Test', 'description' => 'Probe-Export der TSE-Daten für Betriebsprüfung.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['terminal'], 'title' => 'Test-Transaktion Reserve-Terminal', 'description' => 'Mobiles Zweit-Terminal aus Tresor, Test-Buchung über 1 €.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['wawi'], 'title' => 'Restore-Test WaWi', 'description' => 'Voll-Restore der Warenwirtschaft auf Test-Server.', 'due_date' => Helpers::date(180), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['email'], 'title' => 'Phishing-Awareness-Übung', 'description' => 'Gefälschte Mail an Belegschaft, Klickraten auswerten.', 'due_date' => Helpers::date(75), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['alarm'], 'title' => 'Wartung EMA + Aufschaltung Wachdienst', 'description' => 'Jahres-Wartung, Test-Alarm an Wachdienst.', 'due_date' => Helpers::date(120), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
