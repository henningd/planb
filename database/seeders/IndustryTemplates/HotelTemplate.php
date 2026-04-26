<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class HotelTemplate implements Contract
{
    public function name(): string
    {
        return 'Hotel / Pension (Stadthotel, 15–40 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Dienstleistung;
    }

    public function description(): string
    {
        return 'Kleines bis mittleres Stadthotel mit Front Office, Housekeeping, Restaurant, Reservierung. Schwerpunkt PMS, Channel-Manager, Buchungsplattformen, Kassensystem, Schlüsselkartenanlage. Enthält: 14 Mitarbeiter mit Krisenrollen, 1 Standort mit Funktionsbereichen, 12 Systeme inkl. RTO/RPO, 7 Dienstleister, Versicherungen, Notfallvorlagen, Testplan.';
    }

    public function sort(): int
    {
        return 70;
    }

    public function payload(): array
    {
        // Stable IDs, damit FK-Verweise innerhalb des Payloads konsistent
        // bleiben. Beim Apply werden sie via regenerateIds neu gemappt.
        $hotel = Helpers::uuid();

        $emp = [
            'direktor' => Helpers::uuid(),
            'fo_leitung' => Helpers::uuid(),
            'fo_schicht1' => Helpers::uuid(),
            'fo_schicht2' => Helpers::uuid(),
            'fo_nacht' => Helpers::uuid(),
            'reservierung' => Helpers::uuid(),
            'hk_leitung' => Helpers::uuid(),
            'hk_personal' => Helpers::uuid(),
            'fb_leitung' => Helpers::uuid(),
            'fb_kueche' => Helpers::uuid(),
            'haustechnik' => Helpers::uuid(),
            'buchhaltung' => Helpers::uuid(),
            'it_extern' => Helpers::uuid(),
            'brandschutz' => Helpers::uuid(),
        ];

        $prov = [
            'pms' => Helpers::uuid(),
            'channel' => Helpers::uuid(),
            'isp' => Helpers::uuid(),
            'reinigung' => Helpers::uuid(),
            'waescherei' => Helpers::uuid(),
            'brandschutz' => Helpers::uuid(),
            'stadtwerke' => Helpers::uuid(),
        ];

        $sys = [
            'strom' => Helpers::uuid(),
            'internet' => Helpers::uuid(),
            'pms' => Helpers::uuid(),
            'channel' => Helpers::uuid(),
            'booking_engine' => Helpers::uuid(),
            'kasse' => Helpers::uuid(),
            'schluessel' => Helpers::uuid(),
            'telefon' => Helpers::uuid(),
            'wlan_gast' => Helpers::uuid(),
            'gebaeude' => Helpers::uuid(),
            'bma' => Helpers::uuid(),
            'cctv' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'Hotel Sonnenhof GmbH',
                'industry' => 'dienstleistung',
                'employee_count' => 28,
                'locations_count' => 1,
                'review_cycle_months' => 12,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'not_affected',
                'cyber_insurance_deductible' => '5.000 €',
                'budget_it_lead' => 1500,
                'budget_emergency_officer' => 5000,
                'budget_management' => 50000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
            ]],

            'locations' => [
                [
                    'id' => $hotel, 'name' => 'Hotel Sonnenhof', 'street' => 'Bahnhofstraße 5',
                    'postal_code' => '70173', 'city' => 'Stuttgart', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '0711 9988770',
                    'notes' => '4-Sterne-Stadthotel, 68 Zimmer, Restaurant, Bar, 2 Tagungsräume, Tiefgarage. Funktionsbereiche: Front Office, Housekeeping, F&B/Küche, Haustechnik, Verwaltung.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['direktor'], 'first_name' => 'Markus', 'last_name' => 'Brenner', 'position' => 'Hoteldirektor', 'department' => 'Direktion', 'work_phone' => '0711 9988771', 'mobile_phone' => '0171 3344550', 'private_phone' => '0711 1122334', 'email' => 'm.brenner@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fo_leitung'], 'first_name' => 'Sandra', 'last_name' => 'Keller', 'position' => 'Front-Office-Managerin', 'department' => 'Front Office', 'work_phone' => '0711 9988772', 'mobile_phone' => '0171 3344551', 'private_phone' => null, 'email' => 's.keller@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['direktor'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 0, 'notes' => 'Kennt PMS + Channel-Manager + Booking-Backends.', 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fo_schicht1'], 'first_name' => 'Tobias', 'last_name' => 'Lange', 'position' => 'Front Office Agent (Frühschicht)', 'department' => 'Front Office', 'work_phone' => '0711 9988770', 'mobile_phone' => '0171 3344552', 'private_phone' => null, 'email' => 't.lange@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['fo_leitung'], 'is_key_personnel' => 0, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fo_schicht2'], 'first_name' => 'Nadine', 'last_name' => 'Wiegand', 'position' => 'Front Office Agent (Spätschicht)', 'department' => 'Front Office', 'work_phone' => '0711 9988770', 'mobile_phone' => '0171 3344553', 'private_phone' => null, 'email' => 'n.wiegand@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['fo_leitung'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fo_nacht'], 'first_name' => 'Robert', 'last_name' => 'Köhler', 'position' => 'Night Auditor (Nachtschicht)', 'department' => 'Front Office', 'work_phone' => '0711 9988770', 'mobile_phone' => '0171 3344554', 'private_phone' => null, 'email' => 'r.koehler@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['fo_leitung'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'Erste Anlaufstelle nachts bei System-Ausfällen.', 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['reservierung'], 'first_name' => 'Petra', 'last_name' => 'Schuster', 'position' => 'Reservierungsleitung', 'department' => 'Reservierung', 'work_phone' => '0711 9988775', 'mobile_phone' => '0171 3344555', 'private_phone' => null, 'email' => 'p.schuster@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['fo_leitung'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'Pflegt Channel-Manager + Raten + Verfügbarkeiten.', 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['hk_leitung'], 'first_name' => 'Maria', 'last_name' => 'Soares', 'position' => 'Housekeeping-Leitung', 'department' => 'Housekeeping', 'work_phone' => '0711 9988776', 'mobile_phone' => '0171 3344556', 'private_phone' => null, 'email' => 'm.soares@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['direktor'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['hk_personal'], 'first_name' => 'Aleksandra', 'last_name' => 'Nowak', 'position' => 'Zimmermädchen', 'department' => 'Housekeeping', 'work_phone' => null, 'mobile_phone' => '0171 3344557', 'private_phone' => null, 'email' => null, 'emergency_contact' => null, 'manager_id' => $emp['hk_leitung'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fb_leitung'], 'first_name' => 'Stefan', 'last_name' => 'Ostermann', 'position' => 'F&B-Manager / Restaurantleitung', 'department' => 'F&B', 'work_phone' => '0711 9988778', 'mobile_phone' => '0171 3344558', 'private_phone' => null, 'email' => 's.ostermann@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['direktor'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['fb_kueche'], 'first_name' => 'Giovanni', 'last_name' => 'Russo', 'position' => 'Küchenchef', 'department' => 'Küche', 'work_phone' => '0711 9988779', 'mobile_phone' => '0171 3344559', 'private_phone' => null, 'email' => 'g.russo@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['fb_leitung'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'HACCP-Verantwortlicher Küche.', 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['haustechnik'], 'first_name' => 'Klaus', 'last_name' => 'Reichmann', 'position' => 'Haustechniker', 'department' => 'Haustechnik', 'work_phone' => '0711 9988780', 'mobile_phone' => '0171 3344560', 'private_phone' => '0711 4455667', 'email' => 'k.reichmann@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['direktor'], 'is_key_personnel' => 1, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => 'GLT, Heizung, Brandmeldeanlage, Schlüsselkartensystem-Backups.', 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung'], 'first_name' => 'Birgit', 'last_name' => 'Hofmann', 'position' => 'Buchhaltung / Controlling', 'department' => 'Verwaltung', 'work_phone' => '0711 9988781', 'mobile_phone' => '0171 3344561', 'private_phone' => null, 'email' => 'b.hofmann@hotel-sonnenhof.example', 'emergency_contact' => null, 'manager_id' => $emp['direktor'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $hotel, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['it_extern'], 'first_name' => 'Hannes', 'last_name' => 'Fechner', 'position' => 'IT-Betreuung (extern)', 'department' => 'IT', 'work_phone' => null, 'mobile_phone' => '0171 3344562', 'private_phone' => null, 'email' => 'fechner@hotel-it.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Betreut PMS-Anbindung, Netzwerk, Backups. Vor-Ort innerhalb 4h.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['brandschutz'], 'first_name' => 'Werner', 'last_name' => 'Tiedemann', 'position' => 'Brandschutzbeauftragter (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 3344563', 'private_phone' => null, 'email' => 'tiedemann@brandschutz-bw.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => 'Pflicht in Beherbergungsbetrieben. Quartalsbegehung.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['pms'], 'name' => 'Mews Systems s.r.o. (PMS)', 'type' => 'other', 'contact_name' => 'Mews Support DACH', 'hotline' => '+44 20 38 70 33 50', 'email' => 'support@mews.com', 'contract_number' => 'MEWS-2026-7788', 'sla' => '24/7 Chat + Notfall-Hotline', 'notes' => 'Property Management System (PMS). KRITISCH für Check-In/Out, Reservierung, Rechnung.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['channel'], 'name' => 'SiteMinder GmbH (Channel-Manager)', 'type' => 'other', 'contact_name' => 'Customer Success Team', 'hotline' => '+49 89 21090 9876', 'email' => 'support@siteminder.com', 'contract_number' => 'SM-998877', 'sla' => '24/7', 'notes' => 'Channel-Manager: verteilt Verfügbarkeiten an booking.com, Expedia, HRS, Direct-Booking-Engine. KRITISCH für Vermeidung von Überbuchungen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['isp'], 'name' => 'TelCo Deutschland AG', 'type' => 'internet_provider', 'contact_name' => 'Geschäftskunden-Hotline', 'hotline' => '0800 3300000', 'email' => 'gk-stoerung@telco.example', 'contract_number' => 'GK-HOTEL-2233', 'sla' => '24/7 mit 4h-Reaktion', 'notes' => 'Glasfaser 1 Gbit/s symmetrisch + redundante LTE-Backup-Box; Gäste-WLAN + PMS-Verkehr getrennt.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['reinigung'], 'name' => 'CleanService Süd GmbH', 'type' => 'other', 'contact_name' => 'Disposition', 'hotline' => '0711 5544330', 'email' => 'disposition@cleanservice-sued.example', 'contract_number' => 'CL-2025-44', 'sla' => 'Mo-So 6-22, Notdienst 24/7', 'notes' => 'Aushilfsreinigung Tagungsräume + Notfall-Sonderreinigung.', 'direct_order_limit' => 2000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['waescherei'], 'name' => 'Großwäscherei Schwabenland GmbH', 'type' => 'other', 'contact_name' => 'Disposition Stuttgart', 'hotline' => '0711 6677990', 'email' => 'dispo.stuttgart@waescherei-schwabenland.example', 'contract_number' => 'WAS-2025-11', 'sla' => 'tägliche Lieferung Mo-Sa, Notlieferung 12h', 'notes' => 'Bettwäsche, Handtücher, Tischwäsche.', 'direct_order_limit' => 1500, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['brandschutz'], 'name' => 'Tiedemann Brandschutz Süd GmbH', 'type' => 'other', 'contact_name' => 'Werner Tiedemann', 'hotline' => '0711 8899001', 'email' => 'service@brandschutz-bw.example', 'contract_number' => 'BS-2024-09', 'sla' => 'Notdienst 24/7, Wartung quartalsweise', 'notes' => 'Brandmeldeanlage + Sprinkler + jährliche Brandschutz-Begehung.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['stadtwerke'], 'name' => 'Stadtwerke Stuttgart', 'type' => 'utility', 'contact_name' => 'Entstörungsdienst Strom + Wasser', 'hotline' => '0711 289-2222', 'email' => null, 'contract_number' => 'Z-HOTEL-665544', 'sla' => '24/7', 'notes' => 'Strom, Gas, Wasser. Bei Großstörung öffentlichen Störungsmelder prüfen.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['strom'], 'name' => 'Stromversorgung Hotel', 'description' => 'Hausanschluss + Notstrom-Diesel für Notbeleuchtung + Aufzüge + zentrale Server.', 'category' => 'basisbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 1500, 'fallback_process' => 'Notstrom-Diesel startet automatisch (≤ 30s); Front Office wechselt auf manuelle Schlüssel-Karte; Gäste über aushängende Schilder informieren.', 'runbook_reference' => 'Runbook „Stromausfall Hotel" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['internet'], 'name' => 'Internetzugang + LTE-Backup', 'description' => 'Glasfaser 1 Gbit/s + LTE-Failover; trennt Gäste-WLAN von PMS-Netz (VLAN).', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 1200, 'fallback_process' => 'Automatisches LTE-Failover; bei längerem Ausfall PMS-Offline-Modus + manuelle Reservierungsbücher.', 'runbook_reference' => 'Runbook „Internet-Failover Hotel" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['pms'], 'name' => 'PMS Mews (Cloud)', 'description' => 'Property Management System: Reservierung, Check-In/Out, Rechnung, Statistiken.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 30, 'downtime_cost_per_hour' => 2000, 'fallback_process' => 'Manuelle Reservierungsbücher (Print-Out vom Vortag) + Papier-Check-In-Bogen + Bargeldzahlung; Mews-Status-Page prüfen, Support kontaktieren.', 'runbook_reference' => 'Runbook „PMS-Ausfall" v1.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['channel'], 'name' => 'Channel-Manager SiteMinder', 'description' => 'Synchronisation Verfügbarkeiten + Raten zwischen PMS und OTAs (booking.com, Expedia, HRS, Direct).', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 30, 'downtime_cost_per_hour' => 1500, 'fallback_process' => 'OTA-Extranets manuell schließen oder Restbestand auf 0 setzen, um Überbuchungen zu vermeiden; Reservierungs-Mails parallel manuell pflegen.', 'runbook_reference' => 'Runbook „Channel-Manager-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['booking_engine'], 'name' => 'Online Booking Engine (Direkt-Buchung Webseite)', 'description' => 'Buchungsformular auf Hotel-Webseite, angebunden an Channel-Manager.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'Webseite zeigt Banner „Buchung bitte telefonisch" + Telefonnummer Reservierung; Mail-Anfragen aktiv beantworten.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['kasse'], 'name' => 'Kassensystem Restaurant', 'description' => 'GoBD-konformes Kassensystem mit TSE für Restaurant + Bar.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 300, 'fallback_process' => 'Papier-Bons + nachträgliche Erfassung; Bargeldzahlung. Hinweisschild „Kartenzahlung aktuell nicht möglich".', 'runbook_reference' => 'Runbook „Kassen-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['schluessel'], 'name' => 'Schlüsselkartensystem (RFID, Salto)', 'description' => 'Elektronisches Schlüsselkartensystem für Zimmer + Backoffice + Tiefgarage.', 'category' => 'sicherheit', 'rto_minutes' => 120, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Papier-Schlüssel-Backup-System (Generalschlüssel + Etagen-Schlüssel) im Tresor Front Office; Gäste manuell durch Personal ins Zimmer begleiten.', 'runbook_reference' => 'Runbook „Schlüsselkarten-Ausfall" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['telefon'], 'name' => 'Telefonanlage (VoIP) + Zimmer-Telefonie', 'description' => 'Cloud-VoIP für Front Office + Zimmer-Apparate; Notfall-Hotline-Bandansage.', 'category' => 'basisbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Anrufweiterleitung Hauptnummer auf Mobil Front-Office-Manager; Notfall-Mobiltelefon Rezeption (siehe emergency_resources).', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['wlan_gast'], 'name' => 'Gäste-WLAN', 'description' => 'Captive-Portal mit Voucher-Code + AGB-Bestätigung; getrennt vom PMS-Netz.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 1440, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 50, 'fallback_process' => 'Hinweis an Rezeption + ggf. Hotspot-Voucher als Goodwill.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['gebaeude'], 'name' => 'Gebäudeleittechnik (Heizung, Lüftung, Klima)', 'description' => 'GLT für Heizung, Lüftung, Klimatisierung Zimmer + Tagungsräume.', 'category' => 'basisbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'Manuelle Steuerung an den Anlagen vor Ort; Haustechniker übernimmt; Gäste-Info bei Komfort-Einbußen.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['bma'], 'name' => 'Brandmeldeanlage (BMA)', 'description' => 'Aufgeschaltet auf Feuerwehr Stuttgart, Sprinkleranlage Tiefgarage.', 'category' => 'sicherheit', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Bei BMA-Ausfall: Brandwache (Pflicht!) durch Haustechnik + zusätzliches Personal; Gäste-Info; Brandschutz-Sachverständigen sofort einbinden.', 'runbook_reference' => 'Runbook „BMA-Störung" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['cctv'], 'name' => 'Videoüberwachung Eingang/Tiefgarage', 'description' => 'CCTV mit Aufzeichnung 72h, DSGVO-konform.', 'category' => 'sicherheit', 'rto_minutes' => 1440, 'rpo_minutes' => 4320, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Verstärkte Streifengänge Haustechnik; Beschilderung „Videoüberwachung gestört".', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['internet'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['pms'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'Mews ist cloud-based, ohne Internet kein Voll-Zugriff.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['channel'], 'depends_on_system_id' => $sys['pms'], 'sort' => 0, 'note' => 'SiteMinder synchronisiert in beide Richtungen mit PMS.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['channel'], 'depends_on_system_id' => $sys['internet'], 'sort' => 1, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['booking_engine'], 'depends_on_system_id' => $sys['channel'], 'sort' => 0, 'note' => 'Booking Engine zieht Verfügbarkeiten/Preise via Channel-Manager.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['kasse'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => 'TSE-Cloud-Anbindung + PMS-Buchungs-Verknüpfung.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['kasse'], 'depends_on_system_id' => $sys['pms'], 'sort' => 1, 'note' => 'Restaurant-Rechnung kann auf Zimmer gebucht werden.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['schluessel'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => 'Encoder + Schlösser brauchen Strom (Schlösser haben Akku-Puffer).', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['schluessel'], 'depends_on_system_id' => $sys['pms'], 'sort' => 1, 'note' => 'Karten-Codierung kommt aus PMS-Check-In.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['telefon'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['wlan_gast'], 'depends_on_system_id' => $sys['internet'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['gebaeude'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['bma'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => 'BMA mit Akku-Puffer, dann Notstrom.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cctv'], 'depends_on_system_id' => $sys['strom'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'CyberSchutz24 AG', 'policy_number' => 'CY-2026-HTL-9988', 'hotline' => '0800 8765432', 'email' => 'schaden@cyberschutz24.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'Frau Hartmann', 'notes' => 'Hotel-Tarif inkl. Gästedaten + Zahlungsdaten + Reputation. Deckung 1,5 Mio. €.', 'deductible' => '5.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-HTL-7766', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 90 Tage; deckt auch Stornokosten OTA bei System-bedingter Überbuchung.', 'deductible' => '2.500 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'liability', 'insurer' => 'HDI Versicherung AG', 'policy_number' => 'HOTEL-HP-44332', 'hotline' => '0511 6450', 'email' => 'schaden@hdi.example', 'reporting_window' => 'unverzüglich, spätestens 7 Tage', 'contact_name' => 'Herr Bechtold', 'notes' => 'Betriebshaftpflicht inkl. Gäste-Sachschäden + Brandschadens-Zusatz.', 'deductible' => '1.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Gäste-Info bei System-Ausfall (Print Front Office)', 'audience' => 'customers', 'channel' => 'notice', 'subject' => null, 'body' => "Liebe Gäste,\n\naufgrund einer technischen Störung kann es heute zu Verzögerungen beim Check-In, Check-Out oder bei der Zimmerschlüssel-Ausgabe kommen. Wir arbeiten mit Hochdruck an der Wiederherstellung und bitten herzlich um Ihre Geduld.\n\nIm Restaurant ist eine Bezahlung aktuell nur in bar oder per ec-Karte am Mobilterminal möglich.\n\nVielen Dank für Ihr Verständnis – wir entschuldigen uns aufrichtig für die Unannehmlichkeiten.\n\n{{ ansprechpartner }}\nFront Office Manager\n{{ firma }}", 'fallback' => 'Aushang an Rezeption + im Aufzug + auf Zimmer-TV-Begrüßungsbildschirm.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'booking.com / OTA Statusmeldung bei Channel-Manager-Ausfall', 'audience' => 'service_providers', 'channel' => 'email', 'subject' => 'Technische Störung Channel-Manager – manuelle Pflege bis auf Weiteres', 'body' => "Sehr geehrtes Team,\n\nwir informieren Sie hiermit, dass unser Channel-Manager seit {{ zeitpunkt }} gestört ist. Bis zur Wiederherstellung pflegen wir Verfügbarkeiten und Raten manuell direkt im Extranet.\n\nWir haben aus Vorsicht die letzten Restbestände auf 0 gesetzt, um Überbuchungen zu vermeiden. Bitte informieren Sie uns direkt per Mail an reservation@hotel-sonnenhof.example oder telefonisch unter 0711 9988775 über eingehende Reservierungen.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => 'Telefonisch beim jeweiligen OTA-Account-Manager nachfassen.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Pressemitteilung bei sicherheitsrelevantem Vorfall', 'audience' => 'press', 'channel' => 'email', 'subject' => 'Information zu einem aktuellen Vorfall im {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nim {{ firma }} ist es am {{ datum }} zu einem Vorfall gekommen. Sicherheit und Wohl unserer Gäste haben für uns höchste Priorität, daher haben wir umgehend folgende Maßnahmen eingeleitet:\n\n- {{ massnahme_1 }}\n- {{ massnahme_2 }}\n- Information der zuständigen Behörden ({{ behoerde }}).\n\nWir nehmen den Vorfall sehr ernst und arbeiten lückenlos an seiner Aufklärung. Für Rückfragen steht Ihnen unsere Direktion unter presse@hotel-sonnenhof.example zur Verfügung.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}\nDirektion {{ firma }}", 'fallback' => 'Vorab-Abstimmung mit Versicherung + Rechtsberatung.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'DSGVO-Meldung Gästedaten', 'audience' => 'authorities', 'channel' => 'email', 'subject' => 'Meldung gemäß Art. 33 DSGVO – Gästedaten – {{ firma }}', 'body' => "Sehr geehrte Damen und Herren,\n\nhiermit meldet die {{ firma }} gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu Gäste- und Zahlungsdaten.\n\nVerantwortlicher: {{ ansprechpartner }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\nBetroffene Datenkategorien: Gäste-Stammdaten, Aufenthaltsdaten, Zahlungsdaten (PCI-DSS-Bereich).\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}", 'fallback' => null, 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Mitarbeiter-Erstmeldung Schichtteam (SMS-Gruppe)', 'audience' => 'employees', 'channel' => 'sms', 'subject' => null, 'body' => 'Wichtig: Bei {{ firma }} liegt aktuell eine Störung vor (PMS / Schlüsselkarten / Internet). Front Office bitte auf Notfall-Verfahren wechseln (Papier-Reservierungsbuch + Backup-Schlüssel). Weitere Anweisungen über Sandra Keller (0171 3344551). Stand: {{ zeitpunkt }}.', 'fallback' => 'Aushang Personal-Eingang + Pause-Raum.', 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Manuelles Reservierungsbuch (Print Tagesübersicht)', 'description' => 'Tägliche Druckliste aller Anreisen + Abreisen + Auf-Zimmer-Buchungen, jeden Morgen 06:00 vom Night Auditor.', 'location' => 'Front Office Schubfach + Backoffice', 'access_holders' => 'Front Office Team, Direktion', 'last_check_at' => Helpers::date(-1), 'next_check_at' => Helpers::date(0), 'notes' => 'Tägliche Aktualisierung; auch bei kurzer PMS-Störung sofort nutzbar.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Papier-Schlüssel-Backup-System', 'description' => 'Notfall-Mechanik-Schlüssel für jede Etage + Generalschlüssel; Übergabe-Quittungs-Block.', 'location' => 'Tresor Front Office', 'access_holders' => 'Front Office Manager, Night Auditor, Direktor, Haustechniker', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => 'Vollständigkeit + Beschriftung quartalsweise prüfen.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_sim', 'name' => 'Notfall-Telefon Rezeption', 'description' => 'Mobiltelefon mit eigener Nummer (auf Webseite + booking.com im Notfall hinterlegbar).', 'location' => 'Tresor Front Office', 'access_holders' => 'Front Office Manager, Night Auditor', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => 'Akku monatlich laden, Guthaben quartalsweise prüfen.', 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'emergency_cash', 'name' => 'Bargeldreserve Front Office', 'description' => '3.000 € in Scheinen + 500 € Wechselgeld für Restaurant-Kasse-Notfall.', 'location' => 'Tresor Front Office (gesondertes Fach)', 'access_holders' => 'Direktor, Front Office Manager, Buchhaltung', 'last_check_at' => Helpers::date(-15), 'next_check_at' => Helpers::date(75), 'notes' => 'Doppelaufsicht beim Öffnen.', 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'replacement_hardware', 'name' => 'Mobiles ec-/Kreditkarten-Terminal', 'description' => 'GSM-Mobilterminal als Backup bei Kassen-/Internet-Ausfall.', 'location' => 'Tresor Front Office', 'access_holders' => 'Front Office Manager, F&B-Manager', 'last_check_at' => Helpers::date(-45), 'next_check_at' => Helpers::date(45), 'notes' => 'Eigene SIM, monatlich Test-Transaktion.', 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch + Fluchtwegeplan (Papier)', 'description' => 'Druckversion mit Telefonliste, Brandschutzordnung, BMA-Skript, Vertretungsregeln.', 'location' => '1× Rezeption, 1× Backoffice, 1× Haustechnik, 1× Direktor privat', 'access_holders' => 'Direktion, Front Office, Haustechnik, Brandschutzbeauftragter', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Halbjahres-Check Telefonliste + OTAs + Lieferanten', 'description' => 'Erreichbarkeit Direktion, FO-Schichten, Haustechnik, Mews-Support, SiteMinder, OTAs, Wäscherei, Brandschutz prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['fo_leitung'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop „PMS-Ausfall am Anreise-Samstag"', 'description' => 'Schreibtisch-Übung: Mews 4h aus, 50 Anreisen erwartet. Manuelle Reservierung + Schlüssel-Backup + Gäste-Info.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-200), 'next_due_at' => Helpers::date(165), 'responsible_employee_id' => $emp['fo_leitung'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop „Channel-Manager-Crash mit Überbuchungs-Welle"', 'description' => 'Schreibtisch-Übung: SiteMinder-Sync seit 6h ausgefallen, OTAs überbuchen, Walk-In-Gäste am Eingang.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-180), 'next_due_at' => Helpers::date(185), 'responsible_employee_id' => $emp['reservierung'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test PMS-Export', 'description' => 'Wöchentlichen PMS-Export (CSV/JSON) probeweise importieren in Test-Mews-Account.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['it_extern'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Brandschutz-Räumungsübung', 'description' => 'Vollräumung Hotel mit Gäste-Simulation; Pflicht-Übung Brandschutzkonzept.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(305), 'responsible_employee_id' => $emp['brandschutz'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['strom'], 'title' => 'Notstrom-Diesel Probelauf', 'description' => 'Monatlicher Probelauf 30 Min., Tank prüfen, Logbuch.', 'due_date' => Helpers::date(15), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['internet'], 'title' => 'LTE-Failover scharf testen', 'description' => 'Glasfaser physisch trennen, prüfen ob PMS + Kasse weiter funktionieren.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['pms'], 'title' => 'Tagesreport-Druck-Routine kontrollieren', 'description' => 'Print-Out aller Anreisen morgens 06:00 prüfen — auch bei Internet-Ausfall verfügbar?', 'due_date' => Helpers::date(7), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['channel'], 'title' => 'OTA-Extranet-Logins verifizieren', 'description' => 'booking.com, Expedia, HRS — Logins funktionsfähig + 2FA-Backup-Codes im Tresor?', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['kasse'], 'title' => 'TSE-Zertifikat Laufzeit prüfen', 'description' => 'Restlaufzeit TSE-Zertifikat dokumentieren, ggf. rechtzeitig erneuern.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['schluessel'], 'title' => 'Backup-Generalschlüssel Inventur', 'description' => 'Tresor öffnen, Schlüssel zählen, Quittungsblock prüfen, Übergabe-Übungen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['wlan_gast'], 'title' => 'Gäste-WLAN Captive-Portal AGB-Update', 'description' => 'Datenschutzhinweise + AGB-Stand prüfen, Captive-Portal aktualisieren.', 'due_date' => Helpers::date(120), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['bma'], 'title' => 'BMA Quartalswartung mit Brandschutz-SV', 'description' => 'Werner Tiedemann begleitet Quartalsbegehung, Prüfprotokoll archivieren.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['cctv'], 'title' => 'CCTV-Aufzeichnungs-Stichprobe + Löschfristen', 'description' => 'Stichprobe ältere Aufzeichnungen, 72h-Frist eingehalten? DSGVO-Verzeichnis aktualisieren.', 'due_date' => Helpers::date(90), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
