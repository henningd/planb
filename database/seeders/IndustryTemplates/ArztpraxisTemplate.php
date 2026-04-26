<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class ArztpraxisTemplate implements Contract
{
    public function name(): string
    {
        return 'Arztpraxis (hausärztlich, 3–10 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Dienstleistung;
    }

    public function description(): string
    {
        return 'Hausärztliche Einzelpraxis mit Telematikinfrastruktur (TI), KIM, eAU, eGK-Kartenleser. Schwerpunkt Patientenversorgung + KV-Abrechnung. Enthält: 8 Mitarbeiter mit Krisenrollen, 1 Standort, 11 Systeme inkl. RTO/RPO, 6 Dienstleister, Versicherungen, Notfallvorlagen, Testplan. Sektor Gesundheitswesen.';
    }

    public function sort(): int
    {
        return 60;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $praxis = Helpers::uuid();

        $emp = [
            'aerztin' => Helpers::uuid(),
            'arzt_angest' => Helpers::uuid(),
            'mfa_leitung' => Helpers::uuid(),
            'mfa1' => Helpers::uuid(),
            'mfa2' => Helpers::uuid(),
            'mfa_lehrling' => Helpers::uuid(),
            'empfang' => Helpers::uuid(),
            'dsb' => Helpers::uuid(),
        ];

        $prov = [
            'pvs' => Helpers::uuid(),
            'ti_provider' => Helpers::uuid(),
            'kv' => Helpers::uuid(),
            'msp' => Helpers::uuid(),
            'stb' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'pvs' => Helpers::uuid(),
            'ti_konnektor' => Helpers::uuid(),
            'egk_leser' => Helpers::uuid(),
            'kim' => Helpers::uuid(),
            'eau' => Helpers::uuid(),
            'rezeptdrucker' => Helpers::uuid(),
            'telefon' => Helpers::uuid(),
            'arznei_bestell' => Helpers::uuid(),
            'fileserver' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Dr. med. Anna Beispiel – Hausärztliche Praxis',
                'industry' => 'dienstleistung',
                'employee_count' => 8,
                'locations_count' => 1,
                'review_cycle_months' => 12,
                'legal_form' => 'einzelunternehmen',
                'kritis_relevant' => 'pending',
                'nis2_classification' => 'not_affected',
                'cyber_insurance_deductible' => '1.500 €',
                'budget_it_lead' => 800,
                'budget_emergency_officer' => 2500,
                'budget_management' => 15000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
            ]],

            'locations' => [
                [
                    'id' => $praxis, 'name' => 'Praxis Beispiel', 'street' => 'Hauptstraße 18',
                    'postal_code' => '70771', 'city' => 'Leinfelden-Echterdingen', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 7544220',
                    'notes' => 'EG-Praxis mit 3 Sprechzimmern, Anmeldung, Labor, Wartebereich. TI-Konnektor im verschlossenen Technikraum.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['aerztin'], 'first_name' => 'Anna', 'last_name' => 'Beispiel', 'position' => 'Praxisinhaberin / Hausärztin', 'department' => 'Ärztlich', 'work_phone' => '0711 7544221', 'mobile_phone' => '0171 9988770', 'private_phone' => '0711 5566778', 'email' => 'a.beispiel@praxis-beispiel.de', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => 'Schweigepflicht nach § 203 StGB.', 'location_id' => $praxis, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['arzt_angest'], 'first_name' => 'Bernhard', 'last_name' => 'Klein', 'position' => 'Angestellter Arzt (50%)', 'department' => 'Ärztlich', 'work_phone' => '0711 7544222', 'mobile_phone' => '0171 9988771', 'private_phone' => null, 'email' => 'b.klein@praxis-beispiel.de', 'emergency_contact' => null, 'manager_id' => $emp['aerztin'], 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => 'Vertretung Praxisinhaberin.', 'location_id' => $praxis, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['mfa_leitung'], 'first_name' => 'Claudia', 'last_name' => 'Wagner', 'position' => 'MFA / Praxismanagerin', 'department' => 'Anmeldung / Organisation', 'work_phone' => '0711 7544220', 'mobile_phone' => '0171 9988772', 'private_phone' => '0711 7654321', 'email' => 'c.wagner@praxis-beispiel.de', 'emergency_contact' => null, 'manager_id' => $emp['aerztin'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Kennt PVS, KV-Abrechnung, TI-Konnektor.', 'location_id' => $praxis, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['mfa1'], 'first_name' => 'Diana', 'last_name' => 'Hofer', 'position' => 'MFA', 'department' => 'Sprechzimmer / Labor', 'work_phone' => '0711 7544223', 'mobile_phone' => '0171 9988773', 'private_phone' => null, 'email' => 'd.hofer@praxis-beispiel.de', 'emergency_contact' => null, 'manager_id' => $emp['mfa_leitung'], 'is_key_personnel' => 0, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $praxis, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['mfa2'], 'first_name' => 'Elena', 'last_name' => 'Petrov', 'position' => 'MFA', 'department' => 'Anmeldung', 'work_phone' => '0711 7544224', 'mobile_phone' => '0171 9988774', 'private_phone' => null, 'email' => 'e.petrov@praxis-beispiel.de', 'emergency_contact' => null, 'manager_id' => $emp['mfa_leitung'], 'is_key_personnel' => 0, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $praxis, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['mfa_lehrling'], 'first_name' => 'Felix', 'last_name' => 'Bauer', 'position' => 'MFA-Auszubildender (3. LJ)', 'department' => 'Anmeldung / Labor', 'work_phone' => '0711 7544225', 'mobile_phone' => '0171 9988775', 'private_phone' => null, 'email' => 'f.bauer@praxis-beispiel.de', 'emergency_contact' => null, 'manager_id' => $emp['mfa_leitung'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $praxis, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['empfang'], 'first_name' => 'Gisela', 'last_name' => 'Roth', 'position' => 'Empfang / Telefonzentrale (Teilzeit)', 'department' => 'Anmeldung', 'work_phone' => '0711 7544220', 'mobile_phone' => '0171 9988776', 'private_phone' => null, 'email' => 'g.roth@praxis-beispiel.de', 'emergency_contact' => null, 'manager_id' => $emp['mfa_leitung'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $praxis, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb'], 'first_name' => 'Henrik', 'last_name' => 'Bergmann', 'position' => 'Datenschutzbeauftragter (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 9988777', 'private_phone' => null, 'email' => 'bergmann@arzt-datenschutz.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => 'Spezialisiert auf Arztpraxen (§ 203 StGB + DSGVO).', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['pvs'], 'name' => 'CompuGroup Medical (CGM Albis)', 'type' => 'other', 'contact_name' => 'CGM-Service Hausarzt', 'hotline' => '0261 8000-1100', 'email' => 'service.cgm-albis@cgm.com', 'contract_number' => 'PVS-2026-3344', 'sla' => '24/7 Notfall, Mo-Fr 8-18 Standard', 'notes' => 'Praxisverwaltungssystem (PVS). KRITISCH für alles: Patientenakte, Abrechnung, Rezepte.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['ti_provider'], 'name' => 'gematik / TI-Provider RISE', 'type' => 'other', 'contact_name' => 'TI-Service-Desk', 'hotline' => '030 4005440', 'email' => 'support@gematik.de', 'contract_number' => 'TI-SMC-B-998877', 'sla' => '24/7', 'notes' => 'Telematikinfrastruktur (TI-Konnektor, SMC-B-Karte, eHBA). KRITISCH für eRezept, eAU, KIM.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['kv'], 'name' => 'Kassenärztliche Vereinigung Baden-Württemberg', 'type' => 'other', 'contact_name' => 'IT-Hotline KV BW', 'hotline' => '0711 7875-3666', 'email' => 'it-hotline@kvbawue.de', 'contract_number' => 'BSNR-7891234', 'sla' => 'Mo-Fr 8-17', 'notes' => 'Anlaufstelle für Praxis-IT-Probleme + KV-Abrechnungs-Themen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['msp'], 'name' => 'PraxisIT Süd GmbH', 'type' => 'it_msp', 'contact_name' => 'Markus Vogel', 'hotline' => '0711 4433220', 'email' => 'support@praxis-it-sued.example', 'contract_number' => 'WAR-2025-99', 'sla' => 'Mo-Fr 8-18, Notfall 24/7 mit Pauschale', 'notes' => 'IT-Dienstleister speziell für Arztpraxen. Vor-Ort innerhalb 4h.', 'direct_order_limit' => 2000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['stb'], 'name' => 'Steuerkanzlei Müller & Partner', 'type' => 'other', 'contact_name' => 'Frau Müller', 'hotline' => '0711 6677889', 'email' => 'info@stb-mueller-partner.example', 'contract_number' => 'M-2024-22', 'sla' => 'Mo-Fr 9-17', 'notes' => 'Steuerberatung + Lohnabrechnung Praxis.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'LfDI Baden-Württemberg', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '0711 615541-0', 'email' => 'poststelle@lfdi.bwl.de', 'contract_number' => null, 'sla' => 'Mo-Fr 8-16', 'notes' => 'DSGVO Art. 33-Meldung + ergänzend Meldung an LMU/KV bei Patientendaten.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Praxis', 'description' => 'Hausanschluss + USV für Server, TI-Konnektor, eGK-Leser, Telefon.', 'category' => 'basisbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'USV überbrückt 30 Min.; bei längerem Ausfall Praxis schließen, dringende Fälle ins Krankenhaus verweisen.', 'runbook_reference' => 'Runbook „Stromausfall Praxis" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang', 'description' => 'Glasfaser 200/100 mit fester IP für TI + KIM.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'LTE-Stick für Notfall (eAU/eRezept blockiert dann); Papier-Rezept als Notfall-Fallback.', 'runbook_reference' => 'Runbook „Internet-Failover Praxis" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['pvs'], 'name' => 'Praxisverwaltungssystem CGM Albis (PVS)', 'description' => 'Zentrale Patientenakte, Terminkalender, Abrechnung, Dokumentation.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'Papier-Patientenkarte + Notfall-Anamnese-Bogen; Nacherfassung nach Wiederanlauf. CGM-Hotline anrufen.', 'runbook_reference' => 'Runbook „PVS-Ausfall" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['ti_konnektor'], 'name' => 'TI-Konnektor (Telematikinfrastruktur)', 'description' => 'Sichere Anbindung an die TI: Voraussetzung für eAU, eRezept, KIM, ePA, VSDM.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'TI-Provider RISE anrufen; Papier-Rezept (rotes Rezept) und Papier-AU mit handschriftlicher Bestätigung; Patient später erneut einbestellen.', 'runbook_reference' => 'Runbook „TI-Konnektor-Ausfall" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['egk_leser'], 'name' => 'eGK-Kartenleser (Versichertendatenmanagement)', 'description' => 'Stationäre eGK-Leser an Anmeldung + Sprechzimmer 1.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Backup-Kartenleser im IT-Schrank; manuelle Erfassung Versichertendaten + nachträglich VSDM.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['kim'], 'name' => 'KIM-Mailservice (verschlüsselt)', 'description' => 'Pflicht-Kommunikationsdienst der TI für Arztbriefe, eAU-Versand an Krankenkasse.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 240, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'eAU per Fax (Notfall-Übergangsregel der KBV) + Telefon. Arztbrief vorerst per Post.', 'runbook_reference' => 'Runbook „KIM-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['eau'], 'name' => 'eAU (elektronische AU)', 'description' => 'Erstellung + KIM-Versand der Arbeitsunfähigkeit an Krankenkasse.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 100, 'fallback_process' => 'Papier-Muster-1-Formular ausfüllen, Patient bringt zur Krankenkasse; nachträglich elektronisch nachreichen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['rezeptdrucker'], 'name' => 'Rezeptdrucker', 'description' => 'Spezialdrucker für Muster-16-Rezepte (rosa) + bei Bedarf BTM-Rezept.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 80, 'fallback_process' => 'Reserve-Drucker im IT-Schrank; handgeschriebenes Rezept bei akutem Notfall.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefon'], 'name' => 'Telefonanlage (VoIP) + Anrufbeantworter', 'description' => 'Mehrgeräte-Anlage Anmeldung + Sprechzimmer; Notfall-Bandansage.', 'category' => 'basisbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 250, 'fallback_process' => 'Anrufweiterleitung Hauptnummer auf Mobil Praxismanagerin; Notfall-Mobilnummer + Vertretung am Praxis-Aushang.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['arznei_bestell'], 'name' => 'Arzneimittel-Bestellsystem (Sprechstundenbedarf)', 'description' => 'Bestellung von Sprechstundenbedarf + Praxisverbrauch via Großhändler-Portal.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 30, 'fallback_process' => 'Telefonische Bestellung beim Großhändler; Notvorrat im Medikamentenschrank.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['fileserver'], 'name' => 'Praxis-Server (lokale PVS-Datenbank)', 'description' => 'Lokaler Server mit PVS-Datenbank + Verzeichnis für Arztbriefe (PDF) + Befunde.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 120, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 700, 'fallback_process' => 'Restore aus Offline-Backup-NAS oder verschlüsseltem Cloud-Backup; bei Totalausfall MSP + CGM einbinden.', 'runbook_reference' => 'Runbook „Server-Restore Praxis" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['pvs'], 'depends_on_system_id' => $sys['fileserver'], 'sort' => 0, 'note' => 'PVS nutzt lokale DB auf Praxis-Server.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['ti_konnektor'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'TI-Konnektor benötigt Internet für TI-Backbone.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['ti_konnektor'], 'depends_on_system_id' => $sys['strom'], 'sort' => 1, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['egk_leser'], 'depends_on_system_id' => $sys['ti_konnektor'], 'sort' => 0, 'note' => 'eGK-Leser sprechen mit TI-Konnektor (VSDM).', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['kim'], 'depends_on_system_id' => $sys['ti_konnektor'], 'sort' => 0, 'note' => 'KIM läuft über TI-Konnektor.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['eau'], 'depends_on_system_id' => $sys['kim'], 'sort' => 0, 'note' => 'eAU-Versand erfolgt per KIM.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['eau'], 'depends_on_system_id' => $sys['pvs'], 'sort' => 1, 'note' => 'eAU wird im PVS erstellt.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['rezeptdrucker'], 'depends_on_system_id' => $sys['pvs'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['arznei_bestell'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['fileserver'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'professional_liability', 'insurer' => 'Deutsche Ärzteversicherung AG', 'policy_number' => 'AHP-2026-66554', 'hotline' => '0221 1480', 'email' => 'schaden@aerzteversicherung.example', 'reporting_window' => 'unverzüglich, spätestens 7 Tage', 'contact_name' => 'Frau Schenk', 'notes' => 'Berufshaftpflicht Arzt + Praxis-Inventarversicherung. Deckung 5 Mio. €.', 'deductible' => '500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'CyberSchutz24 AG', 'policy_number' => 'CY-2026-MED-3322', 'hotline' => '0800 8765432', 'email' => 'schaden@cyberschutz24.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Herr Lindner', 'notes' => 'Speziell-Tarif Heilberufe inkl. Patientendaten-Krise. Deckung 750.000 €.', 'deductible' => '1.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-MED-887', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 30 Tage inkl. Mehraufwand Vertretungsarzt.', 'deductible' => '500 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Patienten-Info bei Praxisschluss (Aushang + Anrufbeantworter)', 'audience' => 'customers', 'channel' => 'notice', 'subject' => null, 'body' => "Liebe Patientinnen und Patienten,\n\naufgrund einer technischen Störung kann unsere Praxis am {{ datum }} nur eingeschränkt arbeiten / muss heute schließen.\n\nIn dringenden Fällen wenden Sie sich bitte an die Vertretungspraxis Dr. Müller, Hauptstraße 24, Tel. 0711 7544100, oder an den ärztlichen Bereitschaftsdienst unter 116 117. Bei lebensbedrohlichen Notfällen wählen Sie bitte 112.\n\nWir bitten um Ihr Verständnis und melden uns, sobald wir wieder erreichbar sind.\n\n{{ ansprechpartner }}", 'fallback' => 'Aushang an Eingangstür + Bandansage Telefon + Webseite.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Telefon-Verordnung Notfall-Karte (intern)', 'audience' => 'employees', 'channel' => 'notice', 'subject' => null, 'body' => "Bei TI- oder PVS-Ausfall, wenn ein Patient dringend ein Rezept benötigt:\n\n1. Patient identifizieren (Name, Geburtsdatum, ggf. Vers.-Nr.).\n2. Rückruf der Hausärztin/des Hausarztes, mündliche Verordnung dokumentieren.\n3. Papier-Rezept (Muster 16) handschriftlich ausfüllen + Stempel + Unterschrift.\n4. BTM nur mit BTM-Rezept; im Zweifel Notdienst nutzen.\n5. Nacherfassung im PVS sobald wieder verfügbar.", 'fallback' => 'Papier-Rezeptblock liegt im Anmeldeschrank.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'KIM-Empfänger-Info bei KIM-Ausfall', 'audience' => 'service_providers', 'channel' => 'phone', 'subject' => null, 'body' => "Skript: Krankenkasse anrufen wegen nicht zugestellter eAU.\n\n1. Name + BSNR der Praxis nennen.\n2. Patient identifizieren (Name, Geburtsdatum, Vers.-Nr.).\n3. AU-Daten mündlich durchgeben (Beginn, Ende, Diagnose-ICD).\n4. Vermerk in Patientenakte: \"eAU mündlich an KK übermittelt am {{ datum }}\".\n5. Sobald KIM wieder läuft: eAU regulär elektronisch nachsenden.", 'fallback' => 'Papier-AU als zusätzliche Absicherung an Patient mitgeben.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'KV-Meldung Praxis-Ausfall', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung Praxis-Ausfall – BSNR {{ bsnr }}', 'body' => "Sehr geehrte Damen und Herren,\n\ndie Praxis {{ firma }} (BSNR {{ bsnr }}) ist seit {{ zeitpunkt }} von einer technischen Störung betroffen ({{ vorfall }}).\n\nVoraussichtliche Wiederaufnahme: {{ erwartet_zurueck }}.\nVertretung erfolgt durch Dr. Müller, BSNR 7891111.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Telefonisch IT-Hotline KV BW: 0711 7875-3666.', 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Patientendaten an LfDI', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – Patientendaten – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit meldet {{ firma }} gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu Patientendaten (Art. 9 DSGVO).\n\nVerantwortliche: {{ ansprechpartner }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\nBetroffene Datenkategorien: Gesundheitsdaten, Versichertendaten, ggf. Diagnosen.\n\nAufgrund der besonderen Schutzbedürftigkeit prüfen wir parallel eine Information der Betroffenen nach Art. 34 DSGVO.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Papier-Rezeptblock (Muster 16, rosa)', 'description' => 'Unbedruckte Muster-16-Rezeptblöcke + 1 BTM-Rezeptblock im Tresor.', 'location' => 'Anmeldung verschlossen + Tresor (BTM)', 'access_holders' => 'Anna Beispiel, Bernhard Klein, Claudia Wagner', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => 'BTM-Block protokolliert nach BtMVV.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfall-Karte Telefon-Verordnung', 'description' => 'Laminierte Karte mit Skript für Telefon-Verordnung + Vertretungspraxis-Nummer + 116 117.', 'location' => 'Anmeldung sichtbar + jedes Sprechzimmer', 'access_holders' => 'alle Praxis-MA', 'last_check_at' => Helpers::date(-20), 'next_check_at' => Helpers::date(150), 'notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Backup-eGK-Kartenleser', 'description' => 'Mobiles eGK-Lesegerät (Cherry/Ingenico) als Reserve.', 'location' => 'IT-Schrank Technikraum', 'access_holders' => 'Claudia Wagner, Markus Vogel (MSP)', 'last_check_at' => Helpers::date(-60), 'next_check_at' => Helpers::date(30), 'notes' => 'Vor Einsatz: Update auf aktuelle Firmware.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Verschlüsseltes NAS-Backup PVS', 'description' => 'Tägliches Backup der PVS-Datenbank, AES-256, 2x rotierende NAS-Disks.', 'location' => 'Technikraum + Privat Anna Beispiel (rotierend)', 'access_holders' => 'Anna Beispiel, Markus Vogel (MSP)', 'last_check_at' => Helpers::date(-3), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentliche Disk-Rotation Mo morgens.', 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion mit Telefonliste, TI-Hotline, Vertretungsregelung, Playbooks.', 'location' => '1× Anmeldung, 1× Sprechzimmer 1, 1× Privat Praxisinhaberin', 'access_holders' => 'Praxisinhaberin, Praxismanagerin, Vertreter', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste + Vertretung', 'description' => 'Erreichbarkeit MA, MSP, CGM-Hotline, TI-Hotline, KV-IT, Vertretungspraxis prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['mfa_leitung'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop „TI-Konnektor-Ausfall an Montagmorgen"', 'description' => 'Schreibtisch-Übung: TI-Konnektor 4h aus, parallele Patientenflut, eAU/eRezept blockiert.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-200), 'next_due_at' => Helpers::date(165), 'responsible_employee_id' => $emp['mfa_leitung'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test PVS-Datenbank', 'description' => 'Vollrestore der PVS-DB auf Test-Server, Smoke-Test mit Demo-Patient.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['mfa_leitung'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Notfallkette Patienten-Info', 'description' => 'Test: Aushang an Eingang, Bandansage Telefon, Webseite-Banner innerhalb 1h.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(305), 'responsible_employee_id' => $emp['mfa_leitung'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'USV-Akku-Test Praxis', 'description' => 'Last simulieren, dokumentieren ob Server + TI-Konnektor + eGK-Leser sauber überbrückt werden.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'LTE-Stick-Funktionstest', 'description' => 'LTE-Stick einstecken, prüfen ob TI dennoch nicht zugelassen ist (erwartetes Verhalten).', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['pvs'], 'title' => 'CGM-Updates einspielen + smoke-test', 'description' => 'Quartalsupdate einspielen, Demo-Patient anlegen, Termin + Rezept testen.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['ti_konnektor'], 'title' => 'TI-Konnektor-Logs prüfen + Zertifikatslaufzeit', 'description' => 'Konnektor-Web-UI öffnen, Logs auf Anomalien, SMC-B/eHBA-Ablaufdaten dokumentieren.', 'due_date' => Helpers::date(20), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['kim'], 'title' => 'KIM-Versand-Test eAU', 'description' => 'Test-eAU an Test-Empfänger, Zustellbestätigung prüfen.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['egk_leser'], 'title' => 'Backup-Kartenleser scharf testen', 'description' => 'Backup-eGK-Leser anschließen, VSDM mit Test-eGK durchführen.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['rezeptdrucker'], 'title' => 'Reserve-Rezeptdrucker testen', 'description' => 'Reserve-Drucker anstöpseln, Muster-16 Probedruck (mit Rück-Schreddern).', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['fileserver'], 'title' => 'Restore-Test PVS-Backup auf Test-VM', 'description' => 'Letztes NAS-Backup auf Test-VM einspielen, PVS hochfahren, Smoke-Test.', 'due_date' => Helpers::date(180), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
