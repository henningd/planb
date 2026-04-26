<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class KleineFertigungTemplate implements Contract
{
    public function name(): string
    {
        return 'Kleine Fertigung (20–80 MA, Maschinenbau-Zulieferer)';
    }

    public function industry(): Industry
    {
        return Industry::Produktion;
    }

    public function description(): string
    {
        return 'Mittelständischer Maschinenbau-Zulieferer / Sondermaschinenbau mit Konstruktion, CNC-Werkstatt und Lager. Schwerpunkt OEM-Zulieferung mit Liefertermin-SLA. Enthält: 12 Mitarbeiter mit Krisenrollen, 1 Werk, 12 Systeme inkl. ERP/CAD/CNC + Abhängigkeiten, 7 Dienstleister, Versicherungen, Notfallvorlagen, Testplan, system_tasks.';
    }

    public function sort(): int
    {
        return 80;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $werk = Helpers::uuid();

        $emp = [
            'gf' => Helpers::uuid(),
            'konstr_lead' => Helpers::uuid(),
            'konstr_1' => Helpers::uuid(),
            'konstr_2' => Helpers::uuid(),
            'meister' => Helpers::uuid(),
            'facharb_1' => Helpers::uuid(),
            'facharb_2' => Helpers::uuid(),
            'facharb_3' => Helpers::uuid(),
            'lager' => Helpers::uuid(),
            'einkauf' => Helpers::uuid(),
            'auftrag' => Helpers::uuid(),
            'qs' => Helpers::uuid(),
        ];

        $prov = [
            'erp' => Helpers::uuid(),
            'cad' => Helpers::uuid(),
            'maschinen' => Helpers::uuid(),
            'werkzeug' => Helpers::uuid(),
            'stahl' => Helpers::uuid(),
            'it' => Helpers::uuid(),
            'bg' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'druckluft' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'server' => Helpers::uuid(),
            'erp' => Helpers::uuid(),
            'cad' => Helpers::uuid(),
            'cnc' => Helpers::uuid(),
            'mde' => Helpers::uuid(),
            'wms' => Helpers::uuid(),
            'werkzeug' => Helpers::uuid(),
            'edi' => Helpers::uuid(),
            'mail' => Helpers::uuid(),
            'telefon' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Präzisions-Mechanik Hofmann GmbH',
                'industry' => 'produktion',
                'employee_count' => 48,
                'locations_count' => 1,
                'review_cycle_months' => 12,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'important',
                'cyber_insurance_deductible' => '5.000 €',
                'budget_it_lead' => 1500,
                'budget_emergency_officer' => 5000,
                'budget_management' => 50000,
                'data_protection_authority_name' => 'BayLDA (Bayerisches Landesamt für Datenschutzaufsicht)',
                'data_protection_authority_phone' => '0981 180093-0',
                'data_protection_authority_website' => 'https://www.lda.bayern.de',
            ]],

            'locations' => [
                [
                    'id' => $werk, 'name' => 'Werk + Verwaltung', 'street' => 'Industriering 12',
                    'postal_code' => '91550', 'city' => 'Dinkelsbühl', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '09851 555100',
                    'notes' => 'Verwaltung, Konstruktion, CNC-Halle (8 Maschinen), Lager, Versand.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['gf'], 'first_name' => 'Reinhard', 'last_name' => 'Hofmann', 'position' => 'Geschäftsführer', 'department' => 'Geschäftsführung', 'work_phone' => '09851 555101', 'mobile_phone' => '0171 4445501', 'private_phone' => '09851 998877', 'email' => 'r.hofmann@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => 'Inhaber, hauptverantwortlich Vertrieb / Strategie.', 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['konstr_lead'], 'first_name' => 'Andreas', 'last_name' => 'Bauer', 'position' => 'Konstruktionsleitung', 'department' => 'Konstruktion', 'work_phone' => '09851 555110', 'mobile_phone' => '0171 4445510', 'private_phone' => null, 'email' => 'a.bauer@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => 'CAD/CAM-Hauptverantwortlicher.', 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['konstr_1'], 'first_name' => 'Stefan', 'last_name' => 'Wagner', 'position' => 'Konstrukteur', 'department' => 'Konstruktion', 'work_phone' => '09851 555111', 'mobile_phone' => '0171 4445511', 'private_phone' => null, 'email' => 's.wagner@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['konstr_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['konstr_2'], 'first_name' => 'Markus', 'last_name' => 'Hartmann', 'position' => 'Konstrukteur', 'department' => 'Konstruktion', 'work_phone' => '09851 555112', 'mobile_phone' => '0171 4445512', 'private_phone' => null, 'email' => 'm.hartmann@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['konstr_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['meister'], 'first_name' => 'Klaus', 'last_name' => 'Fischer', 'position' => 'Werkstattmeister', 'department' => 'Werkstatt', 'work_phone' => '09851 555120', 'mobile_phone' => '0171 4445520', 'private_phone' => null, 'email' => 'k.fischer@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Maschinen-Verantwortlicher, Werkstatt-Sicherheit.', 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['facharb_1'], 'first_name' => 'Thomas', 'last_name' => 'Schreiner', 'position' => 'CNC-Facharbeiter', 'department' => 'Werkstatt', 'work_phone' => null, 'mobile_phone' => '0171 4445521', 'private_phone' => null, 'email' => 't.schreiner@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['meister'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['facharb_2'], 'first_name' => 'Michael', 'last_name' => 'Krämer', 'position' => 'CNC-Facharbeiter / Vertretung Meister', 'department' => 'Werkstatt', 'work_phone' => null, 'mobile_phone' => '0171 4445522', 'private_phone' => null, 'email' => 'm.kraemer@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['meister'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => 'Vertritt Meister bei Abwesenheit.', 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['facharb_3'], 'first_name' => 'Jürgen', 'last_name' => 'Vogt', 'position' => 'Facharbeiter Montage', 'department' => 'Werkstatt', 'work_phone' => null, 'mobile_phone' => '0171 4445523', 'private_phone' => null, 'email' => 'j.vogt@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['meister'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['lager'], 'first_name' => 'Heinz', 'last_name' => 'Berger', 'position' => 'Lagerist / Versand', 'department' => 'Logistik', 'work_phone' => '09851 555130', 'mobile_phone' => '0171 4445530', 'private_phone' => null, 'email' => 'h.berger@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['meister'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['einkauf'], 'first_name' => 'Petra', 'last_name' => 'Lang', 'position' => 'Einkauf / Disposition', 'department' => 'Verwaltung', 'work_phone' => '09851 555140', 'mobile_phone' => '0171 4445540', 'private_phone' => null, 'email' => 'p.lang@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Lieferanten-Kommunikation in Krise.', 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['auftrag'], 'first_name' => 'Susanne', 'last_name' => 'Maier', 'position' => 'Auftragsabwicklung / Buchhaltung', 'department' => 'Verwaltung', 'work_phone' => '09851 555141', 'mobile_phone' => '0171 4445541', 'private_phone' => null, 'email' => 's.maier@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'IT-Hauptansprechpartnerin (intern), Schnittstelle MSP.', 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['qs'], 'first_name' => 'Birgit', 'last_name' => 'Zimmermann', 'position' => 'Qualitätssicherung / ISO-Beauftragte', 'department' => 'Qualität', 'work_phone' => '09851 555150', 'mobile_phone' => '0171 4445550', 'private_phone' => null, 'email' => 'b.zimmermann@praezisions-hofmann.de', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 1, 'notes' => 'ISO 9001-Beauftragte, Datenschutz-Koordination intern (DSB extern).', 'location_id' => $werk, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['erp'], 'name' => 'ams.erp Hauspartner Süd GmbH', 'type' => 'other', 'contact_name' => 'Hr. Eberle (Account)', 'hotline' => '0800 2671000', 'email' => 'support@ams-partner-sued.example', 'contract_number' => 'WP-2025-441', 'sla' => 'Mo-Fr 8-18, P1 4h Reaktion', 'notes' => 'ERP-Hauspartner ams.erp. Bei Total-Ausfall ist Auftragsabwicklung blockiert — höchste Priorität.', 'direct_order_limit' => 8000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['cad'], 'name' => 'Bechtle Solidworks Reseller', 'type' => 'other', 'contact_name' => 'Hr. Walz', 'hotline' => '0711 555 9090', 'email' => 'cad-support@bechtle.example', 'contract_number' => 'CAD-2026-777', 'sla' => 'Mo-Fr 8-17', 'notes' => 'Solidworks/Inventor Lizenzen + Support. Lizenz-Server-Probleme stoppen die Konstruktion.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['maschinen'], 'name' => 'DMG Mori Service GmbH', 'type' => 'other', 'contact_name' => 'Servicedispo', 'hotline' => '0800 8 364 6674', 'email' => 'service@dmgmori.example', 'contract_number' => 'WV-DMG-2026', 'sla' => 'Mo-Fr 7-19, P1 nächster Werktag', 'notes' => 'Maschinenservice CNC-Hersteller (4 Fräs- und 2 Drehmaschinen).', 'direct_order_limit' => 10000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['werkzeug'], 'name' => 'Hoffmann Group', 'type' => 'other', 'contact_name' => 'Außendienst Region Mittelfranken', 'hotline' => '089 8391-0', 'email' => 'service@hoffmann-group.example', 'contract_number' => 'KD-998812', 'sla' => '24h-Lieferung Standard', 'notes' => 'Werkzeug-Lieferant für Wendeplatten, Bohrer, Messmittel.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['stahl'], 'name' => 'Klöckner & Co. Süd', 'type' => 'other', 'contact_name' => 'Hr. Brunner', 'hotline' => '0911 6502-0', 'email' => 'auftrag@kloeckner.example', 'contract_number' => 'RKV-2026-12', 'sla' => '48–72h Standard, Express möglich', 'notes' => 'Stahl- und Aluminium-Halbzeuge. Bei Engpass Eskalation an Hr. Brunner.', 'direct_order_limit' => 15000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['it'], 'name' => 'NetCom Mittelfranken IT-MSP', 'type' => 'it_msp', 'contact_name' => 'Fr. Renner', 'hotline' => '0911 999 7000', 'email' => 'noc@netcom-mfr.example', 'contract_number' => 'MSP-2026-441', 'sla' => 'Mo-Fr 7-19, Notfall 24/7', 'notes' => 'Server, Netzwerk, Backup, M365-Verwaltung. Anfahrt ca. 60 Min.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['bg'], 'name' => 'BG Holz und Metall', 'type' => 'other', 'contact_name' => 'Bezirksverwaltung Nürnberg', 'hotline' => '0800 9990080', 'email' => 'nuernberg@bghm.example', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Berufsgenossenschaft. Meldung bei Arbeitsunfällen, Maschinen-Unfällen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung 3-Phasen / Werk', 'description' => 'Drehstrom-Hausanschluss 250A, getrennte Verteilung Halle/Büro. USV nur im Server-Raum.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 1500, 'fallback_process' => 'Notstrom-Aggregat (Diesel, mobil) für Server + Notlicht. CNC-Maschinen können nicht generatorgestützt laufen — Werkstatt steht.', 'runbook_reference' => 'Runbook „Stromausfall Werk" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['druckluft'], 'name' => 'Druckluft-Anlage', 'description' => 'Schraubenkompressor 11 kW + Trockner. Versorgt Spannmittel, Reinigung, Pneumatik.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'Reserve-Kompressor (mobil, gemietet) anfordern. Spann-/Reinigungsvorgänge an Maschinen ohne Druckluft nicht möglich.', 'runbook_reference' => 'Runbook „Druckluft-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internet-Anbindung Werk', 'description' => 'Glasfaser 500/200 Geschäftskunde, statische IP, Backup über LTE-Router.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'Automatisches Failover auf LTE; EDI-Übertragungen verlangsamt. Bei mehrtägigem Ausfall mobile Hotspots an kritischen Arbeitsplätzen.', 'runbook_reference' => 'Runbook „Internet-Failover" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['server'], 'name' => 'IT-Server (lokal)', 'description' => 'Hyper-V-Host mit 3 VMs: AD/DC, ERP-DB, Datei-Server. Backup auf NAS + Tape.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 1200, 'fallback_process' => 'Notbetrieb über zweiten Hyper-V-Host (warmer Standby). Bei Total-Ausfall Restore aus Tape — Dauer 6–8h.', 'runbook_reference' => 'Runbook „Server-Restore" v1.3', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['erp'], 'name' => 'ERP (ams.erp)', 'description' => 'ams.erp für Auftrag, Stückliste, Fertigungsplanung, Rechnung. SQL-Backend.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 1800, 'fallback_process' => 'Papier-Auftragsmappen aus Notfall-Schrank verwenden; Aufträge mit Bleistift fortführen. Nacherfassung nach Wiederanlauf. Versand nur gegen Lieferschein-Block.', 'runbook_reference' => 'Runbook „ERP-Ausfall ams" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['cad'], 'name' => 'CAD/CAM (Solidworks + Inventor)', 'description' => 'Solidworks Network-Lizenz + Autodesk Inventor. Modelle auf Datei-Server abgelegt.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Bei Lizenzserver-Ausfall: Reseller anrufen (Notfall-Lizenz möglich). Bestehende NC-Programme weiter ausführen, neue erst nach Wiederanlauf.', 'runbook_reference' => 'Runbook „CAD-Lizenzserver" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['cnc'], 'name' => 'CNC-Steuerungen (Heidenhain / Siemens)', 'description' => '4 Fräsmaschinen (Heidenhain TNC640) + 2 Drehmaschinen (Siemens 840D). NC-Programme aus Datei-Server.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 480, 'downtime_cost_per_hour' => 2000, 'fallback_process' => 'Bei Datei-Server-Ausfall NC-Programme über USB-Stick aus letztem Backup einspielen. Bei Maschinen-Defekt DMG-Mori-Hotline.', 'runbook_reference' => 'Runbook „CNC-Notbetrieb" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['mde'], 'name' => 'MDE/BDE (Maschinen-/Betriebsdaten)', 'description' => 'Erfassung Maschinenlauf-Zeiten + Stückzahlen für Nachkalkulation. Anbindung an ERP.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Manuelle Erfassung auf Stempelkarten / Strichliste. Nacherfassung nach Wiederanlauf.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['wms'], 'name' => 'Lagerverwaltung (WMS)', 'description' => 'Modul im ERP, Barcode-Scanner. Bestände Halbzeug + Fertigteile.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'Papier-Lagerlisten (wöchentlicher Druck im Notfall-Ordner). Buchungen handschriftlich, Nacherfassung nach Wiederanlauf.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['werkzeug'], 'name' => 'Werkzeug-Verwaltung', 'description' => 'Werkzeug-Schrank mit Chip-Ausgabe, Voreinstell-Daten in CAM-System.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Notfall-Werkzeugset (komplett bestückt) im Meister-Büro. Manuelle Ausgabe per Liste.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['edi'], 'name' => 'EDI-Anbindung an OEM-Kunden', 'description' => 'EDI-Übertragung (VDA 4905/4915) an 3 Großkunden Automotive. Lieferabrufe + Avise.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'Manuelle Abstimmung mit OEM-Disposition per Telefon + E-Mail. Lieferabrufe aus Kunden-Portal manuell ziehen. Verzug → Eskalation Vertrieb.', 'runbook_reference' => 'Runbook „EDI-Ausfall OEM" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['mail'], 'name' => 'Mailserver (M365 Exchange)', 'description' => 'Microsoft 365 Business Standard. Verteiler an Konstruktion, Vertrieb, Werkstatt.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'M365-Webmail mobil; bei Total-Ausfall Kommunikation per Telefon + private Hotmail-Adresse für GF (in Notfallplan dokumentiert).', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefon'], 'name' => 'Telefonanlage (VoIP)', 'description' => 'Cloud-VoIP, Sammelanschluss, Durchwahlen.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'Mobil-Nummer GF + Auftragsabwicklung als Notfall-Hotline auf Anrufbeantworter / Webseite.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['druckluft'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => 'Kompressor benötigt 3-Phasen-Strom.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['server'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => 'USV überbrückt 30 Min für sauberen Shutdown.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['erp'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => 'SQL-Backend auf Server.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cad'], 'depends_on_system_id' => $sys['server'], 'sort' => 0, 'note' => 'Lizenz-Server + Modell-Ablage auf Datei-Server.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cnc'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => 'Maschinen brauchen 3-Phasen-Strom.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cnc'], 'depends_on_system_id' => $sys['druckluft'], 'sort' => 1, 'note' => 'Spannmittel pneumatisch.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cnc'], 'depends_on_system_id' => $sys['server'], 'sort' => 2, 'note' => 'NC-Programme vom Datei-Server.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['mde'], 'depends_on_system_id' => $sys['erp'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['wms'], 'depends_on_system_id' => $sys['erp'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['edi'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['edi'], 'depends_on_system_id' => $sys['erp'], 'sort' => 1, 'note' => 'Lieferabrufe werden in ERP eingespielt.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['mail'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'HDI Cyber+', 'policy_number' => 'CY-PMH-2026-001', 'hotline' => '0800 4434464', 'email' => 'cyber-schaden@hdi.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Hr. Pfeiffer', 'notes' => 'Deckung 1,5 Mio €, inkl. Forensik + Wiederherstellung.', 'deductible' => '5.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Industrie AG', 'policy_number' => 'BI-PMH-2025-887', 'hotline' => '0800 1112020', 'email' => 'industrie-schaden@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Fr. Stein', 'notes' => 'BU bis 90 Tage, inkl. Maschinenbruch-Folgeschaden.', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'liability', 'insurer' => 'Gothaer Allgemeine AG', 'policy_number' => 'BHV-PMH-2025-12', 'hotline' => '0800 14333008', 'email' => 'gewerbe-schaden@gothaer.example', 'reporting_window' => 'binnen 14 Tagen', 'contact_name' => 'Fr. Walter', 'notes' => 'Betriebshaftpflicht inkl. Produkthaftung 5 Mio €.', 'deductible' => '1.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Kunden-Eskalation Liefer-Verzug (E-Mail)', 'audience' => 'customers', 'channel' => 'email', 'subject' => 'Wichtige Information zu Lieferung {{ lieferschein }} – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\naufgrund eines aktuellen Produktionsausfalls bei {{ firma }} wird sich die Lieferung zu Auftrag {{ auftrag_nr }} voraussichtlich um {{ verzug_tage }} Tage verzögern. Wir arbeiten mit Hochdruck am Wiederanlauf.\n\nNeuer Liefertermin (vorläufig): {{ neuer_termin }}\nUrsache (allgemein): {{ ursache }}\n\nAnsprechpartner Eskalation: {{ ansprechpartner }} ({{ telefon }}).\n\nWir bitten um Ihr Verständnis.\n\nMit freundlichen Grüßen\n{{ firma }} – Vertrieb / Auftragsabwicklung", 'fallback' => 'Telefonischer Anruf bei OEM-Disposition unmittelbar nach E-Mail-Versand.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Belegschafts-Aushang Anlagenstörung', 'audience' => 'employees', 'channel' => 'notice', 'subject' => 'Anlagenstörung – Vorgehen im Werk', 'body' => "WICHTIG – ANLAGENSTÖRUNG\n\nStand: {{ zeitpunkt }}\nBetroffen: {{ system }}\nVoraussichtlich behoben: {{ prognose }}\n\nVorgehen bis Wiederanlauf:\n• Werkstatt: Anweisungen vom Meister abwarten, keine eigenmächtigen Maschinen-Resets\n• Konstruktion: Auf Offline-Modus wechseln (Modelle lokal weiterbearbeiten)\n• Auftragsabwicklung: Papier-Auftragsmappen verwenden\n• Rückfragen: Notfallbeauftragter Klaus Fischer (0171 4445520)\n\nKeine USB-Sticks anstecken, keine privaten Logins, keine Mails öffnen.", 'fallback' => 'Aushang an Schwarzes Brett Halle + Pausenraum + Eingang Verwaltung.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Lieferanten-Notbestellung (E-Mail)', 'audience' => 'suppliers', 'channel' => 'email', 'subject' => 'Notfall-Express-Bestellung {{ firma }} – {{ datum }}', 'body' => "Sehr geehrte Damen und Herren,\n\naufgrund eines Produktionsengpasses bei {{ firma }} bitten wir um eine Express-Lieferung folgender Positionen:\n\n{{ positionen }}\n\nBenötigt bis: {{ benoetigt_bis }}\nLiefer-Adresse: Industriering 12, 91550 Dinkelsbühl\nFreigabe-Limit Hr. Hofmann/Fr. Lang erteilt.\n\nBitte um kurze Bestätigung der Lieferzeit per Antwort-Mail oder Anruf bei Fr. Lang (0171 4445540).\n\nDanke und freundliche Grüße\nEinkauf / Disposition", 'fallback' => 'Telefonischer Anruf bei Hauspartner direkt nach E-Mail.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Erstmeldung Krisenstab (SMS)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'KRISENSTAB {{ firma }}: Bei aktueller Störung bitte sofort melden bei K. Fischer (0171 4445520) ODER R. Hofmann (0171 4445501). Keine Logins/USB-Sticks. Stand: {{ zeitpunkt }}.', 'fallback' => 'Telefonkette wenn SMS-Versand ausfällt.', 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Notstrom-Aggregat (Diesel, mobil)', 'description' => '20 kVA Diesel-Aggregat, Anhänger, fester Einspeisepunkt am Server-Raum. 200 l Tank.', 'location' => 'Lager Halle West, Schlüssel beim Meister', 'access_holders' => 'Klaus Fischer, Reinhard Hofmann, NetCom MSP', 'last_check_at' => Helpers::date(-45), 'next_check_at' => Helpers::date(45), 'notes' => 'Probelauf vierteljährlich. Diesel-Kanister 60 l Reserve im Lager.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Werkzeug-Notfallset', 'description' => 'Bestückter Werkzeug-Schrank mit Standardwerkzeug für alle gängigen Aufträge (Wendeplatten, Bohrer, Voreinstellung).', 'location' => 'Meister-Büro, abschließbar', 'access_holders' => 'Klaus Fischer, Michael Krämer', 'last_check_at' => Helpers::date(-60), 'next_check_at' => Helpers::date(120), 'notes' => 'Halbjährlich Bestand prüfen.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Papier-Auftragsmappen', 'description' => '50 vorgedruckte Auftragsmappen mit Lieferschein-Kopierblock + Stundenzettel.', 'location' => 'Notfall-Schrank Auftragsabwicklung', 'access_holders' => 'Susanne Maier, Petra Lang, Reinhard Hofmann', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(150), 'notes' => 'Bei Verbrauch Nachdruck über Druckerei.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Sicherheitsdatenblätter (SDB)', 'description' => 'Aktuelle SDB aller verwendeten Kühlschmierstoffe, Reinigungsmittel, Schmieröle. Papier-Ordner.', 'location' => 'Meister-Büro + Lager', 'access_holders' => 'Klaus Fischer, Birgit Zimmermann, Feuerwehr (im Brandfall)', 'last_check_at' => Helpers::date(-90), 'next_check_at' => Helpers::date(90), 'notes' => 'Aktualisierung halbjährlich, BG-Anforderung.', 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Tape-Backup (LTO) extern', 'description' => 'Wöchentliches Voll-Backup auf LTO, rotierend Bankschließfach.', 'location' => 'Bankschließfach Sparkasse Dinkelsbühl + 1 Kassette im Tresor GF', 'access_holders' => 'Reinhard Hofmann, Susanne Maier, NetCom MSP', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentliche Rotation Mo morgens.', 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion inkl. Telefonliste, Playbooks, Lieferanten-Kontakte.', 'location' => '1× GF, 1× Meister, 1× Auftragsabwicklung, 1× Privat GF', 'access_holders' => 'GF, Notfallbeauftragter, Werkstattleitung, Auftragsabwicklung', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste', 'description' => 'Erreichbarkeit aller Krisenrollen + Vertretungen + Hauspartner ams.erp + DMG-Mori prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-90), 'next_due_at' => Helpers::date(90), 'responsible_employee_id' => $emp['auftrag'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Ransomware mit ERP-Ausfall', 'description' => 'Schreibtisch-Übung: Ransomware verschlüsselt Datei-Server + ERP. Ablauf, Versicherungs-Meldung, Kunden-Eskalation.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-180), 'next_due_at' => Helpers::date(185), 'responsible_employee_id' => $emp['gf'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test ERP + Datei-Server', 'description' => 'Voll-Restore aus LTO-Tape auf Test-Hyper-V. Datenstand prüfen, NC-Programme öffnen.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-200), 'next_due_at' => Helpers::date(165), 'responsible_employee_id' => $emp['auftrag'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'recovery', 'name' => 'Notstrom-Probelauf', 'description' => 'Aggregat 30 Min unter Last; Server-Raum + Notlicht. Diesel-Stand auffüllen.', 'interval' => 'quarterly', 'last_executed_at' => Helpers::date(-45), 'next_due_at' => Helpers::date(45), 'responsible_employee_id' => $emp['meister'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Kunden-Eskalations-Mail-Test', 'description' => 'Test-Versand der Liefer-Verzug-Vorlage an internen Verteiler, Freigabe-Workflow durchspielen.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-80), 'next_due_at' => Helpers::date(285), 'responsible_employee_id' => $emp['einkauf'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'USV-Akku-Test Server-Raum', 'description' => 'Last simulieren, Laufzeit dokumentieren.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['druckluft'], 'title' => 'Wartung Schraubenkompressor', 'description' => 'Filter, Trockner, Öl. Hersteller-Servicevertrag.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'LTE-Failover scharf testen', 'description' => 'Glasfaser-Stecker ziehen, Failover-Zeit messen, ERP-Verfügbarkeit prüfen.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['server'], 'title' => 'Tape-Restore-Test (LTO)', 'description' => 'Voll-Restore auf Standby-Hyper-V. Datenstand prüfen.', 'due_date' => Helpers::date(150), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['erp'], 'title' => 'ams.erp Patch-Stand prüfen', 'description' => 'Hauspartner-Termin für nächstes Service-Pack.', 'due_date' => Helpers::date(75), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['cad'], 'title' => 'Solidworks-Lizenzserver-Restart-Plan', 'description' => 'Restart-Procedure dokumentieren, Reseller-Notfallnummer im Server-Raum aushängen.', 'due_date' => Helpers::date(40), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['cnc'], 'title' => 'NC-Programm-Backup auf USB rotieren', 'description' => 'Aktuelle NC-Programme auf USB-Stick im Meister-Tresor (Notfall).', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['edi'], 'title' => 'EDI-Verbindungstest mit OEM A', 'description' => 'Quartalsweise Konnektivitäts-Test (VDA 4905) mit Hauptkunde.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['mail'], 'title' => 'Phishing-Awareness-Übung', 'description' => 'Simulierte Phishing-Mail an Belegschaft, Klickraten auswerten.', 'due_date' => Helpers::date(120), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['wms'], 'title' => 'Inventur Halbjahr', 'description' => 'Stichprobeninventur Halbzeug + Fertigteile.', 'due_date' => Helpers::date(100), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
