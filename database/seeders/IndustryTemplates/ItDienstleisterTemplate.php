<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class ItDienstleisterTemplate implements Contract
{
    public function name(): string
    {
        return 'IT-Dienstleister / MSP (10–30 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Dienstleistung;
    }

    public function description(): string
    {
        return 'Managed Service Provider mit Werkstatt-Bereich. Schwerpunkt PSA, RMM, Backup-Plattform und CSP-Tenant. Eigene Systeme sind kritisch — Ausfall trifft sofort alle Kunden via SLA. Enthält: 13 Mitarbeiter, 1 Standort, 12 Systeme inkl. RTO/RPO, 7 Dienstleister, Berufshaftpflicht/Vermögensschadenhaftpflicht, Kommunikationsvorlagen für Kunden-Massenmail und Statuspage, Cyber-IR-Vertrag, Tabletop-Tests.';
    }

    public function sort(): int
    {
        return 110;
    }

    public function payload(): array
    {
        $standort = Helpers::uuid();

        $emp = [
            'gf' => Helpers::uuid(),
            'service_mgr' => Helpers::uuid(),
            'field_lead' => Helpers::uuid(),
            'field2' => Helpers::uuid(),
            'helpdesk_lead' => Helpers::uuid(),
            'helpdesk2' => Helpers::uuid(),
            'sales' => Helpers::uuid(),
            'projektleitung' => Helpers::uuid(),
            'buchhaltung' => Helpers::uuid(),
            'azubi' => Helpers::uuid(),
            'dsb_extern' => Helpers::uuid(),
            'admin_office' => Helpers::uuid(),
            'inhouse_security' => Helpers::uuid(),
        ];

        $prov = [
            'microsoft_csp' => Helpers::uuid(),
            'distri' => Helpers::uuid(),
            'rmm' => Helpers::uuid(),
            'psa' => Helpers::uuid(),
            'cyber_ir' => Helpers::uuid(),
            'isp' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'psa' => Helpers::uuid(),
            'rmm' => Helpers::uuid(),
            'backup_platform' => Helpers::uuid(),
            'csp_tenant' => Helpers::uuid(),
            'monitoring' => Helpers::uuid(),
            'doku' => Helpers::uuid(),
            'vpn' => Helpers::uuid(),
            'erp' => Helpers::uuid(),
            'statuspage' => Helpers::uuid(),
            'telefonie' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'NetWise Managed Services GmbH',
                'industry' => 'dienstleistung',
                'employee_count' => 13,
                'locations_count' => 1,
                'review_cycle_months' => 6,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'important',
                'cyber_insurance_deductible' => '5.000 €',
                'budget_it_lead' => 5000,
                'budget_emergency_officer' => 5000,
                'budget_management' => 50000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
            ]],

            'locations' => [
                [
                    'id' => $standort, 'name' => 'Hauptsitz mit Werkstatt', 'street' => 'Technologiepark 7',
                    'postal_code' => '70565', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 8800200',
                    'notes' => 'Büro, Helpdesk-Open-Space, Werkstatt für Hardware-Vorkonfiguration, Lager, Rack-Raum.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['gf'], 'first_name' => 'Markus', 'last_name' => 'Reinhardt', 'position' => 'Geschäftsführer', 'department' => 'Geschäftsführung', 'work_phone' => '0711 8800201', 'mobile_phone' => '0171 2233401', 'private_phone' => '0711 8899100', 'email' => 'reinhardt@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['service_mgr'], 'first_name' => 'Linda', 'last_name' => 'Hoffmann', 'position' => 'Servicemanagerin / Account Lead', 'department' => 'Service', 'work_phone' => '0711 8800210', 'mobile_phone' => '0171 2233402', 'private_phone' => null, 'email' => 'hoffmann@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Erste Anlaufstelle Kunden-Eskalation.', 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['field_lead'], 'first_name' => 'Tobias', 'last_name' => 'Schenk', 'position' => 'Field-Service-Lead', 'department' => 'Service', 'work_phone' => '0711 8800220', 'mobile_phone' => '0171 2233403', 'private_phone' => null, 'email' => 'schenk@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['service_mgr'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Disponiert Field-Techniker, Werkstatt-Lead.', 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['field2'], 'first_name' => 'Daniel', 'last_name' => 'Bauer', 'position' => 'Field-Techniker', 'department' => 'Service', 'work_phone' => null, 'mobile_phone' => '0171 2233404', 'private_phone' => null, 'email' => 'bauer@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['field_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['helpdesk_lead'], 'first_name' => 'Sebastian', 'last_name' => 'Wolf', 'position' => 'Helpdesk-Lead / Inhouse-Techniker', 'department' => 'Service', 'work_phone' => '0711 8800230', 'mobile_phone' => '0171 2233405', 'private_phone' => '0711 8899200', 'email' => 'wolf@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['service_mgr'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 1, 'notes' => 'IT-Lead-Vertretung, RMM-Spezialist.', 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['helpdesk2'], 'first_name' => 'Nina', 'last_name' => 'Albrecht', 'position' => 'Helpdesk-Technikerin', 'department' => 'Service', 'work_phone' => '0711 8800231', 'mobile_phone' => '0171 2233406', 'private_phone' => null, 'email' => 'albrecht@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['helpdesk_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['inhouse_security'], 'first_name' => 'Patrick', 'last_name' => 'Vogel', 'position' => 'Security-Engineer (Inhouse)', 'department' => 'Service', 'work_phone' => '0711 8800240', 'mobile_phone' => '0171 2233407', 'private_phone' => null, 'email' => 'vogel@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['helpdesk_lead'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => 'Cyber-Incident-Erstreaktion, Schnittstelle Forensik.', 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['sales'], 'first_name' => 'Christina', 'last_name' => 'Lehmann', 'position' => 'Vertrieb / Sales', 'department' => 'Vertrieb', 'work_phone' => '0711 8800250', 'mobile_phone' => '0171 2233408', 'private_phone' => null, 'email' => 'lehmann@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['projektleitung'], 'first_name' => 'Matthias', 'last_name' => 'Köhler', 'position' => 'Projektleitung', 'department' => 'Projekte', 'work_phone' => '0711 8800260', 'mobile_phone' => '0171 2233409', 'private_phone' => null, 'email' => 'koehler@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['admin_office'], 'first_name' => 'Sandra', 'last_name' => 'Berger', 'position' => 'Office-Manager / Notfallbeauftragte', 'department' => 'Verwaltung', 'work_phone' => '0711 8800202', 'mobile_phone' => '0171 2233410', 'private_phone' => null, 'email' => 'berger@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Operative Krisenkoordination.', 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung'], 'first_name' => 'Karin', 'last_name' => 'Maier', 'position' => 'Buchhaltung', 'department' => 'Verwaltung', 'work_phone' => '0711 8800203', 'mobile_phone' => '0171 2233411', 'private_phone' => null, 'email' => 'maier@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['azubi'], 'first_name' => 'Lukas', 'last_name' => 'Weiß', 'position' => 'Auszubildender Fachinformatiker', 'department' => 'Service', 'work_phone' => null, 'mobile_phone' => '0171 2233412', 'private_phone' => null, 'email' => 'weiss@netwise-ms.example', 'emergency_contact' => null, 'manager_id' => $emp['helpdesk_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $standort, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb_extern'], 'first_name' => 'Barbara', 'last_name' => 'Niemann', 'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 2233413', 'private_phone' => null, 'email' => 'niemann@dsb-extern.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['microsoft_csp'], 'name' => 'Microsoft (CSP-Vertrag)', 'type' => 'cloud_provider', 'contact_name' => 'Partner Center Support', 'hotline' => '0800 2848283', 'email' => 'partner@microsoft.example', 'contract_number' => 'CSP-NWMS-001', 'sla' => '24/7 Premier', 'notes' => 'CSP-Tenant, Lizenzen für alle Kundenmandaten. Premier-Support kritisch.', 'direct_order_limit' => 10000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['distri'], 'name' => 'Tech Data / Ingram Micro', 'type' => 'other', 'contact_name' => 'Vertriebsinnendienst', 'hotline' => '089 4700-0', 'email' => 'orders@techdata.example', 'contract_number' => 'D-NWMS-2024', 'sla' => 'Mo-Fr 8-18, Express 24h', 'notes' => 'Hardware-Distribution für Kundenprojekte und Werkstattbestand.', 'direct_order_limit' => 15000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['rmm'], 'name' => 'NinjaOne (RMM-Hersteller)', 'type' => 'other', 'contact_name' => 'Partner Support DACH', 'hotline' => '030 56796450', 'email' => 'support@ninjaone.example', 'contract_number' => 'NJN-2026-PARTNER-887', 'sla' => '24/7 Partner', 'notes' => 'RMM-Plattform für Endpoint-Management aller Kundenmandaten.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['psa'], 'name' => 'HaloPSA (Ticketsystem-Hersteller)', 'type' => 'other', 'contact_name' => 'Customer Success', 'hotline' => '+44 1372 232 254', 'email' => 'support@halopsa.example', 'contract_number' => 'HPSA-2026-NW', 'sla' => 'Mo-Fr 8-20', 'notes' => 'PSA-/Ticketsystem inkl. Vertragsverwaltung und Abrechnung.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['cyber_ir'], 'name' => 'CyberDefense Forensik GmbH', 'type' => 'other', 'contact_name' => 'IR-Hotline', 'hotline' => '0800 7777200', 'email' => 'ir@cyberdefense.example', 'contract_number' => 'IR-RETAINER-2026', 'sla' => '24/7, Ersteinsatz binnen 4h', 'notes' => 'Cyber-Incident-Response-Retainer. Bei vermutetem Befall sofort anrufen.', 'direct_order_limit' => 25000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['isp'], 'name' => 'TelCo Deutschland AG', 'type' => 'internet_provider', 'contact_name' => 'Geschäftskunden-Störung', 'hotline' => '0800 3300000', 'email' => 'stoerung@telco.example', 'contract_number' => 'GK-NW-2025', 'sla' => '24/7, redundante Anbindung', 'notes' => 'Glasfaser 1 Gbit/s + SD-WAN-Backup über 4G. Sehr kritisch.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen. Bei AVV-betroffenen Kunden zusätzlich Kunden informieren.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Standort', 'description' => 'Hausanschluss + USV im Rack-Raum.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'USV überbrückt 45 Min., Notstromaggregat manuell starten, kontrollierter Shutdown der internen Server.', 'runbook_reference' => 'Runbook „Stromausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang (redundant)', 'description' => 'Glasfaser 1 Gbit/s + LTE-/5G-Failover via SD-WAN.', 'category' => 'basisbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Automatisches SD-WAN-Failover; bei Totalausfall mobile Hotspots an Helpdesk verteilen.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['psa'], 'name' => 'PSA / Ticketsystem (HaloPSA)', 'description' => 'Zentrales Ticket-, Vertrags-, Abrechnungssystem für alle Kunden.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'Manuelle Ticketaufnahme via Mail-Postfach + Notfall-Kanban (Whiteboard); SLA-Uhren extern dokumentieren.', 'runbook_reference' => 'Runbook „PSA-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['rmm'], 'name' => 'RMM-Plattform (NinjaOne)', 'description' => 'Endpoint-Management, Patching, Monitoring aller Kunden-Endgeräte.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 1000, 'fallback_process' => 'Out-of-Band-Management auf priorisierten Kundensystemen; manuelle RDP-/SSH-Zugänge aus Doku-System.', 'runbook_reference' => 'Runbook „RMM-Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['backup_platform'], 'name' => 'Backup-Plattform (Veeam Cloud Connect)', 'description' => 'Zentrale Kunden-Backup-Plattform inkl. Cloud-Repository.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Lokale Kunden-Backups bleiben verfügbar; Cloud-Replikation pausieren, Hersteller-Hotline einbinden.', 'runbook_reference' => 'Runbook „Backup-Plattform" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['csp_tenant'], 'name' => 'Microsoft 365 CSP-Tenant + eigener Mandant', 'description' => 'Eigener Mandant für interne Mails sowie CSP-Verwaltungstenant für Kunden-Lizenzen.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'Interne Mails über Webmail-Backup; Kunden-Lizenzverwaltung über Partner Center direkt.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['monitoring'], 'name' => 'Monitoring (PRTG / Auvik)', 'description' => 'Netzwerk- und Service-Monitoring aller Kundennetze.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 300, 'fallback_process' => 'Reaktiver Betrieb über Kunden-Tickets, On-Call-Telefon bleibt aktiv.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['doku'], 'name' => 'IT-Dokumentation (IT Glue / Hudu)', 'description' => 'Zentrale Kunden-Dokumentation inkl. Passwörter und Eskalations-Runbooks.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 700, 'fallback_process' => 'Offline-Export (täglich) auf verschlüsseltem USB im Tresor; Notfall-Runbooks zusätzlich auf Papier.', 'runbook_reference' => 'Runbook „Doku-Recovery" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['vpn'], 'name' => 'VPN-Konzentrator', 'description' => 'Zentrales Site-to-Site- und Remote-VPN zu Kunden.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 500, 'fallback_process' => 'Kalt-Standby-Konzentrator aus Werkstatt; Direkt-Zugriff via Out-of-Band-Modems pro Kunde.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['erp'], 'name' => 'Internes ERP / Buchhaltung', 'description' => 'Aufträge, Rechnungen, Mitarbeiterstunden.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Stundenerfassung temporär in Excel; Rechnungslauf verschiebt sich.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['statuspage'], 'name' => 'Status-Page für Kunden', 'description' => 'Öffentliche Statusseite mit Service-Status pro Kundengruppe.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Massenmail an alle Kunden via Notfall-Mailprovider.', 'runbook_reference' => 'Runbook „Statuspage-Update" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefonie'], 'name' => 'Cloud-Telefonie (Hotline + Helpdesk-Queue)', 'description' => 'Cloud-PBX mit Helpdesk-Queue, On-Call-Routing.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Notfall-Mobilrufnummer (Bereitschafts-Handy) als Hotline kommunizieren; Statuspage aktualisieren.', 'runbook_reference' => 'Runbook „VoIP-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['psa'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'SaaS-Plattform.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['rmm'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'SaaS-Plattform.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['backup_platform'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Cloud-Replikation.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['csp_tenant'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['monitoring'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['doku'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'SaaS-Plattform.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['vpn'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['statuspage'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Bewusst extern gehostet, daher unabhängig vom eigenen Standort.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefonie'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['erp'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['psa'], 'depends_on_system_id' => $sys['csp_tenant'], 'sort' => 1, 'note' => 'SSO via Microsoft.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['rmm'], 'depends_on_system_id' => $sys['csp_tenant'], 'sort' => 1, 'note' => 'SSO via Microsoft.', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'professional_liability', 'insurer' => 'HDI Versicherung AG', 'policy_number' => 'VSH-2026-IT-998877', 'hotline' => '0511 645-0', 'email' => 'it-vsh@hdi.example', 'reporting_window' => 'unverzüglich', 'contact_name' => 'Schadenstelle IT-Dienstleister', 'notes' => 'Vermögensschadenhaftpflicht für IT-Dienstleister. Deckung 5 Mio €. KRITISCH wegen SLA-Vertragsstrafen.', 'deductible' => '10.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'Munich Re Cyber', 'policy_number' => 'CY-2026-MSP-771122', 'hotline' => '0800 6666200', 'email' => 'cyber@munichre.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'IR-Hotline', 'notes' => 'Deckung 3 Mio €, inkl. Forensik-Stunden, Krisenkommunikation, Drittschäden bei Kunden.', 'deductible' => '5.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-MSP-441199', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 60 Tage.', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Statuspage-Update Provider-Outage', 'audience' => 'customers', 'channel' => 'web', 'subject' => '[INVESTIGATING] Eingeschränkte Erreichbarkeit Helpdesk', 'body' => "Wir sind aktuell auf eine Störung an unserer {{ komponente }} aufmerksam geworden. Auswirkung: {{ auswirkung }}. Unsere Techniker arbeiten an der Behebung.\n\nWir melden uns spätestens in 30 Minuten mit einem Update.\n\nStand: {{ zeitpunkt }}", 'fallback' => 'Massenmail an alle Kunden bei längerer Statuspage-Störung.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Kunden-Massenmail (Service-Beeinträchtigung)', 'audience' => 'customers', 'channel' => 'email', 'subject' => 'Service-Mitteilung: Aktuelle Beeinträchtigung unserer Plattform', 'body' => "Sehr geehrte Damen und Herren,\n\nwir informieren Sie hiermit über eine aktuelle Beeinträchtigung unserer Service-Plattform ({{ komponente }}).\n\nAuswirkungen für Sie: {{ auswirkung }}\nVoraussichtliche Dauer: {{ prognose }}\nLive-Status: {{ statuspage_url }}\n\nUnsere Techniker arbeiten mit Hochdruck. Für dringende Eskalationen erreichen Sie uns über die Notfallnummer 0800 7777200.\n\nMit freundlichen Grüßen\nIhr NetWise-Team", 'fallback' => 'SMS-Massendienst über Notfall-Mailgate.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Kunden-Eskalations-Anruf-Skript', 'audience' => 'customers', 'channel' => 'phone', 'subject' => null, 'body' => "Begrüßung: Guten Tag {{ ansprechpartner_kunde }}, hier {{ name }} von NetWise.\nAnliegen: Wir möchten Sie aktiv darüber informieren, dass es bei {{ komponente }} aktuell zu Beeinträchtigungen kommt.\nKonkret: {{ auswirkung }}\nUnser Plan: {{ massnahme }}, Zeithorizont {{ prognose }}.\nFragen?\nNächster Kontakt: {{ naechster_kontakt }}.\nDanke für Ihre Geduld.", 'fallback' => 'Voicemail mit Verweis auf Statuspage und Kunden-Massenmail.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Lieferanten-Eskalation (CSP / RMM / PSA)', 'audience' => 'suppliers', 'channel' => 'email', 'subject' => 'P1 Eskalation – {{ komponente }} – NetWise', 'body' => "Sehr geehrtes {{ hersteller }}-Partner-Team,\n\nwir eskalieren P1 für {{ komponente }} (Vertrag {{ vertragsnummer }}). Auswirkungen: betrifft mehrere Endkunden, geschätzter Schaden je Stunde >1.000 €.\n\nBitte sofort technische Eskalation, Rückmeldung mit Ticketnummer und ETA.\n\nKontakt: {{ ansprechpartner }}, mobil 0171 2233405.", 'fallback' => 'Anruf an Hersteller-Hotline, Hinweis auf Premier-/Partner-Vertrag.', 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Erstmeldung Mitarbeiter (SMS)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'Achtung: Aktuell laufender Incident bei NetWise. Keine eigenständigen Aktionen an Kundensystemen, kein Patching, kein RMM-Push. Anweisungen über Sandra Berger (0171 2233410). Stand: {{ zeitpunkt }}.', 'fallback' => 'Telefonkette über Helpdesk-Lead.', 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Out-of-Band-Management-Hardware', 'description' => '4G-Router + serielle Konsolen-Server für Notzugriff auf eigenen VPN-Konzentrator und priorisierte Kundennetze.', 'location' => 'Rack-Raum + Werkstatt', 'access_holders' => 'Sebastian Wolf, Tobias Schenk, Patrick Vogel', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Cyber-Incident-Response-Vertrag (Papier)', 'description' => 'Retainer-Vertrag CyberDefense Forensik GmbH inkl. Eskalationsweg und Codewort.', 'location' => 'Tresor GF + Privatadresse GF', 'access_holders' => 'Markus Reinhardt, Sandra Berger, Patrick Vogel', 'last_check_at' => Helpers::date(-60), 'next_check_at' => Helpers::date(120), 'notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfall-Eskalations-Runbooks pro Kunde', 'description' => 'Pro Kundenmandat ein 1-Pager mit Hauptansprechpartnern, kritischen Systemen, RTO und Eskalationsweg.', 'location' => 'Aktenschrank Service-Manager + verschlüsselter Cloud-Mirror', 'access_holders' => 'Linda Hoffmann, Sebastian Wolf, Tobias Schenk', 'last_check_at' => Helpers::date(-21), 'next_check_at' => Helpers::date(70), 'notes' => 'Quartalsweise mit Kunden abgleichen.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Doku-Export (verschlüsselter USB)', 'description' => 'Tägliche Exporte aus IT Glue / Hudu auf 2× 2TB rotierend.', 'location' => 'Tresor GF + Bankschließfach', 'access_holders' => 'Markus Reinhardt, Patrick Vogel', 'last_check_at' => Helpers::date(-3), 'next_check_at' => Helpers::date(0), 'notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Bereitschafts-Handy + Prepaid-SIM', 'description' => 'Diensthandy für 24/7-Bereitschaft + Prepaid-Reserve.', 'location' => 'Helpdesk-Open-Space (Übergabeplatz)', 'access_holders' => 'Wechselnde Bereitschaft, koordiniert von Linda Hoffmann', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion inkl. Telefonkette, IR-Codewort, Statuspage-Templates.', 'location' => '1× GF, 1× Office-Manager, 1× Privatadresse Patrick Vogel', 'access_holders' => 'GF, Office-Manager, IT-Lead, Security-Engineer', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Quartals-Check Telefonliste', 'description' => 'Erreichbarkeit aller Krisenrollen, Bereitschaft und Hersteller-Hotlines prüfen.', 'interval' => 'quarterly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(30), 'responsible_employee_id' => $emp['admin_office'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Ransomware via RMM-Hijack', 'description' => 'Schreibtisch-Übung: Ransomware verbreitet sich über kompromittierten RMM-Token. Reaktion, Kunden-Eskalation, Versicherung.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-120), 'next_due_at' => Helpers::date(245), 'responsible_employee_id' => $emp['inhouse_security'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Ausfall PSA + Helpdesk-Telefon', 'description' => 'Übung: Komplettausfall PSA und Cloud-PBX. Manuelle Ticketaufnahme, Kunden-Massenmail, SLA-Buchführung.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-200), 'next_due_at' => Helpers::date(165), 'responsible_employee_id' => $emp['service_mgr'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Doku-System', 'description' => 'Voll-Restore aus IT-Glue-Export (USB) in Test-Tenant.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-110), 'next_due_at' => Helpers::date(70), 'responsible_employee_id' => $emp['helpdesk_lead'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Test Kunden-Massenmail + Statuspage-Update', 'description' => 'Trockenlauf: Statuspage-Update + Kunden-Massenmail an Test-Verteiler.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-90), 'next_due_at' => Helpers::date(90), 'responsible_employee_id' => $emp['service_mgr'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['rmm'], 'title' => 'RMM-Token rotieren', 'description' => 'API-Token und Agenten-Secrets quartalsweise rotieren.', 'due_date' => Helpers::date(20), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['rmm'], 'title' => 'RMM-Restore-Test (Konfiguration)', 'description' => 'Konfigurations-Backup wiederherstellen, in Sandbox prüfen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['psa'], 'title' => 'SLA-Reportings überprüfen', 'description' => 'Monatliches Report-Sampling auf 3 Kunden, SLA-Werte plausibilisieren.', 'due_date' => Helpers::date(15), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['backup_platform'], 'title' => 'Restore-Test Kunden-Backup', 'description' => 'Stichproben-Restore eines Kunden-Backups in Sandbox.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['csp_tenant'], 'title' => 'CSP-Tenant Conditional-Access-Review', 'description' => 'Bedingte Zugriffsregeln und privilegierte Konten reviewen.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['vpn'], 'title' => 'VPN-Failover-Test', 'description' => 'Aktiv-/Standby-Switch des VPN-Konzentrators testen.', 'due_date' => Helpers::date(75), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['statuspage'], 'title' => 'Statuspage-Templates aktualisieren', 'description' => 'Templates auf aktuelle Service-Komponenten und Sprache prüfen.', 'due_date' => Helpers::date(40), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['doku'], 'title' => 'Passwort-Audit (Stichprobe)', 'description' => '10 Kunden zufällig auswählen, Passwortrotation und MFA prüfen.', 'due_date' => Helpers::date(-2), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
