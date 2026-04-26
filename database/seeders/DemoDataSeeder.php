<?php

namespace Database\Seeders;

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Enums\CrisisRole;
use App\Enums\EmergencyResourceType;
use App\Enums\HandbookTestInterval;
use App\Enums\HandbookTestType;
use App\Enums\IncidentType;
use App\Enums\Industry;
use App\Enums\InsuranceType;
use App\Enums\KritisRelevance;
use App\Enums\LegalForm;
use App\Enums\Nis2Classification;
use App\Enums\ReportingObligation;
use App\Enums\ScenarioRunMode;
use App\Enums\ServiceProviderType;
use App\Enums\TeamRole;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\Employee;
use App\Models\GlobalScenario;
use App\Models\HandbookTest;
use App\Models\HandbookVersion;
use App\Models\IncidentReport;
use App\Models\IncidentReportObligation;
use App\Models\InsurancePolicy;
use App\Models\Location;
use App\Models\Role;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use App\Models\ScenarioStep;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use App\Support\IndustryTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Seeds a ready-to-go demo account: login as max@mustermann.de / password.
     * Idempotent – safe to re-run.
     */
    public function run(): void
    {
        $this->callOnce(GlobalScenariosSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'max@mustermann.de'],
            [
                'name' => 'Max Mustermann',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_super_admin' => true,
            ],
        );

        if (! $user->is_super_admin) {
            $user->forceFill(['is_super_admin' => true])->save();
        }

        $team = $user->teams()->first() ?? Team::create([
            'name' => "{$user->name}'s Team",
            'slug' => Str::slug("{$user->name}'s Team"),
            'is_personal' => true,
        ]);

        if (! $user->belongsToTeam($team)) {
            $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
        }

        if ($user->current_team_id === null) {
            $user->forceFill(['current_team_id' => $team->id])->save();
        }

        $this->ensureSecondaryUser($team);

        $company = Company::updateOrCreate(
            ['team_id' => $team->id],
            [
                'name' => 'Musterfirma GmbH',
                'industry' => Industry::Handwerk,
                'legal_form' => LegalForm::GmbH,
                'kritis_relevant' => KritisRelevance::No,
                'nis2_classification' => Nis2Classification::NotAffected,
                'valid_from' => '2026-01-01',
                'cyber_insurance_deductible' => '1.500 €',
                'budget_it_lead' => 500,
                'budget_emergency_officer' => 2000,
                'budget_management' => 20000,
                'data_protection_authority_name' => 'LfDI Baden-Württemberg',
                'data_protection_authority_phone' => '0711 615541-0',
                'data_protection_authority_website' => 'https://www.baden-wuerttemberg.datenschutz.de',
                'employee_count' => 9,
                'locations_count' => 2,
            ],
        );

        $this->seedLocations($company);
        $this->seedEmployees($company);
        $this->seedRoles($company);
        $itProvider = $this->seedServiceProviders($company);
        $this->seedInsurancePolicy($company);
        $this->seedSystems($company, $itProvider);
        $this->enrichSystems($company);
        $this->seedSystemDependencies($company);
        $this->seedScenarios($company);
        $this->seedCommunicationTemplates($company);
        $this->seedScenarioRuns($company, $user);
        $this->seedIncidentReports($company);
        $this->seedHandbookVersions($company);
        $this->seedEmergencyResources($company);
        $this->seedHandbookTests($company);

        $this->command?->info('Demo-Daten bereit. Logins: max@mustermann.de / password · maxigreis@icloud.com / passworD321!1');
    }

    /**
     * Stellt sicher, dass der zweite Demo-Nutzer existiert und Mitglied
     * (Admin) des Demo-Teams ist. Admin, damit er die admin-gegateten
     * Bereiche (Versicherungen, Audit-Log, Vorlagen) sieht.
     */
    private function ensureSecondaryUser(Team $team): void
    {
        $secondary = User::firstOrCreate(
            ['email' => 'maxigreis@icloud.com'],
            [
                'name' => 'Maxi Greis',
                'password' => Hash::make('passworD321!1'),
                'email_verified_at' => now(),
            ],
        );

        if (! $secondary->belongsToTeam($team)) {
            $team->members()->attach($secondary, ['role' => TeamRole::Admin->value]);
        }

        if ($secondary->current_team_id === null) {
            $secondary->forceFill(['current_team_id' => $team->id])->save();
        }
    }

    private function seedLocations(Company $company): void
    {
        Location::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Hauptsitz'],
            [
                'street' => 'Musterstraße 1',
                'postal_code' => '70173',
                'city' => 'Stuttgart',
                'country' => 'DE',
                'is_headquarters' => true,
                'phone' => '0711 1234567',
                'notes' => 'Geschäftsführung, Büro, Empfang.',
                'sort' => 0,
            ],
        );

        Location::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Werkstatt Vaihingen'],
            [
                'street' => 'Industriestraße 42',
                'postal_code' => '70565',
                'city' => 'Stuttgart',
                'country' => 'DE',
                'is_headquarters' => false,
                'phone' => '0711 2345678',
                'notes' => 'Werkstatt, Materiallager, Auslieferung.',
                'sort' => 1,
            ],
        );
    }

    private function seedEmployees(Company $company): void
    {
        $employees = [
            [
                'first_name' => 'Max', 'last_name' => 'Mustermann',
                'position' => 'Geschäftsführer', 'department' => 'Geschäftsführung',
                'mobile_phone' => '0171 1234567', 'private_phone' => '07154 555666',
                'email' => 'max@mustermann.de',
                'crisis_role' => CrisisRole::Management,
                'is_crisis_deputy' => false,
                'is_key_personnel' => true,
            ],
            [
                'first_name' => 'Sabine', 'last_name' => 'Mustermann',
                'position' => 'Prokuristin', 'department' => 'Geschäftsführung',
                'mobile_phone' => '0171 1234568',
                'email' => 'sabine@mustermann.de',
                'crisis_role' => CrisisRole::Management,
                'is_crisis_deputy' => true,
                'is_key_personnel' => true,
            ],
            [
                'first_name' => 'Anna', 'last_name' => 'Beispiel',
                'position' => 'Büroleitung', 'department' => 'Verwaltung',
                'mobile_phone' => '0171 2345678', 'private_phone' => '0711 7778899',
                'email' => 'anna@mustermann.de',
                'crisis_role' => CrisisRole::EmergencyOfficer,
                'is_crisis_deputy' => false,
                'is_key_personnel' => true,
            ],
            [
                'first_name' => 'Bernd', 'last_name' => 'Schneider',
                'position' => 'Werkstattleitung', 'department' => 'Werkstatt',
                'mobile_phone' => '0171 3456789',
                'email' => 'bernd.schneider@mustermann.de',
                'crisis_role' => CrisisRole::EmergencyOfficer,
                'is_crisis_deputy' => true,
                'is_key_personnel' => false,
            ],
            [
                'first_name' => 'Dieter', 'last_name' => 'Klein',
                'position' => 'IT-Beauftragter (intern)', 'department' => 'Verwaltung',
                'mobile_phone' => '0171 4567890',
                'email' => 'dieter.klein@mustermann.de',
                'crisis_role' => CrisisRole::ItLead,
                'is_crisis_deputy' => false,
                'is_key_personnel' => true,
            ],
            [
                'first_name' => 'Carla', 'last_name' => 'Wagner',
                'position' => 'Datenschutzbeauftragte (extern)', 'department' => 'Compliance',
                'mobile_phone' => '0171 5678901',
                'email' => 'wagner@datenschutz-extern.example',
                'crisis_role' => CrisisRole::DataProtectionOfficer,
                'is_crisis_deputy' => false,
                'is_key_personnel' => true,
            ],
            [
                'first_name' => 'Eva', 'last_name' => 'Kommer',
                'position' => 'Marketing & Kommunikation', 'department' => 'Verwaltung',
                'mobile_phone' => '0171 6789012',
                'email' => 'eva.kommer@mustermann.de',
                'crisis_role' => CrisisRole::CommunicationsLead,
                'is_crisis_deputy' => false,
                'is_key_personnel' => false,
            ],
            [
                'first_name' => 'Tobias', 'last_name' => 'Fischer',
                'position' => 'Buchhaltung', 'department' => 'Verwaltung',
                'mobile_phone' => '0171 7890123',
                'email' => 'tobias.fischer@mustermann.de',
                'is_key_personnel' => false,
            ],
            [
                'first_name' => 'Jonas', 'last_name' => 'Müller',
                'position' => 'Geselle', 'department' => 'Werkstatt',
                'mobile_phone' => '0171 8901234',
                'email' => 'jonas.mueller@mustermann.de',
                'is_key_personnel' => false,
            ],
        ];

        foreach ($employees as $data) {
            Employee::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => $data['email'],
                ],
                array_merge(['company_id' => $company->id], $data),
            );
        }
    }

    private function seedRoles(Company $company): void
    {
        $roleEmployeeMap = [
            ['name' => 'Geschäftsleitung', 'description' => 'Geschäftsführung und Prokura.', 'sort' => 0, 'emails' => ['max@mustermann.de', 'sabine@mustermann.de']],
            ['name' => 'Buchhaltung', 'description' => 'Finanzbuchhaltung, Lohn, DATEV.', 'sort' => 1, 'emails' => ['tobias.fischer@mustermann.de']],
            ['name' => 'Verwaltung & Empfang', 'description' => 'Büroleitung, Auftragsannahme, Kommunikation.', 'sort' => 2, 'emails' => ['anna@mustermann.de', 'eva.kommer@mustermann.de']],
            ['name' => 'Werkstatt', 'description' => 'Werkstattleitung und Gesellen.', 'sort' => 3, 'emails' => ['bernd.schneider@mustermann.de', 'jonas.mueller@mustermann.de']],
            ['name' => 'IT', 'description' => 'IT-Beauftragter intern, Schnittstelle zum IT-Dienstleister.', 'sort' => 4, 'emails' => ['dieter.klein@mustermann.de']],
            ['name' => 'Datenschutz & Compliance', 'description' => 'Externe DSB und Compliance-Themen.', 'sort' => 5, 'emails' => ['wagner@datenschutz-extern.example']],
        ];

        foreach ($roleEmployeeMap as $data) {
            $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                [
                    'company_id' => $company->id,
                    'description' => $data['description'],
                    'sort' => $data['sort'],
                ],
            );

            $employeeIds = Employee::withoutGlobalScope(CurrentCompanyScope::class)
                ->where('company_id', $company->id)
                ->whereIn('email', $data['emails'])
                ->pluck('id')
                ->all();

            $existing = $role->employees()->pluck('employees.id')->all();
            $merged = array_values(array_unique(array_merge($existing, $employeeIds)));
            AssignmentSync::sync($role, $role->employees(), $merged);
        }
    }

    private function seedServiceProviders(Company $company): ServiceProvider
    {
        $providers = [
            [
                'name' => 'IT-Service GmbH',
                'type' => ServiceProviderType::ItMsp,
                'contact_name' => 'Peter Techniker',
                'hotline' => '0800 1234567',
                'email' => 'support@it-service.example',
                'contract_number' => 'K-4711',
                'sla' => 'Mo-Fr 8-18, Notfall 24/7',
                'direct_order_limit' => 5000,
                'notes' => 'Betreut Server, Netzwerk, Arbeitsplätze. Notfall-Hotline rund um die Uhr.',
            ],
            [
                'name' => 'TelCo Deutschland AG',
                'type' => ServiceProviderType::InternetProvider,
                'contact_name' => 'Störungsstelle',
                'hotline' => '0800 3300000',
                'email' => 'stoerung@telco.example',
                'contract_number' => 'GK-998877',
                'sla' => '24/7',
                'direct_order_limit' => null,
                'notes' => 'Geschäftskunden-Glasfaser 200/100, statische IP.',
            ],
            [
                'name' => 'Stadtwerke Stuttgart',
                'type' => ServiceProviderType::Utility,
                'contact_name' => 'Entstörungsdienst',
                'hotline' => '0711 289-2222',
                'email' => null,
                'contract_number' => 'Z-87654',
                'sla' => '24/7',
                'direct_order_limit' => null,
                'notes' => 'Strom + Gas. Bei Ausfall öffentlichen Störungsmelder prüfen.',
            ],
            [
                'name' => 'LfDI Baden-Württemberg',
                'type' => ServiceProviderType::DataProtectionAuthority,
                'contact_name' => 'Beschwerdestelle',
                'hotline' => '0711 615541-0',
                'email' => 'poststelle@lfdi.bwl.de',
                'contract_number' => null,
                'sla' => 'Mo-Fr 8-16',
                'direct_order_limit' => null,
                'notes' => 'Zuständige Datenschutz-Aufsichtsbehörde für DSGVO-Meldungen (Art. 33).',
            ],
            [
                'name' => 'BSI Meldestelle',
                'type' => ServiceProviderType::BsiReportingOffice,
                'contact_name' => 'Bürgertelefon',
                'hotline' => '0228 99 9582-0',
                'email' => 'meldestelle@bsi.bund.de',
                'contract_number' => null,
                'sla' => 'Mo-Fr 9-15',
                'direct_order_limit' => null,
                'notes' => 'Meldepflichtige Sicherheitsvorfälle (NIS2 / IT-SiG).',
            ],
            [
                'name' => 'Kanzlei Recht & Co.',
                'type' => ServiceProviderType::Other,
                'contact_name' => 'RA Hoffmann',
                'hotline' => '0711 9988770',
                'email' => 'hoffmann@recht-co.example',
                'contract_number' => 'M-2025-12',
                'sla' => 'Mo-Fr 9-17',
                'direct_order_limit' => 3000,
                'notes' => 'Wirtschafts- und IT-Recht. Bei Datenpanne / Ransomware sofort.',
            ],
        ];

        $itProvider = null;

        foreach ($providers as $data) {
            $provider = ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                array_merge(['company_id' => $company->id], $data),
            );

            if ($data['type'] === ServiceProviderType::ItMsp) {
                $itProvider = $provider;
            }
        }

        return $itProvider;
    }

    private function seedInsurancePolicy(Company $company): void
    {
        InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
            ['company_id' => $company->id, 'type' => InsuranceType::Cyber->value],
            [
                'insurer' => 'CyberSchutz24 AG',
                'policy_number' => 'CY-2026-4711',
                'hotline' => '0800 8765432',
                'email' => 'schaden@cyberschutz24.example',
                'reporting_window' => 'unverzüglich, spätestens 24 Stunden',
                'deductible' => '1.500 €',
                'contact_name' => 'Frau Hartmann (Sachbearbeitung)',
                'notes' => 'Deckung bis 500.000 €. Vor größeren Notfallausgaben Versicherer informieren.',
            ],
        );

        InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
            ['company_id' => $company->id, 'type' => InsuranceType::BusinessInterruption->value],
            [
                'insurer' => 'Allianz Sach AG',
                'policy_number' => 'BI-2025-99887',
                'hotline' => '0800 1112020',
                'email' => 'gewerbe@allianz.example',
                'reporting_window' => 'binnen 7 Tagen',
                'deductible' => '500 €',
                'contact_name' => 'Herr Berger',
                'notes' => 'Betriebsunterbrechung bis 30 Tage abgedeckt.',
            ],
        );
    }

    private function seedSystems(Company $company, ?ServiceProvider $itProvider): void
    {
        if ($company->systems()->count() > 0) {
            return;
        }

        $priorityIdByName = $company->systemPriorities()->pluck('id', 'name');
        $systems = IndustryTemplates::systemsFor(Industry::Handwerk->value) ?? [];

        foreach ($systems as $entry) {
            $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'name' => $entry['name'],
                'description' => $entry['description'],
                'category' => $entry['category'],
                'system_priority_id' => $entry['priority']
                    ? ($priorityIdByName[$entry['priority']] ?? null)
                    : null,
                'rto_minutes' => $entry['rto_minutes'],
                'rpo_minutes' => $entry['rpo_minutes'],
            ]);

            if ($itProvider && in_array($entry['name'], ['Büro-Server / Zentralrechner', 'Handwerkersoftware', 'E-Mail'], true)) {
                AssignmentSync::attach($system, $system->serviceProviders(), $itProvider->id);
            }
        }
    }

    private function enrichSystems(Company $company): void
    {
        $levels = $company->emergencyLevels()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->pluck('id', 'name');

        $priorityToLevel = [
            'Kritisch' => $levels['Kritisch'] ?? null,
            'Hoch' => $levels['Wichtig'] ?? null,
            'Normal' => $levels['Beobachten'] ?? null,
        ];

        $fallbackByKeyword = [
            'Strom' => ['fallback' => 'USV überbrückt 30 Min.; Generator anwerfen oder kontrollierter Shutdown.', 'runbook' => 'Runbook „Stromausfall" v1.1'],
            'Internet' => ['fallback' => 'Mobile Hotspots aus Notfall-SIM aktivieren; LTE-Router an kritischen Arbeitsplätzen.', 'runbook' => 'Runbook „Internet-Failover" v1.0'],
            'Netzwerk' => ['fallback' => 'Switch-Reset; Backup-Switch im IT-Schrank einsetzen.', 'runbook' => 'Runbook „Netzwerk-Recovery" v1.0'],
            'Telefon' => ['fallback' => 'Notfall-Rufnummer (Mobil GF) als Kunden-Hotline kommunizieren.', 'runbook' => 'Runbook „VoIP-Ausfall" v1.0'],
            'Server' => ['fallback' => 'Manueller Betrieb auf Papier; Zugriff auf Offline-Backup-USB für Stammdaten.', 'runbook' => 'Runbook „Server-Restore" v1.2'],
            'Kassensystem' => ['fallback' => 'Bargeld-Kasse + Papier-Bon; spätere digitale Nacherfassung.', 'runbook' => 'Runbook „POS-Ausfall" v1.0'],
            'Kartenterminal' => ['fallback' => 'Hinweisschild „Nur Bargeld"; Anbieter-Hotline kontaktieren.', 'runbook' => null],
            'Zahlungsabwicklung' => ['fallback' => 'Hinweisschild „Nur Bargeld"; Anbieter-Hotline kontaktieren.', 'runbook' => null],
            'Warenwirtschaft' => ['fallback' => 'Auftragsannahme telefonisch + Papier; Nacherfassung nach Wiederanlauf.', 'runbook' => 'Runbook „ERP-Restore" v1.0'],
            'ERP' => ['fallback' => 'Auftragsannahme telefonisch + Papier; Nacherfassung nach Wiederanlauf.', 'runbook' => 'Runbook „ERP-Restore" v1.0'],
            'Handwerkersoftware' => ['fallback' => 'Auftragsannahme telefonisch + Papier-Auftragsblock; Nacherfassung nach Wiederanlauf.', 'runbook' => 'Runbook „Handwerkersoftware" v1.0'],
            'Online-Shop' => ['fallback' => 'Hinweis-Banner „Wartungsmodus"; Bestellungen per E-Mail.', 'runbook' => 'Runbook „Shop-Recovery" v1.0'],
            'Lager' => ['fallback' => 'Bestand per Sichtprüfung; Bestellungen telefonisch beim Lieferanten.', 'runbook' => null],
            'Buchhaltung' => ['fallback' => 'DATEV-Online-Backup; Belege physisch ablegen, später erfassen.', 'runbook' => 'Runbook „DATEV-Restore" v1.0'],
            'CRM' => ['fallback' => 'Kundenstamm aus letztem Export (CSV); Anrufe in Notiz-App.', 'runbook' => null],
            'E-Mail' => ['fallback' => 'M365-Webmail (mobil) als Fallback; kritische Mails per SMS bestätigen.', 'runbook' => 'Runbook „M365 Recovery" v1.0'],
            'Cloud' => ['fallback' => 'Lokale Kopien auf Offline-Backup-USB; OneDrive Web-Login als Fallback.', 'runbook' => null],
            'Alarm' => ['fallback' => 'Manuelle Sichtkontrolle; Wachdienst informieren.', 'runbook' => null],
            'Video' => ['fallback' => 'Manuelle Sichtkontrolle; Wachdienst informieren.', 'runbook' => null],
        ];

        $systems = System::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->with('priority')
            ->get();

        foreach ($systems as $system) {
            $updates = [];

            if ($system->emergency_level_id === null && $system->priority) {
                $levelId = $priorityToLevel[$system->priority->name] ?? null;
                if ($levelId !== null) {
                    $updates['emergency_level_id'] = $levelId;
                }
            }

            $detail = null;
            foreach ($fallbackByKeyword as $keyword => $candidate) {
                if (str_contains(mb_strtolower($system->name), mb_strtolower($keyword))) {
                    $detail = $candidate;
                    break;
                }
            }

            if ($detail) {
                if ($system->fallback_process === null) {
                    $updates['fallback_process'] = $detail['fallback'];
                }
                if ($system->runbook_reference === null && $detail['runbook'] !== null) {
                    $updates['runbook_reference'] = $detail['runbook'];
                }
            }

            if ($updates !== []) {
                $system->forceFill($updates)->save();
            }
        }
    }

    private function seedSystemDependencies(Company $company): void
    {
        $bySimplifiedName = System::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('name');

        $findFirst = function (array $candidates) use ($bySimplifiedName): ?string {
            foreach ($candidates as $name) {
                foreach ($bySimplifiedName as $sysName => $sys) {
                    if (str_contains(mb_strtolower($sysName), mb_strtolower($name))) {
                        return $sys->id;
                    }
                }
            }

            return null;
        };

        $power = $findFirst(['Strom']);
        $network = $findFirst(['Netzwerk', 'WLAN']);
        $internet = $findFirst(['Internet']);
        $telephone = $findFirst(['Telefon']);
        $server = $findFirst(['Server', 'Zentralrechner']) ?? $network;
        $email = $findFirst(['E-Mail']);
        $cloud = $findFirst(['Cloud']);
        $erp = $findFirst(['Warenwirtschaft', 'ERP', 'Handwerkersoftware']);
        $pos = $findFirst(['Kassensystem', 'POS']);
        $payment = $findFirst(['Zahlung', 'Kartenterminal']);
        $shop = $findFirst(['Online-Shop']);
        $accounting = $findFirst(['Buchhaltung']);
        $crm = $findFirst(['CRM']);
        $stock = $findFirst(['Lager']);
        $alarm = $findFirst(['Alarm', 'Video']);

        $deps = [
            $internet => [$power],
            $network => [$power],
            $telephone => [$power, $internet],
            $server => [$power, $network],
            $email => [$internet],
            $cloud => [$internet],
            $erp => [$server],
            $pos => [$power, $network],
            $payment => [$internet],
            $shop => [$internet],
            $accounting => [$server],
            $crm => [$server],
            $stock => [$server],
            $alarm => [$power],
        ];

        foreach ($deps as $systemId => $dependsOnIds) {
            if (! $systemId) {
                continue;
            }
            foreach ($dependsOnIds as $sort => $depId) {
                if (! $depId || $depId === $systemId) {
                    continue;
                }

                DB::table('system_dependencies')->updateOrInsert(
                    ['system_id' => $systemId, 'depends_on_system_id' => $depId],
                    ['sort' => $sort, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        }
    }

    private function seedScenarios(Company $company): void
    {
        if ($company->scenarios()->withoutGlobalScope(CurrentCompanyScope::class)->exists()) {
            return;
        }

        $globals = GlobalScenario::where('is_active', true)
            ->with('steps')
            ->orderBy('sort')
            ->get();

        foreach ($globals as $global) {
            $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'name' => $global->name,
                'description' => $global->description,
                'trigger' => $global->trigger,
            ]);

            foreach ($global->steps as $step) {
                ScenarioStep::create([
                    'scenario_id' => $scenario->id,
                    'sort' => $step->sort,
                    'title' => $step->title,
                    'description' => $step->description,
                    'responsible' => $step->responsible,
                ]);
            }
        }
    }

    private function seedCommunicationTemplates(Company $company): void
    {
        $ransomwareScenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('name', 'like', '%Ransomware%')
            ->first();

        $outageScenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('name', 'like', '%Internet%')
            ->orWhere('name', 'like', '%Ausfall%')
            ->first();

        $templates = [
            [
                'name' => 'Erstmeldung Mitarbeiter (SMS)',
                'audience' => CommunicationAudience::Employees,
                'channel' => CommunicationChannel::Sms,
                'subject' => null,
                'body' => 'Wichtig: Bei {{ firma }} liegt aktuell eine Störung vor. Bitte keine E-Mails / Logins versuchen, keine USB-Sticks anstecken. Weisungen folgen über Anna Beispiel (0171 2345678). Stand: {{ zeitpunkt }}.',
                'fallback' => 'Aushang im Empfangsbereich + Werkstatt.',
                'scenario_id' => $ransomwareScenario?->id,
                'sort' => 0,
            ],
            [
                'name' => 'Mitarbeiter-Aushang Stromausfall',
                'audience' => CommunicationAudience::Employees,
                'channel' => CommunicationChannel::Notice,
                'subject' => null,
                'body' => 'Stromausfall am {{ datum }}. Server fahren kontrolliert herunter. Werkstatt-Maschinen vor Wiederzuschalten Sichtprüfung. Bei Fragen: Bernd Schneider.',
                'fallback' => null,
                'scenario_id' => $outageScenario?->id,
                'sort' => 1,
            ],
            [
                'name' => 'Kunden-Information (E-Mail)',
                'audience' => CommunicationAudience::Customers,
                'channel' => CommunicationChannel::Email,
                'subject' => 'Kurzfristige Einschränkung der Erreichbarkeit',
                'body' => "Sehr geehrte Damen und Herren,\n\naufgrund einer technischen Störung sind wir bei {{ firma }} aktuell eingeschränkt erreichbar.\n\nUnter der Notfallnummer 0171 1234567 ({{ ansprechpartner }}) sind wir für Sie da.\n\nWir arbeiten an der Behebung und melden uns, sobald der Normalbetrieb wieder läuft.\n\nMit freundlichen Grüßen\nIhr Team von {{ firma }}",
                'fallback' => 'Anruf durch Empfang an betroffene Bestandskunden.',
                'scenario_id' => null,
                'sort' => 2,
            ],
            [
                'name' => 'Pressemeldung (Vorlage GF-Freigabe)',
                'audience' => CommunicationAudience::Press,
                'channel' => CommunicationChannel::Email,
                'subject' => '{{ firma }}: Sicherheitsvorfall – aktueller Stand',
                'body' => "[NUR NACH FREIGABE DURCH GESCHÄFTSFÜHRUNG VERSENDEN]\n\nBei {{ firma }} ist es am {{ datum }} zu einem Sicherheitsvorfall gekommen. Wir haben unverzüglich Maßnahmen eingeleitet, externe IT-Forensik beauftragt und die zuständigen Behörden informiert.\n\nNach aktuellem Kenntnisstand sind keine Kundendaten betroffen. Wir werden weiter transparent informieren.\n\nRückfragen: {{ ansprechpartner }}, presse@mustermann.de",
                'fallback' => null,
                'scenario_id' => $ransomwareScenario?->id,
                'sort' => 3,
            ],
            [
                'name' => 'DSGVO-Meldung Aufsichtsbehörde',
                'audience' => CommunicationAudience::Authorities,
                'channel' => CommunicationChannel::Email,
                'subject' => 'Meldung gemäß Art. 33 DSGVO – {{ firma }}',
                'body' => "Sehr geehrte Damen und Herren,\n\nhiermit melden wir gemäß Art. 33 DSGVO einen Sicherheitsvorfall mit Bezug zu personenbezogenen Daten.\n\nVerantwortlicher: {{ firma }}\nZeitpunkt der Kenntnis: {{ zeitpunkt }}\nVorfall: {{ vorfall }}\n\nBetroffene Daten, Personenkreis und Folgen werden in der beigefügten Lagebeschreibung dokumentiert. Wir stehen für Rückfragen zur Verfügung.\n\nMit freundlichen Grüßen\n{{ ansprechpartner }}",
                'fallback' => null,
                'scenario_id' => null,
                'sort' => 4,
            ],
        ];

        foreach ($templates as $data) {
            CommunicationTemplate::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                array_merge(['company_id' => $company->id], $data),
            );
        }
    }

    private function seedScenarioRuns(Company $company, User $user): void
    {
        if (ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->exists()) {
            return;
        }

        $tabletopScenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('name', 'like', '%Ransomware%')
            ->first();

        $internetScenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('name', 'like', '%Internet%')
            ->orWhere('name', 'like', '%Ausfall%')
            ->first();

        if ($tabletopScenario) {
            $completedRun = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'scenario_id' => $tabletopScenario->id,
                'started_by_user_id' => $user->id,
                'title' => 'Tabletop Ransomware (Q2/2026)',
                'mode' => ScenarioRunMode::Drill,
                'started_at' => now()->setDate(2026, 4, 22)->setTime(9, 0),
                'ended_at' => now()->setDate(2026, 4, 22)->setTime(11, 30),
                'aborted_at' => null,
                'summary' => 'Tabletop-Übung erfolgreich. Schwachstellen: Offline-Backup-Pfad nicht eindeutig dokumentiert (→ Handbuch v1.2). DSGVO-Meldung erfolgte zeitlich knapp – Process verbessern.',
            ]);

            foreach ($tabletopScenario->steps as $i => $step) {
                ScenarioRunStep::create([
                    'scenario_run_id' => $completedRun->id,
                    'sort' => $step->sort,
                    'title' => $step->title,
                    'description' => $step->description,
                    'responsible' => $step->responsible,
                    'checked_at' => now()->setDate(2026, 4, 22)->setTime(9, 0)->addMinutes(5 * ($i + 1)),
                    'checked_by_user_id' => $user->id,
                    'note' => $i === 0 ? 'Geräte erfolgreich isoliert (simuliert).' : null,
                ]);
            }
        }

        if ($internetScenario) {
            $activeRun = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'scenario_id' => $internetScenario->id,
                'started_by_user_id' => $user->id,
                'title' => 'Internetausfall – aktive Lage',
                'mode' => ScenarioRunMode::Real,
                'started_at' => now()->subHours(2),
                'ended_at' => null,
                'aborted_at' => null,
                'summary' => null,
            ]);

            foreach ($internetScenario->steps as $i => $step) {
                ScenarioRunStep::create([
                    'scenario_run_id' => $activeRun->id,
                    'sort' => $step->sort,
                    'title' => $step->title,
                    'description' => $step->description,
                    'responsible' => $step->responsible,
                    'checked_at' => $i < 2 ? now()->subHours(2)->addMinutes(10 * ($i + 1)) : null,
                    'checked_by_user_id' => $i < 2 ? $user->id : null,
                    'note' => $i === 0 ? 'Provider-Hotline 0800 3300000 erreicht. Großstörung in Region bestätigt, ETA 4h.' : null,
                ]);
            }
        }
    }

    private function seedIncidentReports(Company $company): void
    {
        if (IncidentReport::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->exists()) {
            return;
        }

        $tabletopRun = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('mode', ScenarioRunMode::Drill->value)
            ->first();

        $reports = [
            [
                'title' => 'Phishing-Mail an Buchhaltung – verdächtiger Anhang',
                'type' => IncidentType::CyberAttack,
                'occurred_at' => now()->setDate(2026, 3, 18)->setTime(10, 30),
                'notes' => 'Mitarbeiterin der Buchhaltung erhielt Mail mit angeblichem Lieferschein-PDF. Anhang nicht geöffnet. Postfach gesichert, Sender blockiert. Keine Kompromittierung. DSGVO nicht relevant.',
                'scenario_run_id' => null,
                'obligations' => [
                    [
                        'obligation' => ReportingObligation::CyberInsurance,
                        'reported_at' => now()->setDate(2026, 3, 18)->setTime(11, 0),
                        'note' => 'Kurzmeldung an Versicherer (CY-2026-4711). Selbstmeldung als Vorsichtsmaßnahme.',
                    ],
                    [
                        'obligation' => ReportingObligation::EmployeeNotification,
                        'reported_at' => now()->setDate(2026, 3, 18)->setTime(14, 0),
                        'note' => 'Aushang + Team-Briefing zu aktuellen Phishing-Wellen.',
                    ],
                ],
            ],
            [
                'title' => 'Tabletop Ransomware (Übung) – Auswertung',
                'type' => IncidentType::CyberAttack,
                'occurred_at' => now()->setDate(2026, 4, 22)->setTime(9, 0),
                'notes' => 'Geplante Übung gemäß Testplan. Keine echten Daten betroffen. Lessons Learned in Versionshistorie 1.2 dokumentiert.',
                'scenario_run_id' => $tabletopRun?->id,
                'obligations' => [],
            ],
        ];

        foreach ($reports as $data) {
            $report = IncidentReport::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'scenario_run_id' => $data['scenario_run_id'],
                'title' => $data['title'],
                'type' => $data['type'],
                'occurred_at' => $data['occurred_at'],
                'notes' => $data['notes'],
            ]);

            foreach ($data['obligations'] as $obligation) {
                IncidentReportObligation::create([
                    'incident_report_id' => $report->id,
                    'obligation' => $obligation['obligation']->value,
                    'reported_at' => $obligation['reported_at'],
                    'note' => $obligation['note'],
                ]);
            }
        }
    }

    private function seedHandbookVersions(Company $company): void
    {
        $author = Employee::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('email', 'anna@mustermann.de')
            ->first();

        $approver = Employee::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('email', 'max@mustermann.de')
            ->first();

        $versions = [
            [
                'version' => '1.0',
                'changed_at' => '2026-01-05',
                'change_reason' => 'Erstversion nach BSI-Standard 200-4. Einführung Notfallorganisation, Eskalationsstufen, RACI-Matrix für Kernsysteme.',
                'approved_at' => '2026-01-15',
                'approved_by_name' => 'Max Mustermann (GF)',
            ],
            [
                'version' => '1.1',
                'changed_at' => '2026-04-10',
                'change_reason' => 'Quartals-Review: Telefonliste aktualisiert, neuer IT-Verantwortlicher Dieter Klein eingetragen, Cyber-Versicherung verlängert.',
                'approved_at' => '2026-04-15',
                'approved_by_name' => 'Max Mustermann (GF)',
            ],
            [
                'version' => '1.2',
                'changed_at' => '2026-04-23',
                'change_reason' => 'Tabletop-Übung Ransomware durchgeführt. Lessons Learned: Offline-Backup-Standort dokumentiert, Notfall-SIM ergänzt.',
                'approved_at' => null,
                'approved_by_name' => null,
            ],
        ];

        foreach ($versions as $data) {
            HandbookVersion::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                ['company_id' => $company->id, 'version' => $data['version']],
                array_merge([
                    'company_id' => $company->id,
                    'changed_by_employee_id' => $author?->id,
                    'approved_by_employee_id' => $data['approved_at'] ? $approver?->id : null,
                ], $data),
            );
        }
    }

    private function seedEmergencyResources(Company $company): void
    {
        $resources = [
            [
                'type' => EmergencyResourceType::EmergencyCash,
                'name' => 'Notfallkasse Empfang',
                'description' => '500 € in Scheinen + Kleingeld für Sofort-Käufe (Material, Treibstoff, Mietgeräte).',
                'location' => 'Tresor Empfang, Hauptsitz',
                'access_holders' => 'Anna Beispiel, Max Mustermann',
                'last_check_at' => '2026-04-01',
                'next_check_at' => '2026-07-01',
                'sort' => 0,
            ],
            [
                'type' => EmergencyResourceType::ReplacementHardware,
                'name' => 'Ersatz-Notebook (vorinstalliert)',
                'description' => 'Lenovo ThinkPad mit Standard-Image, Office, Handwerkersoftware-Client. Letzte Aktualisierung 2026-03.',
                'location' => 'IT-Schrank, Werkstatt Vaihingen',
                'access_holders' => 'Dieter Klein, IT-Service GmbH',
                'last_check_at' => '2026-03-15',
                'next_check_at' => '2026-06-15',
                'sort' => 1,
            ],
            [
                'type' => EmergencyResourceType::OfflineBackup,
                'name' => 'Offline-Backup (USB-Tresor)',
                'description' => 'Wöchentliches Offline-Backup auf 2x 4TB USB-Festplatten, rotierend. Letzter erfolgreicher Restore-Test: Q1/2026.',
                'location' => 'Tresor Geschäftsführung + Bankschließfach',
                'access_holders' => 'Max Mustermann, Anna Beispiel',
                'last_check_at' => '2026-04-20',
                'next_check_at' => '2026-04-27',
                'notes' => 'Wöchentliche Rotation Mo morgens. Bankschließfach-Backup monatlich.',
                'sort' => 2,
            ],
            [
                'type' => EmergencyResourceType::EmergencySim,
                'name' => 'Prepaid-SIM mit Hotspot',
                'description' => 'Telekom Prepaid 50GB, sofort einsetzbar bei Internet-Ausfall. Hotspot-fähig.',
                'location' => 'Schreibtisch-Schublade GF',
                'access_holders' => 'Max Mustermann, Sabine Mustermann',
                'last_check_at' => '2026-04-01',
                'next_check_at' => '2026-10-01',
                'sort' => 3,
            ],
            [
                'type' => EmergencyResourceType::OfflineDocs,
                'name' => 'Notfallhandbuch (Papier)',
                'description' => 'Aktuelle Druckversion des Handbuchs inkl. Telefonliste, Systemblättern, Playbooks.',
                'location' => '1x GF-Büro, 1x Werkstatt-Büro, 1x Privat GF',
                'access_holders' => 'GF, Notfallbeauftragte/r, IT-Lead',
                'last_check_at' => '2026-04-15',
                'next_check_at' => '2026-10-15',
                'sort' => 4,
            ],
            [
                'type' => EmergencyResourceType::PasswordSafe,
                'name' => 'Passwort-Safe Bitwarden Backup',
                'description' => 'Verschlüsselter Export der gemeinsamen Tresor-Vault, jährlich aktualisiert. Master-Passwort separat im Tresor.',
                'location' => 'Tresor Geschäftsführung (USB-Stick + Papier-Backup)',
                'access_holders' => 'Max Mustermann (Master-PW), Anna Beispiel (Tresor-Zugang)',
                'last_check_at' => '2026-01-15',
                'next_check_at' => '2027-01-15',
                'sort' => 5,
            ],
            [
                'type' => EmergencyResourceType::GeneratorUps,
                'name' => 'USV Serverraum (APC SmartUPS 1500)',
                'description' => 'Überbrückt 30 Min. Server-Last. Automatischer Shutdown nach 25 Min.',
                'location' => 'Serverraum Hauptsitz',
                'access_holders' => 'Dieter Klein, IT-Service GmbH',
                'last_check_at' => '2026-01-15',
                'next_check_at' => '2027-01-15',
                'notes' => 'Akku-Tausch alle 4 Jahre, nächster Tausch 2028.',
                'sort' => 6,
            ],
        ];

        foreach ($resources as $data) {
            EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                ['company_id' => $company->id, 'type' => $data['type']->value, 'name' => $data['name']],
                array_merge(['company_id' => $company->id], $data),
            );
        }
    }

    private function seedHandbookTests(Company $company): void
    {
        $emergencyOfficer = Employee::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('email', 'anna@mustermann.de')
            ->first();

        $itLead = Employee::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('email', 'dieter.klein@mustermann.de')
            ->first();

        $tests = [
            [
                'type' => HandbookTestType::ContactCheck,
                'name' => 'Halbjahres-Check Telefonliste',
                'description' => 'Alle Mobilnummern, Privat-Nummern und E-Mails der Krisenrollen testen. Erreichbarkeit Vertretungen verifizieren.',
                'interval' => HandbookTestInterval::Biannually,
                'last_executed_at' => '2026-01-15',
                'next_due_at' => '2026-07-15',
                'responsible_employee_id' => $emergencyOfficer?->id,
                'result_notes' => 'Alle Kontakte erreichbar. Zwei E-Mail-Adressen aktualisiert.',
                'sort' => 0,
            ],
            [
                'type' => HandbookTestType::Tabletop,
                'name' => 'Tabletop Ransomware-Szenario',
                'description' => 'Schreibtisch-Übung: Ransomware-Befall. Kommunikationskette, Eskalation, Versicherungs-Meldung durchspielen.',
                'interval' => HandbookTestInterval::Yearly,
                'last_executed_at' => '2026-04-22',
                'next_due_at' => '2027-04-22',
                'responsible_employee_id' => $emergencyOfficer?->id,
                'result_notes' => 'Verbesserungsbedarf: Offline-Backup-Pfad war nicht eindeutig dokumentiert. → V1.2 ergänzt.',
                'sort' => 1,
            ],
            [
                'type' => HandbookTestType::BackupRestore,
                'name' => 'Restore-Test Büro-Server',
                'description' => 'Voll-Restore aus Offline-Backup auf Test-Server. Datenvollständigkeit & Zeitbedarf messen.',
                'interval' => HandbookTestInterval::Yearly,
                'last_executed_at' => '2026-02-08',
                'next_due_at' => '2027-02-08',
                'responsible_employee_id' => $itLead?->id,
                'result_notes' => 'Restore erfolgreich, Dauer 3h 40min. Empfehlung: SSD-Backup ergänzen.',
                'sort' => 2,
            ],
            [
                'type' => HandbookTestType::Communication,
                'name' => 'SMS-Notfallkette',
                'description' => 'Test der SMS-Kommunikationskette bei E-Mail-Ausfall. Alle Mitarbeiter erhalten Test-SMS, antworten innerhalb 30 Min.',
                'interval' => HandbookTestInterval::Yearly,
                'last_executed_at' => '2026-03-15',
                'next_due_at' => '2027-03-15',
                'responsible_employee_id' => $emergencyOfficer?->id,
                'result_notes' => '8/9 innerhalb 30 Min. geantwortet. 1 Mitarbeiter im Urlaub.',
                'sort' => 3,
            ],
            [
                'type' => HandbookTestType::Recovery,
                'name' => 'Wiederanlauf-Test Kernsysteme',
                'description' => 'Vollständiger Wiederanlauf nach simuliertem Stromausfall. Reihenfolge: Strom → Netzwerk → Server → Anwendungen.',
                'interval' => HandbookTestInterval::BiYearly,
                'last_executed_at' => null,
                'next_due_at' => '2026-11-01',
                'responsible_employee_id' => $itLead?->id,
                'result_notes' => null,
                'sort' => 4,
            ],
        ];

        foreach ($tests as $data) {
            HandbookTest::withoutGlobalScope(CurrentCompanyScope::class)->updateOrCreate(
                ['company_id' => $company->id, 'type' => $data['type']->value, 'name' => $data['name']],
                array_merge(['company_id' => $company->id], $data),
            );
        }
    }
}
