<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class PflegedienstTemplate implements Contract
{
    public function name(): string
    {
        return 'Ambulanter Pflegedienst (10–30 MA, 24/7)';
    }

    public function industry(): Industry
    {
        return Industry::Sonstiges;
    }

    public function description(): string
    {
        return 'Ambulanter Pflegedienst mit Sozialstation, mobiler Tour-Versorgung und gesetzlicher 24/7-Erreichbarkeitspflicht. Schwerpunkte: Pflege-Software (MDA), Tourenplanung, Krankenkassen-Abrechnung (DTA), Schweigepflicht. Enthält: 12 Mitarbeiter, 1 Standort, 11 Systeme inkl. extrem niedrige Telefon-RTO, 7 Dienstleister, Versicherungen, Kommunikationsvorlagen, Testplan, system_tasks.';
    }

    public function sort(): int
    {
        return 90;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $station = Helpers::uuid();

        $emp = [
            'pdl' => Helpers::uuid(),
            'pdl_stv' => Helpers::uuid(),
            'pflege_1' => Helpers::uuid(),
            'pflege_2' => Helpers::uuid(),
            'pflege_3' => Helpers::uuid(),
            'pflege_4' => Helpers::uuid(),
            'helfer_1' => Helpers::uuid(),
            'helfer_2' => Helpers::uuid(),
            'dispo' => Helpers::uuid(),
            'sekretariat' => Helpers::uuid(),
            'qmb' => Helpers::uuid(),
            'dsb' => Helpers::uuid(),
        ];

        $prov = [
            'software' => Helpers::uuid(),
            'mds' => Helpers::uuid(),
            'gkv' => Helpers::uuid(),
            'pflegekasse' => Helpers::uuid(),
            'steuer' => Helpers::uuid(),
            'it' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
        ];

        $sys = [
            'telefon' => Helpers::uuid(),
            'pflegesoft' => Helpers::uuid(),
            'mda' => Helpers::uuid(),
            'gps' => Helpers::uuid(),
            'doku' => Helpers::uuid(),
            'dta' => Helpers::uuid(),
            'eau' => Helpers::uuid(),
            'dienstplan' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'mail' => Helpers::uuid(),
            'strom' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Pflegeteam Mustermann GmbH',
                'industry' => 'sonstiges',
                'employee_count' => 22,
                'locations_count' => 1,
                'review_cycle_months' => 12,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'not_affected',
                'cyber_insurance_deductible' => '2.500 €',
                'budget_it_lead' => 800,
                'budget_emergency_officer' => 3000,
                'budget_management' => 25000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
            ]],

            'locations' => [
                [
                    'id' => $station, 'name' => 'Sozialstation', 'street' => 'Pfarrgasse 8',
                    'postal_code' => '74072', 'city' => 'Heilbronn', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '07131 555 200',
                    'notes' => 'Büro, Dienstplan, Material-Lager, Übergabe-Raum. Belegung morgens (6:00–8:30) und abends (18:00–20:00) hoch, tagsüber Pflegekräfte unterwegs.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['pdl'], 'first_name' => 'Martina', 'last_name' => 'Mustermann', 'position' => 'Pflegedienstleitung (PDL)', 'department' => 'Leitung', 'work_phone' => '07131 555 201', 'mobile_phone' => '0172 7770001', 'private_phone' => '07131 998811', 'email' => 'm.mustermann@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => 'Verantwortliche PDL nach SGB XI § 71. Bereitschaft auch außerhalb der Bürozeiten.', 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['pdl_stv'], 'first_name' => 'Carolin', 'last_name' => 'Becker', 'position' => 'Stellv. PDL', 'department' => 'Leitung', 'work_phone' => '07131 555 202', 'mobile_phone' => '0172 7770002', 'private_phone' => null, 'email' => 'c.becker@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pdl'], 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => 'Vertretung PDL bei Urlaub/Krankheit, Mit-Bereitschaft.', 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['pflege_1'], 'first_name' => 'Sandra', 'last_name' => 'Hoffmann', 'position' => 'Pflegefachkraft / Tourenleitung Früh', 'department' => 'Pflege', 'work_phone' => null, 'mobile_phone' => '0172 7770010', 'private_phone' => null, 'email' => 's.hoffmann@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pdl'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Tour-Verantwortung Tour 1 + 2 (Frühschicht), Notfallbeauftragte.', 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['pflege_2'], 'first_name' => 'Nadine', 'last_name' => 'Weber', 'position' => 'Pflegefachkraft / Tourenleitung Spät', 'department' => 'Pflege', 'work_phone' => null, 'mobile_phone' => '0172 7770011', 'private_phone' => null, 'email' => 'n.weber@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pdl'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => 'Tour-Verantwortung Spätschicht, Vertretung Notfallbeauftragte.', 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['pflege_3'], 'first_name' => 'Beate', 'last_name' => 'Richter', 'position' => 'Pflegefachkraft', 'department' => 'Pflege', 'work_phone' => null, 'mobile_phone' => '0172 7770012', 'private_phone' => null, 'email' => 'b.richter@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pflege_1'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['pflege_4'], 'first_name' => 'Yvonne', 'last_name' => 'Krause', 'position' => 'Pflegefachkraft', 'department' => 'Pflege', 'work_phone' => null, 'mobile_phone' => '0172 7770013', 'private_phone' => null, 'email' => 'y.krause@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pflege_2'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['helfer_1'], 'first_name' => 'Tanja', 'last_name' => 'Schäfer', 'position' => 'Pflegehelferin', 'department' => 'Pflege', 'work_phone' => null, 'mobile_phone' => '0172 7770020', 'private_phone' => null, 'email' => 't.schaefer@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pflege_1'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['helfer_2'], 'first_name' => 'Ramona', 'last_name' => 'Köhler', 'position' => 'Pflegehelferin', 'department' => 'Pflege', 'work_phone' => null, 'mobile_phone' => '0172 7770021', 'private_phone' => null, 'email' => 'r.koehler@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pflege_2'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dispo'], 'first_name' => 'Heike', 'last_name' => 'Brandt', 'position' => 'Tourenplanung / Disposition', 'department' => 'Verwaltung', 'work_phone' => '07131 555 210', 'mobile_phone' => '0172 7770030', 'private_phone' => null, 'email' => 'h.brandt@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pdl'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Hauptanwenderin Pflege-Software, IT-Schnittstelle zum MSP.', 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['sekretariat'], 'first_name' => 'Manuela', 'last_name' => 'Henkel', 'position' => 'Sekretariat / Empfang', 'department' => 'Verwaltung', 'work_phone' => '07131 555 200', 'mobile_phone' => '0172 7770031', 'private_phone' => null, 'email' => 'm.henkel@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pdl'], 'is_key_personnel' => 1, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Hauptansprechpartnerin Telefon, Angehörigen-Kommunikation.', 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['qmb'], 'first_name' => 'Birgit', 'last_name' => 'Lehmann', 'position' => 'Qualitätsbeauftragte (QMB)', 'department' => 'Qualität', 'work_phone' => '07131 555 220', 'mobile_phone' => '0172 7770040', 'private_phone' => null, 'email' => 'b.lehmann@pflegeteam-mustermann.de', 'emergency_contact' => null, 'manager_id' => $emp['pdl'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'MDK-/MD-Prüfung, Dokumentationsqualität, Hygiene.', 'location_id' => $station, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb'], 'first_name' => 'Klaus', 'last_name' => 'Engel', 'position' => 'Datenschutzbeauftragter (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0172 7770050', 'private_phone' => null, 'email' => 'engel@datenschutz-extern.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => 'Externer DSB. Bei Datenpanne sofort Kontakt — Patientendaten besonders sensibel (Gesundheitsdaten Art. 9 DSGVO).', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['software'], 'name' => 'MEDIFOX DAN GmbH (Vivendi PEP)', 'type' => 'other', 'contact_name' => 'Support-Hotline', 'hotline' => '05121 28 29 1700', 'email' => 'support@medifoxdan.example', 'contract_number' => 'KD-PT-2026-441', 'sla' => '24/7 für Notfälle (P1), sonst Mo-Fr 7-19', 'notes' => 'Pflege-Software-Hersteller — KRITISCH: bei Ausfall steht die komplette Tour-Steuerung + Abrechnung. 24/7-Hotline ist vertraglich zugesichert.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['mds'], 'name' => 'Medizinischer Dienst (MD) Baden-Württemberg', 'type' => 'other', 'contact_name' => 'Geschäftsstelle Heilbronn', 'hotline' => '07131 9988 700', 'email' => 'heilbronn@md-bw.example', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Prüfinstanz Pflegequalität (ehem. MDK). Anlassprüfung kann jederzeit kommen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['gkv'], 'name' => 'GKV-Spitzenverband (DTA-Annahmestelle)', 'type' => 'other', 'contact_name' => 'Datenannahmestelle', 'hotline' => '030 206288-0', 'email' => 'dta@gkv-spitzenverband.example', 'contract_number' => 'IK-460999111', 'sla' => 'Mo-Fr 8-17', 'notes' => 'DTA-Verfahren §302 SGB V. Bei Übertragungs-Ausfall Eskalation an Annahmestelle.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['pflegekasse'], 'name' => 'AOK Baden-Württemberg (Pflegekasse)', 'type' => 'other', 'contact_name' => 'Sachbearbeitung Pflegedienste', 'hotline' => '07131 9990 0', 'email' => 'pflegedienste-hn@aok-bw.example', 'contract_number' => 'VV-2024-7766', 'sla' => 'Mo-Fr 8-17', 'notes' => 'Hauptkostenträger. Versorgungsvertrag, Pflegekosten-Abrechnung.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['steuer'], 'name' => 'Steuerkanzlei Wiedemann & Partner', 'type' => 'other', 'contact_name' => 'StB Wiedemann', 'hotline' => '07131 778899', 'email' => 'wiedemann@steuer-hn.example', 'contract_number' => 'M-PT-2025', 'sla' => 'Mo-Fr 8-17', 'notes' => 'Buchhaltung extern, Lohnabrechnung Pflegekräfte.', 'direct_order_limit' => 2000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['it'], 'name' => 'GesundheitsIT Süd GmbH (MSP)', 'type' => 'it_msp', 'contact_name' => 'Hr. Wolf', 'hotline' => '0711 4455 800', 'email' => 'noc@gesundheits-it.example', 'contract_number' => 'MSP-PT-2026', 'sla' => 'Mo-Fr 7-19, Notfall 24/7 mit Aufpreis', 'notes' => 'IT-MSP spezialisiert auf Pflege/Heilberufe (DSGVO Art. 9, KIM/TI bereit).', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'Aufsichtsbehörde für DSGVO Art. 33-Meldungen. Patientendaten = besondere Kategorie.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['telefon'], 'name' => 'Telefon-Erreichbarkeit (24/7)', 'description' => 'Cloud-VoIP mit Rufweiterleitung; nachts auf Bereitschafts-Mobil PDL/stv. PDL. Gesetzliche Pflicht zur 24/7-Erreichbarkeit nach SGB XI.', 'category' => 'basisbetrieb', 'rto_minutes' => 15, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'SOFORT: alle eingehenden Anrufe auf Bereitschafts-Mobil 0172 7770001 (PDL) umleiten — Karte mit Nummer und Codes liegt im Schreibtisch Sekretariat. Bei Ausfall der Mobilnetzes: Notfall-Mobiltelefon (anderer Provider) aktivieren.', 'runbook_reference' => 'Runbook „Telefon-Ausfall — höchste Priorität" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['pflegesoft'], 'name' => 'Pflege-Software (Vivendi PEP)', 'description' => 'Zentrale Software für Stammdaten, Pflegeplanung, Tourenplanung, Leistungsabrechnung. Cloud-Hosting beim Hersteller.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Papier-Tourenpläne aus Notfall-Ordner (täglich gedruckt!) verwenden. Leistungserfassung handschriftlich auf Vordrucken. Hersteller-24/7-Hotline anrufen. Nacherfassung nach Wiederanlauf — wichtig wegen Abrechnung!', 'runbook_reference' => 'Runbook „Pflege-Software-Ausfall" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['mda'], 'name' => 'Mobile Pflege-App (MDA Smartphones/Tablets)', 'description' => 'Mobile Datenerfassung auf Tour-Geräten (Smartphones/Tablets der Pflegekräfte). Synchronisation mit Pflege-Software.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 300, 'fallback_process' => 'Offline-Modus der App nutzen, später synchronisieren. Bei Total-Ausfall der Geräte: Papier-Tourenpläne + handschriftliche Doku im Patienten-Ordner.', 'runbook_reference' => 'Runbook „MDA-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['gps'], 'name' => 'GPS / Tour-Tracking', 'description' => 'GPS-Ortung + Tour-Status (im Patientenhaus / unterwegs / Pause). In Pflege-Software integriert.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 50, 'fallback_process' => 'Manuelle Statusmeldung per SMS/Anruf an Sekretariat. Tour-Reihenfolge nach Papier-Plan.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['doku'], 'name' => 'Dokumentationssystem (Pflegedoku, gesetzlich)', 'description' => 'Elektronische Pflegedokumentation (in Vivendi). Gesetzlich vorgeschrieben (SGB XI), haftungsrelevant, Grundlage für Abrechnung + MD-Prüfung.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'Sofort-Umstellung auf Papier-Doku im Patienten-Ordner (Vordruck-Sätze in jedem Patientenhaus + Ersatz im Stations-Lager). Spätere lückenlose Übertragung in Software!', 'runbook_reference' => 'Runbook „Doku-Ausfall (gesetzlich)" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['dta'], 'name' => 'Abrechnung Krankenkasse (DTA-Verfahren §302)', 'description' => 'Datenträgeraustausch §302 SGB V mit Krankenkassen über GKV-Datenannahmestelle. Modul in Pflege-Software.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Abrechnungslauf um 1–3 Tage verschieben (kein direkter Patientenschaden). Bei längerem Ausfall: Liquiditäts-Engpass-Plan, Kontakt Hausbank.', 'runbook_reference' => 'Runbook „DTA-Abrechnung" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['eau'], 'name' => 'eAU / eRezept-Empfang', 'description' => 'Empfang elektronischer Verordnungen (häusliche Krankenpflege, Hilfsmittel) und eAU. Über Telematik-Infrastruktur (TI).', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Verordnungen in Papier von Patient/Hausarzt anfordern (Übergangsregelung). Faxgerät als Backup-Empfangsweg.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['dienstplan'], 'name' => 'Dienstplan-Software', 'description' => 'Modul in Vivendi: Schichtdienst-Planung, Arbeitszeit-Erfassung, Urlaubsverwaltung.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Dienstplan der nächsten 2 Wochen wird wöchentlich gedruckt im Sekretariat. Änderungen handschriftlich, Nacherfassung nach Wiederanlauf.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internet-Anbindung Sozialstation', 'description' => 'VDSL 100/40 Geschäftskunde + LTE-Backup-Router.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'LTE-Failover automatisch. Bei Total-Ausfall: Pflege-App im Offline-Modus, Telefon umleiten auf Bereitschafts-Mobil.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['mail'], 'name' => 'E-Mail (M365)', 'description' => 'Microsoft 365 Business Standard. Verwaltung, Angehörigen-Kontakt, Hausärzte.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'M365-Webmail mobil; bei Total-Ausfall Telefon + Fax als Übergang.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Sozialstation', 'description' => 'Hausanschluss + USV für Server-Schrank (Router, NAS).', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'USV überbrückt 30 Min für sauberen Shutdown. Touren laufen weiter (Pflege erfolgt beim Patienten zuhause), Sekretariat ggf. ins HomeOffice.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Cloud-VoIP. Bei Ausfall sofort Mobil-Umleitung!', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['pflegesoft'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Cloud-Software des Herstellers.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['mda'], 'depends_on_system_id' => $sys['pflegesoft'], 'sort' => 0, 'note' => 'MDA synchronisiert mit Pflege-Software.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['gps'], 'depends_on_system_id' => $sys['mda'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['doku'], 'depends_on_system_id' => $sys['pflegesoft'], 'sort' => 0, 'note' => 'Doku-Modul in Vivendi.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['dta'], 'depends_on_system_id' => $sys['pflegesoft'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['dta'], 'depends_on_system_id' => $sys['internet'], 'sort' => 1, 'note' => 'Übermittlung an GKV.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['eau'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'TI-Anbindung.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['dienstplan'], 'depends_on_system_id' => $sys['pflegesoft'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['mail'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'HDI Cyber+ Heilberufe', 'policy_number' => 'CY-PT-2026-099', 'hotline' => '0800 4434464', 'email' => 'cyber-heilberufe@hdi.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Hr. Lorenz', 'notes' => 'Deckung 750.000 €, Spezialtarif Heilberufe (Patientendaten Art. 9 DSGVO).', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'liability', 'insurer' => 'Versicherungskammer Bayern (BHV Heilberufe)', 'policy_number' => 'BHV-PT-2025-44', 'hotline' => '089 2160-0', 'email' => 'heilberufe-schaden@vkb.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Fr. Mayer', 'notes' => 'Berufshaftpflicht Pflegedienst, inkl. Vermögensschaden 3 Mio €.', 'deductible' => '500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-PT-2025-22', 'hotline' => '0800 1112020', 'email' => 'gewerbe-schaden@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Hr. Schubert', 'notes' => 'BU bis 30 Tage. Ertragsausfall Sozialstation.', 'deductible' => '1.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Angehörigen-Info bei Pflege-Verlegung', 'audience' => 'customers', 'channel' => 'phone', 'subject' => null, 'body' => "Anrufskript Angehörige:\n\nGuten Tag, hier {{ ansprechpartner }} vom {{ firma }}. Ich rufe wegen Frau/Herrn {{ patient }} an.\n\nAufgrund einer Störung in unserem System / kurzfristigen Personalausfall müssen wir die heutige Pflege:\n• verschieben auf {{ neue_zeit }}, ODER\n• von Kollegin {{ vertretung }} durchführen lassen, ODER\n• in Absprache mit Hausarzt anders organisieren.\n\nBitte rufen Sie uns bei Fragen unter 07131 555 200 (im Notfall: 0172 7770001) zurück.\n\nVielen Dank für Ihr Verständnis.", 'fallback' => 'Bei Nicht-Erreichen: 2. Versuch nach 30 Min, danach Hausarzt informieren.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Krankenkasse-Statusmeldung (E-Mail)', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Statusmeldung Pflegedienst {{ firma }} (IK 460999111)', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit informieren wir Sie als zuständige Pflegekasse über eine aktuelle technische Störung beim Pflegedienst {{ firma }}.\n\nStörungsbeginn: {{ zeitpunkt }}\nBetroffene Systeme: {{ systeme }}\nAuswirkung auf Versorgung: {{ auswirkung }}\nMaßnahmen: {{ massnahmen }}\nVoraussichtliche Behebung: {{ prognose }}\n\nDie Pflege-Versorgung der Patienten ist sichergestellt durch Papier-Tourenpläne und manuelle Dokumentation.\n\nDie Abrechnung gemäß §302 SGB V verzögert sich voraussichtlich um {{ verzug }} Tage.\n\nMit freundlichen Grüßen\n{{ pdl_name }}\nPflegedienstleitung", 'fallback' => 'Telefonischer Anruf bei Sachbearbeitung Pflegekasse parallel zur Mail.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Mitarbeiter-Schichtanweisung (SMS-Verteiler)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'WICHTIG {{ firma }}: Pflege-Software aktuell gestört. Bitte Papier-Tourenplan im Stations-Briefkasten abholen ODER von PDL/Tour-Leitung erhalten. Doku auf Papier-Vordruck. Keine Patientendaten per privater App weitergeben! Rückfragen: 0172 7770010 (S. Hoffmann). Stand: {{ zeitpunkt }}.', 'fallback' => 'Telefonkette: PDL → Tour-Leitungen → Pflegekräfte/Helfer.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Aufsichtsbehörde (Patientendaten)', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – {{ firma }} (besondere Kategorien)', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit melden wir gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten besonderer Kategorien (Gesundheitsdaten Art. 9).\n\nVerantwortlicher: {{ firma }}\nBranche: Ambulanter Pflegedienst (SGB XI)\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\nBetroffene Personen (geschätzt): {{ anzahl }}\nMaßnahmen: {{ massnahmen }}\nDSB: Klaus Engel (extern)\n\nMit freundlichen Grüßen\n{{ pdl_name }}\nPflegedienstleitung", 'fallback' => null, 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Bereitschaftsweiterleitung Telefon (Aushang)', 'audience' => 'employees', 'channel' => 'notice', 'subject' => 'Telefon-Notfall – Sofort-Maßnahme', 'body' => "AUSHANG SEKRETARIAT — TELEFON-NOTFALL\n\n1. Schritt: Cloud-VoIP-Portal öffnen → Rufweiterleitung auf 0172 7770001 (PDL)\n2. Wenn Portal nicht erreichbar: Provider-Hotline anrufen (im Notfall-Ordner)\n3. Notfall-Mobiltelefon (anderer Provider) aus Tresor → SIM-Karte aktiv halten\n4. Kollegin/Kollegen mit zentraler Nummer informieren (Tour-Verantwortliche)\n5. Bei Patient-/Angehörigen-Anrufen während Ausfall: Rückrufnummer 0172 7770001 nennen\n\nGesetzliche Erreichbarkeit (24/7) MUSS innerhalb 15 Min wiederhergestellt sein!", 'fallback' => 'Aushang Sekretariat + Übergaberaum, Karte beim PDL-Schreibtisch.', 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Papier-Tourenpläne (Tagesausdruck)', 'description' => 'Täglich automatischer Druck der Tagestouren am Vorabend (Frühschicht) bzw. morgens (Spätschicht). 3 Sätze: Sekretariat, PDL-Schreibtisch, Pausenraum.', 'location' => 'Sekretariat (Schublade 1), PDL-Büro, Pausenraum-Brett', 'access_holders' => 'Alle Pflegefachkräfte, PDL, Sekretariat, Disposition', 'last_check_at' => Helpers::date(-1), 'next_check_at' => Helpers::date(0), 'notes' => 'TÄGLICH neu zu drucken — wichtigste Notfall-Ressource! Cron-Job um 19:00 + 6:00.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_cash', 'name' => 'Bargeld-Notfallkasse', 'description' => '300 € in Scheinen + 50 € Münzgeld (für Tank, Mietwagen-Anfahrt, Kleinmaterial unterwegs).', 'location' => 'Tresor PDL-Büro', 'access_holders' => 'Martina Mustermann, Carolin Becker, Sandra Hoffmann', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Patientenliste mit Schlüssel-Aufbewahrung', 'description' => 'Vollständige Patientenliste mit Adressen, Hausarzt, Notfallkontakt, Schlüssel-Aufbewahrungsort (Schlüsselsafe-Code, Versteck, Türcode). Verschlossen aufbewahrt!', 'location' => 'Tresor PDL-Büro (Original) + Tresor Privat-Wohnung PDL (Backup-Kopie)', 'access_holders' => 'Martina Mustermann, Carolin Becker (verschlüsselte Kopie)', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => 'BESONDERS SENSIBEL — Zugang protokollieren. Schlüssel-Codes bei Patienten-Wechsel sofort aktualisieren.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Notfall-Mobiltelefone (2 Stück, anderer Provider)', 'description' => '2× Smartphone mit Vodafone-Prepaid-SIM (Hauptnetz Telekom). Aktivierung halbjährlich. Für Telefon-Failover und mobile Pflege-Software-App.', 'location' => 'Tresor PDL-Büro', 'access_holders' => 'Martina Mustermann, Carolin Becker, Manuela Henkel', 'last_check_at' => Helpers::date(-90), 'next_check_at' => Helpers::date(90), 'notes' => 'Halbjährlicher Aktivierungs-Anruf zwingend (sonst SIM-Sperre).', 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Papier-Pflegedokumentation (Vordrucke)', 'description' => 'Vordruck-Sätze für Pflegedoku, Leistungsnachweise, Vitalparameter, Wunddoku. Lückenlose Übertragung in Software nach Wiederanlauf zwingend!', 'location' => 'Stations-Lager (50 Sätze) + jeder Patienten-Ordner (1 Notfall-Satz)', 'access_holders' => 'Alle Pflegekräfte', 'last_check_at' => Helpers::date(-60), 'next_check_at' => Helpers::date(120), 'notes' => 'Bestand quartalsweise prüfen, Nachdruck rechtzeitig bestellen.', 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion inkl. Telefonliste, Playbooks, MEDIFOX-Hotline, Pflegekassen-Kontakte, Hausärzte-Liste.', 'location' => '1× PDL-Büro, 1× stv. PDL (privat), 1× Sekretariat, 1× Pausenraum', 'access_holders' => 'PDL, stv. PDL, Tour-Leitungen, Sekretariat', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Quartals-Check Telefonliste + Bereitschaft', 'description' => 'Erreichbarkeit aller Krisenrollen + 24/7-Bereitschafts-Mobilkette + MEDIFOX-Hotline + Pflegekasse prüfen.', 'interval' => 'quarterly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(30), 'responsible_employee_id' => $emp['sekretariat'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'recovery', 'name' => 'Telefon-Failover-Test (24/7-Pflicht)', 'description' => 'Cloud-VoIP simuliert ausfallen → Umleitung auf Bereitschafts-Mobil → Anruf von extern → Antwort messen. RTO 15 Min!', 'interval' => 'quarterly', 'last_executed_at' => Helpers::date(-45), 'next_due_at' => Helpers::date(45), 'responsible_employee_id' => $emp['pdl'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Pflege-Software-Totalausfall', 'description' => 'Schreibtisch-Übung: Vivendi-Cloud nicht erreichbar 24h. Tour-Steuerung über Papier, Doku-Pflicht, Abrechnung verschieben, Hersteller-Eskalation.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['pdl'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test Pflegedokumentation', 'description' => 'Anbieter-Backup-Restore-Übung mit MEDIFOX (jährlich vertraglich vereinbart). Datenstand prüfen, Doku-Konsistenz validieren.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-200), 'next_due_at' => Helpers::date(165), 'responsible_employee_id' => $emp['dispo'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Angehörigen-Anrufkette-Test', 'description' => 'Test-Anrufe an alle Angehörigen-Hauptkontakte (Stichprobe 5 Patienten/Quartal). Erreichbarkeit dokumentieren, Daten aktualisieren.', 'interval' => 'quarterly', 'last_executed_at' => Helpers::date(-80), 'next_due_at' => Helpers::date(10), 'responsible_employee_id' => $emp['sekretariat'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['telefon'], 'title' => 'Bereitschafts-Mobil Akku/Tarif prüfen', 'description' => 'Prepaid-Guthaben + Akkulaufzeit beider Notfall-Mobiltelefone prüfen, Aktivierungsanruf.', 'due_date' => Helpers::date(20), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['telefon'], 'title' => 'Cloud-VoIP-Failover-Skript hinterlegen', 'description' => 'Schritt-für-Schritt-Anleitung im Sekretariats-Ordner aushängen, halbjährlich aktualisieren.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['pflegesoft'], 'title' => 'MEDIFOX-Patch-Stand prüfen', 'description' => 'Update-Plan mit Hersteller koordinieren, Sandbox-Test wenn möglich.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['mda'], 'title' => 'Tour-Geräte-Hardware-Check', 'description' => 'Akkus aller Smartphones prüfen, Display-Schäden, Schutzhüllen. Defekte austauschen.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['doku'], 'title' => 'Papier-Vordrucke Bestand prüfen', 'description' => 'Mindestbestand 50 Sätze Pflegedoku-Vordrucke im Stations-Lager + 1 Satz pro Patienten-Ordner.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['dta'], 'title' => 'DTA-Testübertragung GKV', 'description' => 'Quartalsweise Test-Übertragung an Datenannahmestelle, Quittung dokumentieren.', 'due_date' => Helpers::date(75), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['eau'], 'title' => 'TI-Konnektor-Status prüfen', 'description' => 'Verbindung Telematik-Infrastruktur, Zertifikate, Kartenleser-Funktion.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'LTE-Backup-Router scharfschalten', 'description' => 'VDSL-Stecker ziehen, LTE-Failover messen, Pflege-App-Sync prüfen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['mail'], 'title' => 'Phishing-Awareness Pflegekräfte', 'description' => 'Schulung speziell zu Phishing mit Patientendaten-Fokus.', 'due_date' => Helpers::date(120), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'USV-Akku-Test', 'description' => 'Last simulieren, Laufzeit dokumentieren.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
