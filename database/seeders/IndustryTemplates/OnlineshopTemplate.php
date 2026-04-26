<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class OnlineshopTemplate implements Contract
{
    public function name(): string
    {
        return 'Onlineshop / D2C (5–15 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Handel;
    }

    public function description(): string
    {
        return 'D2C-Onlineshop mit eigenem Shopsystem (Shopify) plus Marktplätzen (Amazon, Otto). Fast alle Geschäftsprozesse digital. Enthält: 9 Mitarbeiter mit Krisenrollen, 2 Standorte (Büro + Versand-Hub), 12 Systeme inkl. Hosting/Payment/CRM, 7 Dienstleister, Versicherungen, Notfallvorlagen, Testplan.';
    }

    public function sort(): int
    {
        return 40;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $buero = Helpers::uuid();
        $versand = Helpers::uuid();

        $emp = [
            'gf' => Helpers::uuid(),
            'shopmgr' => Helpers::uuid(),
            'cs1' => Helpers::uuid(),
            'cs2' => Helpers::uuid(),
            'versand' => Helpers::uuid(),
            'marketing' => Helpers::uuid(),
            'buchhaltung' => Helpers::uuid(),
            'it' => Helpers::uuid(),
            'dsb' => Helpers::uuid(),
        ];

        $prov = [
            'hosting' => Helpers::uuid(),
            'agentur' => Helpers::uuid(),
            'payment' => Helpers::uuid(),
            'carrier' => Helpers::uuid(),
            'msp' => Helpers::uuid(),
            'steuer' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
        ];

        $sys = [
            'shop' => Helpers::uuid(),
            'payment' => Helpers::uuid(),
            'erp' => Helpers::uuid(),
            'versand' => Helpers::uuid(),
            'marketing' => Helpers::uuid(),
            'amazon' => Helpers::uuid(),
            'crm' => Helpers::uuid(),
            'helpdesk' => Helpers::uuid(),
            'hosting' => Helpers::uuid(),
            'dns' => Helpers::uuid(),
            'email' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'NaturBox Direct GmbH',
                'industry' => 'handel',
                'employee_count' => 9,
                'locations_count' => 2,
                'review_cycle_months' => 6,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'not_affected',
                'cyber_insurance_deductible' => '5.000 €',
                'budget_it_lead' => 1500,
                'budget_emergency_officer' => 5000,
                'budget_management' => 30000,
                'data_protection_authority_name' => 'BfDI',
                'data_protection_authority_phone' => '0228 997799-0',
                'data_protection_authority_website' => 'https://www.bfdi.bund.de',
            ]],

            'locations' => [
                [
                    'id' => $buero, 'name' => 'Büro Köln', 'street' => 'Aachener Straße 312',
                    'postal_code' => '50931', 'city' => 'Köln', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0221 5544330',
                    'notes' => 'Geschäftsführung, Marketing, Customer Service, Buchhaltung. Reine Büronutzung, kein Lager.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
                [
                    'id' => $versand, 'name' => 'Versand-Hub Köln-Porz', 'street' => 'Eiler Straße 17',
                    'postal_code' => '51107', 'city' => 'Köln', 'country' => 'DE',
                    'is_headquarters' => 0, 'phone' => '0221 5544360',
                    'notes' => 'Wareneingang, Lagerung, Kommissionierung, Versand DHL/DPD. 1 Mitarbeiter fest, Aushilfen saisonal.',
                    'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['gf'], 'first_name' => 'Friederike', 'last_name' => 'Naumann', 'position' => 'Geschäftsführerin', 'department' => 'Geschäftsführung', 'work_phone' => null, 'mobile_phone' => '0173 9988770', 'private_phone' => '02236 887766', 'email' => 'naumann@naturbox-direct.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $buero, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['shopmgr'], 'first_name' => 'Tim', 'last_name' => 'Sander', 'position' => 'Webshop-Manager / E-Commerce Lead', 'department' => 'E-Commerce', 'work_phone' => null, 'mobile_phone' => '0173 9988771', 'private_phone' => null, 'email' => 'sander@naturbox-direct.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Operativer Lead Shop, Marktplätze, Marketing-Tech.', 'location_id' => $buero, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['cs1'], 'first_name' => 'Nadine', 'last_name' => 'Brand', 'position' => 'Customer Service Lead', 'department' => 'Customer Service', 'work_phone' => null, 'mobile_phone' => '0173 9988772', 'private_phone' => null, 'email' => 'brand@naturbox-direct.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Hauptansprechpartnerin Kunden + Marktplatz-Eskalationen.', 'location_id' => $buero, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['cs2'], 'first_name' => 'Robin', 'last_name' => 'Becker', 'position' => 'Customer Service Agent', 'department' => 'Customer Service', 'work_phone' => null, 'mobile_phone' => '0173 9988773', 'private_phone' => null, 'email' => 'becker@naturbox-direct.de', 'emergency_contact' => null, 'manager_id' => $emp['cs1'], 'is_key_personnel' => 0, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $buero, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['versand'], 'first_name' => 'Heiko', 'last_name' => 'Lindner', 'position' => 'Lager- und Versandleitung', 'department' => 'Logistik', 'work_phone' => null, 'mobile_phone' => '0173 9988774', 'private_phone' => null, 'email' => 'lindner@naturbox-direct.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $versand, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['marketing'], 'first_name' => 'Mira', 'last_name' => 'Otten', 'position' => 'Marketing & Content', 'department' => 'Marketing', 'work_phone' => null, 'mobile_phone' => '0173 9988775', 'private_phone' => null, 'email' => 'otten@naturbox-direct.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Pflegt Klaviyo, Newsletter, Social Media – auch für Krisenkommunikation.', 'location_id' => $buero, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung'], 'first_name' => 'Klaus', 'last_name' => 'Hartmann', 'position' => 'Buchhaltung (Teilzeit)', 'department' => 'Verwaltung', 'work_phone' => null, 'mobile_phone' => '0173 9988776', 'private_phone' => null, 'email' => 'hartmann@naturbox-direct.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $buero, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['it'], 'first_name' => 'Jannik', 'last_name' => 'Roth', 'position' => 'IT-Operations (extern)', 'department' => 'IT', 'work_phone' => null, 'mobile_phone' => '0173 9988777', 'private_phone' => null, 'email' => 'roth@cloudops-koeln.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 1, 'notes' => 'Externer DevOps-Dienstleister, on-call 24/7 nach SLA.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb'], 'first_name' => 'Annette', 'last_name' => 'Schreiber', 'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0173 9988778', 'private_phone' => null, 'email' => 'schreiber@datenschutz-extern.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['hosting'], 'name' => 'Shopify International Ltd.', 'type' => 'cloud_provider', 'contact_name' => 'Shopify Plus Support', 'hotline' => 'nur per Chat / Ticket', 'email' => 'plus-support@shopify.example', 'contract_number' => 'SHP-PLUS-22119', 'sla' => '24/7 Plus-Support, 99,99 % Uptime', 'notes' => 'Shop-Hosting + Checkout. Statusseite status.shopify.com beobachten.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['agentur'], 'name' => 'Pixelwerk Köln GmbH', 'type' => 'other', 'contact_name' => 'Lena Förster', 'hotline' => '0221 7766551', 'email' => 'support@pixelwerk-koeln.example', 'contract_number' => 'AGT-2025-04', 'sla' => 'Mo-Fr 9-18, Notfall +50 %', 'notes' => 'Shop-Theme, Custom-Apps, Deployments. Kennt Code und Backups.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['payment'], 'name' => 'Stripe Payments Europe Ltd.', 'type' => 'other', 'contact_name' => 'Stripe Support', 'hotline' => 'nur per Chat / Ticket', 'email' => 'support@stripe.example', 'contract_number' => 'STR-acct-bd771', 'sla' => '24/7 (Standard)', 'notes' => 'Hauptzahlungsanbieter (Karten, Apple/Google Pay). PayPal als Sekundär-Provider.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['carrier'], 'name' => 'DHL Paket Geschäftskunden', 'type' => 'other', 'contact_name' => 'Geschäftskunden-Service', 'hotline' => '0228 4333112', 'email' => 'gk@dhl.example', 'contract_number' => 'DHL-GK-9988123', 'sla' => 'Mo-Fr 8-18', 'notes' => 'Hauptcarrier. Backup: DPD-Account aktiv halten.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['msp'], 'name' => 'CloudOps Köln GmbH', 'type' => 'it_msp', 'contact_name' => 'Jannik Roth', 'hotline' => '0221 9988770', 'email' => 'noc@cloudops-koeln.example', 'contract_number' => 'MSP-2026-12', 'sla' => '24/7 on-call', 'notes' => 'Monitoring, DNS, Backup-Orchestrierung, Marktplatz-Schnittstellen.', 'direct_order_limit' => 4000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['steuer'], 'name' => 'Kanzlei Steuerberatung Rheinland', 'type' => 'other', 'contact_name' => 'StB Mertens', 'hotline' => '0221 5544770', 'email' => 'mertens@stb-rheinland.example', 'contract_number' => 'STB-2024-77', 'sla' => 'Mo-Fr 9-17', 'notes' => 'OSS-Meldungen, Marktplatz-Umsätze, monatliche FiBu.', 'direct_order_limit' => 2000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'BfDI', 'type' => 'data_protection_authority', 'contact_name' => 'Bürgerservice', 'hotline' => '0228 997799-0', 'email' => 'poststelle@bfdi.bund.de', 'contract_number' => null, 'sla' => 'Mo-Fr 9-15', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['internet'], 'name' => 'Internetzugang Büro', 'description' => 'Glasfaser 1000/500, primär für Office-Betrieb (Cloud-Tools).', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Mobil-Hotspot Notfall-SIM oder Home-Office. Shop läuft hostingbedingt unabhängig.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['hosting'], 'name' => 'Cloud-Hosting (Shopify Plus)', 'description' => 'Shop + Checkout vollständig bei Shopify Plus gehostet.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 1500, 'fallback_process' => 'Shopify-Status prüfen (status.shopify.com). Bei längerem Ausfall: Wartungsmodus-Banner in Custom-Domain (CDN-Edge), Verkauf über Marktplätze (Amazon/Otto) hochfahren.', 'runbook_reference' => 'Runbook „Shop-Ausfall Shopify" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['dns'], 'name' => 'DNS / Domain', 'description' => 'Cloudflare DNS für Hauptdomain + Subdomains; Domain-Registrar separat.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 1500, 'fallback_process' => 'Cloudflare-Status prüfen, Records-Backup im Passwort-Tresor; Notfall-TTL niedrig halten (5 Min.).', 'runbook_reference' => 'Runbook „DNS-Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['shop'], 'name' => 'Shopsystem (Shopify Storefront)', 'description' => 'Storefront, Theme, Checkout, Apps. Hauptumsatzkanal D2C.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 1800, 'fallback_process' => 'Shop-Wartungsmodus aktivieren („Wir sind in Kürze wieder da"); Marketing-Sendungen pausieren; Customer-Service-Vorlage „Shop down" raus.', 'runbook_reference' => 'Runbook „Shop-Down Notice" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['payment'], 'name' => 'Payment-Gateway (Stripe + PayPal)', 'description' => 'Stripe primär, PayPal Express als Sekundär-Anbieter.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 1500, 'fallback_process' => 'Bei Stripe-Ausfall: PayPal-only-Banner schalten (Theme-Toggle). Bei Komplettausfall: Vorkasse via Banner anbieten.', 'runbook_reference' => 'Runbook „Payment-Gateway-Failover" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['erp'], 'name' => 'ERP / Bestandsführung', 'description' => 'Cloud-ERP (Xentral) mit Bestand, Auftrag, Rechnung, Buchhaltungs-Schnittstelle.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'Bestellungen sammeln (Shopify-Backend); Versand stoppt nach 4h. Manueller Bestand-Sync nach Wiederanlauf.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['versand'], 'name' => 'Versand-Software (DHL/DPD)', 'description' => 'Sendungserstellung, Labeldruck, Track&Trace-Sync zurück in ERP/Shop.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'Manuelle Label-Erstellung über DHL-Geschäftskundenportal; Tracking-Nummern manuell ins ERP nachtragen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['marketing'], 'name' => 'Marketing-Stack (Klaviyo + Meta Ads)', 'description' => 'Klaviyo für E-Mail/SMS-Flows, Meta + Google Ads für Performance-Marketing.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Im Krisenfall sofort alle aktiven Kampagnen pausieren (kein Push auf einen ausgefallenen Shop).', 'runbook_reference' => 'Runbook „Marketing-Shutdown bei Krise" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['amazon'], 'name' => 'Marktplatz-Anbindungen (Amazon, Otto)', 'description' => 'Amazon SP-API + Otto-Partner-API. Ca. 25 % Umsatzanteil.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 350, 'fallback_process' => 'Bei Anbindungsausfall: Bestand auf Marktplatz manuell nullen, Status-Hinweis im Marktplatz-Profil.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['crm'], 'name' => 'CRM (Kundendaten)', 'description' => 'HubSpot CRM mit Kundenhistorie, Segmenten, B2B-Kontakten.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Read-only Export aus letztem Backup; neue Tickets über Helpdesk weiterführen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['helpdesk'], 'name' => 'Customer-Service-Tool (Zendesk)', 'description' => 'Tickets aus E-Mail, Shop-Formular, Marktplätzen, Social.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'Direkt-Postfach support@ wieder aktivieren; Antworten in geteiltem Postfach koordinieren.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['email'], 'name' => 'E-Mail (Google Workspace)', 'description' => 'Google Workspace Business Standard, 9 Postfächer.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Workspace-Webmail (mobil); private Mobilnummern aus Telefonliste für dringende Abstimmungen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['shop'], 'depends_on_system_id' => $sys['hosting'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['shop'], 'depends_on_system_id' => $sys['dns'], 'sort' => 1, 'note' => 'Ohne DNS keine Auflösung der Storefront-Domain.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['payment'], 'depends_on_system_id' => $sys['shop'], 'sort' => 0, 'note' => 'Checkout im Shop ruft Stripe/PayPal.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['erp'], 'depends_on_system_id' => $sys['shop'], 'sort' => 0, 'note' => 'Order-Sync Shopify -> ERP.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['versand'], 'depends_on_system_id' => $sys['erp'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['amazon'], 'depends_on_system_id' => $sys['erp'], 'sort' => 0, 'note' => 'Bestand-Sync Marktplatz <-> ERP.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['marketing'], 'depends_on_system_id' => $sys['shop'], 'sort' => 0, 'note' => 'Klaviyo-Trigger basieren auf Shop-Events.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['marketing'], 'depends_on_system_id' => $sys['crm'], 'sort' => 1, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['crm'], 'depends_on_system_id' => $sys['shop'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['helpdesk'], 'depends_on_system_id' => $sys['email'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['email'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['email'], 'depends_on_system_id' => $sys['dns'], 'sort' => 1, 'note' => 'MX-Records.', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'Hiscox SA', 'policy_number' => 'CY-2026-D2C-3322', 'hotline' => '0800 5252420', 'email' => 'cyberclaims@hiscox.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Schadenteam Cyber', 'notes' => 'Deckung 1.000.000 €, inkl. Forensik, Erpressung, PR-Krise, Kundenbenachrichtigung.', 'deductible' => '5.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2026-D2C-7711', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'Ertragsausfall 60 Tage, ausdrücklich inkl. Cloud-/Plattform-Ausfall (Shopify, Stripe).', 'deductible' => '2.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Shop-Down-Banner (Custom-Domain)', 'audience' => 'customers', 'channel' => 'website', 'subject' => null, 'body' => "Wir sind gleich wieder da!\n\nUnser Shop ist aktuell aufgrund einer technischen Störung nicht erreichbar. Wir arbeiten mit Hochdruck an einer Lösung.\n\nBestellungen, die schon ausgelöst wurden, sind sicher und werden wie geplant versendet.\n\nFragen? Schreib uns an support@naturbox-direct.de oder per Instagram-DM.\n\nDanke für deine Geduld!\n\nDein {{ firma }}-Team", 'fallback' => 'Statusseite auf separater Subdomain (status.naturbox-direct.de) – wird über Cloudflare Pages gehostet.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Shop-Down Kunden-E-Mail (Klaviyo)', 'audience' => 'customers', 'channel' => 'email', 'subject' => 'Kurze Pause bei {{ firma }} – wir sind gleich wieder für dich da', 'body' => "Hallo {{ vorname }},\n\nunser Shop ist aktuell aufgrund einer technischen Störung nicht erreichbar. Bestehende Bestellungen werden wie geplant versendet.\n\nSobald wir wieder online sind, melden wir uns. Danke für deine Geduld!\n\nDein {{ firma }}-Team", 'fallback' => 'Versand über Notfall-Account bei Klaviyo (zweiter Sender), falls Hauptdomain blockiert.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Marktplatz-Statusmeldung (Amazon/Otto)', 'audience' => 'service_providers', 'channel' => 'email', 'subject' => 'Vorübergehende Bestandsanpassung – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\naufgrund einer technischen Störung in unserem Backend setzen wir den verfügbaren Bestand vorübergehend auf 0, um Überverkäufe zu vermeiden. Geplante Wiederherstellung: {{ zeitpunkt }}.\n\nBitte berücksichtigen Sie dies in den Performance-Metriken (Late Shipment Rate etc.).\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Bestand manuell im Seller Central / Otto Partner Connect auf 0 setzen.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Pressemitteilung Datenleck (Entwurf)', 'audience' => 'media', 'channel' => 'press_release', 'subject' => '{{ firma }} informiert über Sicherheitsvorfall', 'body' => "Köln, {{ datum }} – {{ firma }} hat heute Kenntnis von einem Sicherheitsvorfall erhalten, bei dem möglicherweise Kundendaten betroffen sind. Wir haben sofort {{ massnahmen }} eingeleitet und arbeiten mit externen Forensik-Spezialisten an der Aufklärung. Die zuständige Aufsichtsbehörde wurde informiert.\n\nAlle betroffenen Kundinnen und Kunden werden direkt kontaktiert. Wir bedauern den Vorfall und nehmen den Schutz personenbezogener Daten sehr ernst.\n\nFür Rückfragen: presse@naturbox-direct.de", 'fallback' => 'Vor Versand zwingend Freigabe durch GF + DSB + ggf. PR-Agentur.', 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Aufsichtsbehörde', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit melden wir gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten (Kunden- und Bestelldaten).\n\nVerantwortlicher: {{ firma }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\nBetroffene Datensätze (geschätzt): {{ anzahl }}\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Shop-Wartungsmodus-Banner (Code + Asset)', 'description' => 'Statisches HTML + Bild-Asset, das per Cloudflare-Worker auf die Custom-Domain ausgeliefert wird, falls Shopify-Storefront nicht erreichbar ist.', 'location' => 'Repo „naturbox-emergency-page" + Cloudflare Pages', 'access_holders' => 'Tim Sander, Jannik Roth, Pixelwerk Köln', 'last_check_at' => Helpers::date(-21), 'next_check_at' => Helpers::date(70), 'notes' => 'Quartalsweise Test-Aktivierung; DNS-TTL niedrig halten.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Shop-Theme + App-Konfig Rollback', 'description' => 'Git-getaggte Snapshots von Shop-Theme und App-Konfiguration; Daily Export der Produkt-/Kunden-/Order-Daten via Shopify-API in S3.', 'location' => 'GitHub-Repo + AWS S3 (eu-central-1)', 'access_holders' => 'Tim Sander, Jannik Roth, Pixelwerk Köln', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentlich automatisierter Restore-Smoketest.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Customer-Service-Vorlagenset', 'description' => 'Antwortvorlagen Deutsch + Englisch für: Shop down, Lieferverzögerung, Datenpanne, Marktplatz-Bestand-Korrektur.', 'location' => 'Zendesk-Macros + Backup als Markdown im Notion', 'access_holders' => 'Customer Service Team, Marketing', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => 'Quartalsweise Sprachreview, Datums-Platzhalter prüfen.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion inkl. Telefonliste, Provider-Kontakte, DNS-Records, Wartungsmodus-Aktivierung.', 'location' => '1× GF, 1× Webshop-Manager, 1× CS-Lead', 'access_holders' => 'GF, Notfallbeauftragte/r, IT-Lead', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Prepaid-Hotspot-SIM', 'description' => 'Vodafone Prepaid 100GB, Hotspot-fähig, falls Büro-Internet ausfällt.', 'location' => 'GF-Schreibtisch, Reserve im Versand-Hub', 'access_holders' => 'Friederike Naumann, Tim Sander, Heiko Lindner', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste', 'description' => 'Erreichbarkeit aller Krisenrollen + Vertretungen + on-call MSP prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['cs1'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Shopify-Komplettausfall', 'description' => 'Schreibtisch-Übung: Shopify-Storefront 6h offline während Black-Week-Sale.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-90), 'next_due_at' => Helpers::date(275), 'responsible_employee_id' => $emp['shopmgr'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Datenleck Kundendaten', 'description' => 'Schreibtisch-Übung: Forensik, DSGVO-Meldung 72h, Kundenbenachrichtigung, Pressemitteilung.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-200), 'next_due_at' => Helpers::date(165), 'responsible_employee_id' => $emp['gf'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Shop-Snapshot', 'description' => 'Restore eines Shop-Theme-Snapshots auf eine Development-Domain inkl. Datenimport (Produkte, Bestand) aus S3-Export.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(120), 'responsible_employee_id' => $emp['it'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Test Wartungsmodus-Banner', 'description' => 'Kontrollierte Aktivierung des Wartungsmodus-Banners auf Test-Subdomain, Sichtprüfung Mobil + Desktop.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-110), 'next_due_at' => Helpers::date(255), 'responsible_employee_id' => $emp['shopmgr'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['hosting'], 'title' => 'Shopify-Plus SLA-Review', 'description' => 'SLA-Reports + Statusseite-Vorfälle der letzten 6 Monate sichten, Lessons Learned ableiten.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['shop'], 'title' => 'Shop-Theme-Update + Regression-Test', 'description' => 'Theme-Update auf Dev-Branch, Regression-Test Checkout, Mobile-Performance prüfen.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['payment'], 'title' => 'PayPal-Failover-Test', 'description' => 'Stripe simuliert deaktivieren, Checkout-Verhalten + Banner prüfen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['dns'], 'title' => 'DNS-Record-Backup aktualisieren', 'description' => 'Aktuelle Cloudflare-Records exportieren, im Passwort-Tresor ablegen, mit MSP teilen.', 'due_date' => Helpers::date(15), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['amazon'], 'title' => 'Marktplatz-Token-Erneuerung', 'description' => 'Amazon SP-API + Otto-API-Tokens prüfen und vor Ablauf erneuern.', 'due_date' => Helpers::date(20), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['erp'], 'title' => 'Restore-Test ERP-Export', 'description' => 'ERP-Export ins Test-System einspielen, Datenintegrität stichprobenartig prüfen.', 'due_date' => Helpers::date(120), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['marketing'], 'title' => 'Klaviyo Notfall-Pause-Drill', 'description' => 'Test, wie schnell sich alle aktiven Flows + Kampagnen pausieren lassen.', 'due_date' => Helpers::date(75), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['email'], 'title' => 'Phishing-Awareness-Übung', 'description' => 'Gefälschte „Shopify-Support"-Mail an Belegschaft, Klickraten auswerten.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['helpdesk'], 'title' => 'Zendesk-Macros-Review', 'description' => 'Antwortvorlagen aktualisieren (Versandzeiten, neue Marktplatz-Texte).', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
