<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

class SoftwareUnternehmenTemplate implements Contract
{
    public function name(): string
    {
        return 'Software-Unternehmen / SaaS-Anbieter (20–50 MA)';
    }

    public function industry(): Industry
    {
        return Industry::Dienstleistung;
    }

    public function description(): string
    {
        return 'Remote-first SaaS-Anbieter mit kleinem Hauptbüro. Schwerpunkt Cloud-Hosting (AWS/Azure/GCP), CI/CD, APM und Public-Statuspage-Kommunikation. Enthält: 14 Mitarbeiter mit Krisenrollen, 1 Standort, 12 Systeme inkl. RTO/RPO, 7 Dienstleister (inkl. Hyperscaler-Premium-Support), Berufs-/Cyber-Versicherung, Statuspage- und Postmortem-Templates, On-Call-Schedule, Backup-Restore-Tests.';
    }

    public function sort(): int
    {
        return 120;
    }

    public function payload(): array
    {
        $office = Helpers::uuid();

        $emp = [
            'gf' => Helpers::uuid(),
            'cto' => Helpers::uuid(),
            'eng_lead' => Helpers::uuid(),
            'backend1' => Helpers::uuid(),
            'backend2' => Helpers::uuid(),
            'frontend' => Helpers::uuid(),
            'devops1' => Helpers::uuid(),
            'devops2' => Helpers::uuid(),
            'qa' => Helpers::uuid(),
            'pm' => Helpers::uuid(),
            'csm' => Helpers::uuid(),
            'sales' => Helpers::uuid(),
            'buchhaltung' => Helpers::uuid(),
            'dsb_extern' => Helpers::uuid(),
        ];

        $prov = [
            'aws' => Helpers::uuid(),
            'github' => Helpers::uuid(),
            'auth' => Helpers::uuid(),
            'stripe' => Helpers::uuid(),
            'pentest' => Helpers::uuid(),
            'lfdi' => Helpers::uuid(),
            'cloudflare' => Helpers::uuid(),
        ];

        $sys = [
            'cloud' => Helpers::uuid(),
            'k8s' => Helpers::uuid(),
            'db' => Helpers::uuid(),
            'cdn' => Helpers::uuid(),
            'sourcecode' => Helpers::uuid(),
            'cicd' => Helpers::uuid(),
            'apm' => Helpers::uuid(),
            'logs' => Helpers::uuid(),
            'statuspage' => Helpers::uuid(),
            'support' => Helpers::uuid(),
            'sso' => Helpers::uuid(),
            'wiki' => Helpers::uuid(),
        ];

        $ts = Helpers::now();

        return Helpers::payload([
            'company' => [[
                'name' => 'CloudMetric Analytics GmbH',
                'industry' => 'dienstleistung',
                'employee_count' => 14,
                'locations_count' => 1,
                'review_cycle_months' => 6,
                'legal_form' => 'gmbh',
                'kritis_relevant' => 'no',
                'nis2_classification' => 'important',
                'cyber_insurance_deductible' => '10.000 €',
                'budget_it_lead' => 10000,
                'budget_emergency_officer' => 5000,
                'budget_management' => 100000,
                'data_protection_authority_name' => 'Berliner Beauftragte für Datenschutz und Informationsfreiheit',
                'data_protection_authority_phone' => '030 13889-0',
                'data_protection_authority_website' => 'https://www.datenschutz-berlin.de',
            ]],

            'locations' => [
                [
                    'id' => $office, 'name' => 'Office Berlin (remote-first)', 'street' => 'Rosenthaler Straße 40',
                    'postal_code' => '10178', 'city' => 'Berlin', 'country' => 'DE',
                    'is_headquarters' => 1, 'phone' => '030 5557788',
                    'notes' => 'Kleines Office für hybride Workshops und Kundentermine. Großteil der Belegschaft remote in DACH.',
                    'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts,
                ],
            ],

            'employees' => [
                ['id' => $emp['gf'], 'first_name' => 'Julia', 'last_name' => 'Sommer', 'position' => 'Geschäftsführerin / CEO', 'department' => 'Geschäftsführung', 'work_phone' => '030 5557701', 'mobile_phone' => '0171 9988201', 'private_phone' => '030 5556677', 'email' => 'sommer@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $office, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['cto'], 'first_name' => 'David', 'last_name' => 'Roth', 'position' => 'CTO', 'department' => 'Engineering', 'work_phone' => '030 5557702', 'mobile_phone' => '0171 9988202', 'private_phone' => null, 'email' => 'roth@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'management', 'is_crisis_deputy' => 1, 'notes' => null, 'location_id' => $office, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['eng_lead'], 'first_name' => 'Robin', 'last_name' => 'Engel', 'position' => 'Engineering Lead', 'department' => 'Engineering', 'work_phone' => null, 'mobile_phone' => '0171 9988203', 'private_phone' => null, 'email' => 'engel@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['cto'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 0, 'notes' => 'Hauptverantwortlich für On-Call-Rotation.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['backend1'], 'first_name' => 'Mehmet', 'last_name' => 'Yilmaz', 'position' => 'Senior Backend-Entwickler', 'department' => 'Engineering', 'work_phone' => null, 'mobile_phone' => '0171 9988204', 'private_phone' => null, 'email' => 'yilmaz@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['eng_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['backend2'], 'first_name' => 'Anna', 'last_name' => 'Petrova', 'position' => 'Backend-Entwicklerin', 'department' => 'Engineering', 'work_phone' => null, 'mobile_phone' => '0171 9988205', 'private_phone' => null, 'email' => 'petrova@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['eng_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['frontend'], 'first_name' => 'Lisa', 'last_name' => 'Brandt', 'position' => 'Frontend-Entwicklerin', 'department' => 'Engineering', 'work_phone' => null, 'mobile_phone' => '0171 9988206', 'private_phone' => null, 'email' => 'brandt@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['eng_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['devops1'], 'first_name' => 'Sven', 'last_name' => 'Krämer', 'position' => 'DevOps / SRE', 'department' => 'Engineering', 'work_phone' => null, 'mobile_phone' => '0171 9988207', 'private_phone' => '030 5559911', 'email' => 'kraemer@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['eng_lead'], 'is_key_personnel' => 1, 'crisis_role' => 'it_lead', 'is_crisis_deputy' => 1, 'notes' => 'Hauptverantwortung Cloud-Infrastruktur.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['devops2'], 'first_name' => 'Felix', 'last_name' => 'Bauer', 'position' => 'SRE (Site Reliability Engineer)', 'department' => 'Engineering', 'work_phone' => null, 'mobile_phone' => '0171 9988208', 'private_phone' => null, 'email' => 'bauer@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['eng_lead'], 'is_key_personnel' => 1, 'crisis_role' => 'emergency_officer', 'is_crisis_deputy' => 1, 'notes' => 'On-Call-Lead, Statuspage-Owner.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['qa'], 'first_name' => 'Tobias', 'last_name' => 'Lange', 'position' => 'QA-Engineer', 'department' => 'Engineering', 'work_phone' => null, 'mobile_phone' => '0171 9988209', 'private_phone' => null, 'email' => 'lange@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['eng_lead'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['pm'], 'first_name' => 'Carolin', 'last_name' => 'Hoffmann', 'position' => 'Product Manager', 'department' => 'Product', 'work_phone' => null, 'mobile_phone' => '0171 9988210', 'private_phone' => null, 'email' => 'hoffmann@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['cto'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['csm'], 'first_name' => 'Maria', 'last_name' => 'Schultz', 'position' => 'Customer Success Manager', 'department' => 'Customer Success', 'work_phone' => '030 5557720', 'mobile_phone' => '0171 9988211', 'private_phone' => null, 'email' => 'schultz@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 1, 'crisis_role' => 'communications_lead', 'is_crisis_deputy' => 0, 'notes' => 'Kundenkommunikation im Incident.', 'location_id' => $office, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['sales'], 'first_name' => 'Niklas', 'last_name' => 'Berger', 'position' => 'Sales Lead', 'department' => 'Vertrieb', 'work_phone' => '030 5557721', 'mobile_phone' => '0171 9988212', 'private_phone' => null, 'email' => 'berger@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $office, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['buchhaltung'], 'first_name' => 'Petra', 'last_name' => 'Klein', 'position' => 'Buchhaltung / Office Management', 'department' => 'Verwaltung', 'work_phone' => '030 5557703', 'mobile_phone' => '0171 9988213', 'private_phone' => null, 'email' => 'klein@cloudmetric.example', 'emergency_contact' => null, 'manager_id' => $emp['gf'], 'is_key_personnel' => 0, 'crisis_role' => null, 'is_crisis_deputy' => 0, 'notes' => null, 'location_id' => $office, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $emp['dsb_extern'], 'first_name' => 'Dr. Henning', 'last_name' => 'Vogt', 'position' => 'Datenschutzbeauftragter (extern)', 'department' => 'Compliance', 'work_phone' => null, 'mobile_phone' => '0171 9988214', 'private_phone' => null, 'email' => 'vogt@dsb-saas.example', 'emergency_contact' => null, 'manager_id' => null, 'is_key_personnel' => 1, 'crisis_role' => 'dpo', 'is_crisis_deputy' => 0, 'notes' => 'Spezialisiert auf SaaS und EU-Datenresidenz.', 'location_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'service_providers' => [
                ['id' => $prov['aws'], 'name' => 'AWS (Hyperscaler) Enterprise Support', 'type' => 'cloud_provider', 'contact_name' => 'Technical Account Manager', 'hotline' => '0800 0009923', 'email' => 'enterprise-support@aws.example', 'contract_number' => 'AWS-ENT-CM-2026', 'sla' => '24/7 Critical 15 Min Response', 'notes' => 'Produktions-Cloud (eu-central-1). Premium-Support kritisch — eskaliert zu TAM bei P1.', 'direct_order_limit' => 25000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['github'], 'name' => 'GitHub Enterprise Support', 'type' => 'other', 'contact_name' => 'Premium Support', 'hotline' => '+1 877 448-4820', 'email' => 'premium-support@github.example', 'contract_number' => 'GHE-CM-2026', 'sla' => '24/7, Severity 1: 1h', 'notes' => 'Source-Code-Hosting + GitHub Actions CI/CD.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['auth'], 'name' => 'Auth0 / Okta Customer Identity', 'type' => 'other', 'contact_name' => 'Enterprise Support', 'hotline' => '+1 888 722-7871', 'email' => 'enterprise-support@auth0.example', 'contract_number' => 'AUTH0-CM-2026', 'sla' => '24/7, P1: 1h', 'notes' => 'Identity Provider für Endkunden-Logins. Single Point of Failure für Login.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['stripe'], 'name' => 'Stripe Payments', 'type' => 'other', 'contact_name' => 'Account Manager DACH', 'hotline' => '0800 8888400', 'email' => 'support@stripe.example', 'contract_number' => 'STR-CM-2026', 'sla' => '24/7', 'notes' => 'Abrechnung Subscriptions. Bei Ausfall Webhook-Retries.', 'direct_order_limit' => 3000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['cloudflare'], 'name' => 'Cloudflare (CDN, WAF, DNS)', 'type' => 'other', 'contact_name' => 'Enterprise Support', 'hotline' => '+1 650 319-8930', 'email' => 'enterprise@cloudflare.example', 'contract_number' => 'CF-CM-2026', 'sla' => '24/7, P1: 1h', 'notes' => 'CDN, WAF, DDoS-Schutz, primäres DNS.', 'direct_order_limit' => 5000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['pentest'], 'name' => 'SecureCheck GmbH (Pentester)', 'type' => 'other', 'contact_name' => 'Lead Pentester', 'hotline' => '030 7766551', 'email' => 'pentest@securecheck.example', 'contract_number' => 'PT-2026-CM', 'sla' => 'Auftrag pro Sprint', 'notes' => 'Jährlicher Pentest, ad-hoc bei sicherheitskritischen Releases.', 'direct_order_limit' => 15000, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $prov['lfdi'], 'name' => 'BlnBDI – Berliner Beauftragte für Datenschutz', 'type' => 'data_protection_authority', 'contact_name' => 'Beschwerdestelle', 'hotline' => '030 13889-0', 'email' => 'mailbox@datenschutz-berlin.de', 'contract_number' => null, 'sla' => 'Mo-Fr 9-15', 'notes' => 'Aufsichtsbehörde DSGVO Art. 33-Meldungen. Bei Auftragsverarbeitung Kunden zusätzlich gemäß Art. 33 Abs. 2 informieren.', 'direct_order_limit' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'systems' => [
                ['id' => $sys['cloud'], 'name' => 'Cloud-Hosting (AWS Production)', 'description' => 'Primärregion eu-central-1 (Frankfurt), Multi-AZ.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 15, 'downtime_cost_per_hour' => 3000, 'fallback_process' => 'Failover in eu-west-1 via Terraform-Disaster-Recovery; Statuspage sofort aktualisieren; Premium-Support eskalieren.', 'runbook_reference' => 'Runbook „AWS DR" v2.1', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['k8s'], 'name' => 'Container-Orchestrierung (EKS)', 'description' => 'EKS-Cluster mit 12 Node-Groups, GitOps via ArgoCD.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => 15, 'downtime_cost_per_hour' => 2500, 'fallback_process' => 'Cluster-Recreate via Terraform; Workloads aus Git-Repo deployen; degradierter Modus mit reduzierten Replicas.', 'runbook_reference' => 'Runbook „EKS Recovery" v1.4', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['db'], 'name' => 'Datenbank-Cluster (PostgreSQL)', 'description' => 'Aurora PostgreSQL Cluster, Multi-AZ mit Read Replicas.', 'category' => 'basisbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => 5, 'downtime_cost_per_hour' => 4000, 'fallback_process' => 'Automatisches Aurora-Failover auf Standby; Point-in-Time-Recovery aus S3-Snapshot bei Korruption.', 'runbook_reference' => 'Runbook „DB Failover" v2.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['cdn'], 'name' => 'CDN / WAF (Cloudflare)', 'description' => 'CDN, WAF, DDoS-Schutz, Edge-Caching.', 'category' => 'basisbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 2000, 'fallback_process' => 'DNS auf Direkt-Origin umleiten (eingeschränkter DDoS-Schutz); Cloudflare-Status prüfen.', 'runbook_reference' => 'Runbook „CDN Bypass" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['sourcecode'], 'name' => 'Source-Code (GitHub Enterprise)', 'description' => 'Source-Code, Issues, PR-Workflow.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 800, 'fallback_process' => 'Lokale Klone bleiben funktional; Hotfix-Deploys über vorhandene CI-Caches; Premium-Support eskalieren.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['cicd'], 'name' => 'CI/CD-Pipeline (GitHub Actions)', 'description' => 'Build-, Test- und Deployment-Pipelines.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 600, 'fallback_process' => 'Manuelles Deployment via Terraform/CLI von Engineering-Workstation; Hotfix-Path im Runbook.', 'runbook_reference' => 'Runbook „Manual Deploy" v1.2', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['apm'], 'name' => 'APM / Error-Tracking (Datadog + Sentry)', 'description' => 'Application Performance Monitoring, Error-Tracking, Alerting.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 400, 'fallback_process' => 'CloudWatch-Dashboards als Notfall-Sicht; manuelle Logs-Beobachtung via SSM-Session.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['logs'], 'name' => 'Log-Aggregation (Elastic Stack)', 'description' => 'Zentrale Log-Pipeline für alle Services.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 200, 'fallback_process' => 'CloudWatch Logs als Fallback-Backend; Log-Forwarder umkonfigurieren.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['statuspage'], 'name' => 'Public Statuspage (statuspage.io)', 'description' => 'Öffentliche Statuspage mit Komponenten-Status und Subscriber-Notifications.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 60, 'rpo_minutes' => null, 'downtime_cost_per_hour' => 0, 'fallback_process' => 'Status-Update via Twitter/X-Notfallkonto; Massenmail an Kunden via Backup-SMTP.', 'runbook_reference' => 'Runbook „Statuspage Update" v1.3', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['support'], 'name' => 'Customer-Support (Intercom)', 'description' => 'Kunden-Chat, Tickets, In-App-Messaging.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 240, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 300, 'fallback_process' => 'Direkte Mail an support@-Postfach; Notfall-Auto-Responder verweist auf Statuspage.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['sso'], 'name' => 'SSO / Identity (Auth0)', 'description' => 'Customer Identity / SSO.', 'category' => 'basisbetrieb', 'rto_minutes' => 30, 'rpo_minutes' => 60, 'downtime_cost_per_hour' => 3500, 'fallback_process' => 'Lange Session-TTLs überbrücken kurze Ausfälle; bei langem Ausfall Notfall-Login-Endpoint mit Magic-Link über eigenen SMTP.', 'runbook_reference' => 'Runbook „Auth0 Outage" v1.0', 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => $sys['wiki'], 'name' => 'Internes Wiki (Confluence)', 'description' => 'Engineering-Doku, Runbooks, Postmortems.', 'category' => 'geschaeftsbetrieb', 'rto_minutes' => 480, 'rpo_minutes' => 1440, 'downtime_cost_per_hour' => 150, 'fallback_process' => 'Lokale Markdown-Kopien der wichtigsten Runbooks im Git-Repo; Notfallhandbuch als PDF im Tresor.', 'runbook_reference' => null, 'system_priority_id' => null, 'emergency_level_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'system_dependencies' => [
                ['system_id' => $sys['k8s'], 'depends_on_system_id' => $sys['cloud'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['db'], 'depends_on_system_id' => $sys['cloud'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cdn'], 'depends_on_system_id' => $sys['k8s'], 'sort' => 0, 'note' => 'Origin im Cluster.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cicd'], 'depends_on_system_id' => $sys['sourcecode'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cicd'], 'depends_on_system_id' => $sys['cloud'], 'sort' => 1, 'note' => 'Deploys ins Cloud-Hosting.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['apm'], 'depends_on_system_id' => $sys['k8s'], 'sort' => 0, 'note' => 'Agenten in Pods.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['logs'], 'depends_on_system_id' => $sys['k8s'], 'sort' => 0, 'note' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['sso'], 'depends_on_system_id' => $sys['cdn'], 'sort' => 0, 'note' => 'Login-Domain hinter Cloudflare.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['support'], 'depends_on_system_id' => $sys['sso'], 'sort' => 0, 'note' => 'SSO-Anbindung.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['statuspage'], 'depends_on_system_id' => $sys['cdn'], 'sort' => 0, 'note' => 'Bewusst extern gehostet, daher unabhängig vom Cloud-Hosting.', 'created_at' => $ts, 'updated_at' => $ts],
                ['system_id' => $sys['cloud'], 'depends_on_system_id' => $sys['cdn'], 'sort' => 0, 'note' => 'DNS via Cloudflare.', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'insurance_policies' => [
                ['id' => Helpers::uuid(), 'type' => 'professional_liability', 'insurer' => 'HDI Versicherung AG', 'policy_number' => 'VSH-2026-SAAS-441188', 'hotline' => '0511 645-0', 'email' => 'it-vsh@hdi.example', 'reporting_window' => 'unverzüglich', 'contact_name' => 'Schadenstelle SaaS', 'notes' => 'Vermögensschadenhaftpflicht für SaaS-Anbieter. Deckung 5 Mio €. Wegen SLA-Service-Credits relevant.', 'deductible' => '15.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'cyber', 'insurer' => 'Munich Re Cyber', 'policy_number' => 'CY-2026-SAAS-882211', 'hotline' => '0800 6666200', 'email' => 'cyber@munichre.example', 'reporting_window' => 'unverzüglich, spätestens 24 Stunden', 'contact_name' => 'IR-Hotline', 'notes' => 'Deckung 5 Mio €, inkl. Forensik, Krisenkommunikation, Erpressungsfälle, Drittschäden.', 'deductible' => '10.000 €', 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'type' => 'business_interruption', 'insurer' => 'Allianz Sach AG', 'policy_number' => 'BI-2025-SAAS-991122', 'hotline' => '0800 1112020', 'email' => 'gewerbe@allianz.example', 'reporting_window' => 'binnen 7 Tagen', 'contact_name' => 'Herr Berger', 'notes' => 'BU bis 90 Tage.', 'deductible' => '5.000 €', 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'communication_templates' => [
                ['id' => Helpers::uuid(), 'name' => 'Statuspage-Update – Incident-Stages', 'audience' => 'customers', 'channel' => 'web', 'subject' => '[INVESTIGATING] Service-Beeinträchtigung {{ komponente }}', 'body' => "Investigating: Wir untersuchen aktuell eine Beeinträchtigung von {{ komponente }}. Auswirkung: {{ auswirkung }}. Erstes Update folgt in 15 Minuten.\n\nIdentified: Ursache eingegrenzt: {{ ursache }}. Maßnahmen laufen.\n\nMonitoring: Behebung implementiert. Wir beobachten die Plattform für 30 Minuten.\n\nResolved: Der Vorfall ist behoben. Postmortem folgt innerhalb von 5 Werktagen.\n\nStand: {{ zeitpunkt }}", 'fallback' => 'Twitter/X-Notfallkonto + Massenmail.', 'sort' => 0, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Kunden-Massenmail bei Datenleck', 'audience' => 'customers', 'channel' => 'email', 'subject' => 'Wichtige Sicherheitsmitteilung – CloudMetric Analytics', 'body' => "Sehr geehrte Kundin, sehr geehrter Kunde,\n\nwir informieren Sie hiermit transparent über einen Sicherheitsvorfall, der am {{ zeitpunkt }} festgestellt wurde.\n\nWas ist passiert: {{ vorfall }}\nWelche Daten sind betroffen: {{ datenkategorien }}\nWas wir unternommen haben: {{ massnahmen }}\nWas Sie tun sollten: {{ empfehlung }}\n\nWir haben den Vorfall der zuständigen Aufsichtsbehörde gemäß DSGVO Art. 33 gemeldet. Für Rückfragen erreichen Sie uns unter security@cloudmetric.example.\n\nMit freundlichen Grüßen\nJulia Sommer, Geschäftsführerin", 'fallback' => 'In-App-Banner + Statuspage-Notice.', 'sort' => 1, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Postmortem-Template (intern + öffentlich)', 'audience' => 'customers', 'channel' => 'web', 'subject' => 'Postmortem – {{ titel_incident }} ({{ datum }})', 'body' => "Zusammenfassung: {{ zusammenfassung }}\nAuswirkung: {{ auswirkung }} (Dauer: {{ dauer }}, betroffen: {{ betroffene_kunden }})\nZeitleiste:\n  {{ timeline }}\nUrsachenanalyse:\n  Auslöser: {{ trigger }}\n  Beitragende Faktoren: {{ contributing_factors }}\nWas gut lief / Was nicht gut lief\nMaßnahmen (Action Items):\n  {{ actions }}\n\nWir entschuldigen uns für die Beeinträchtigung und bedanken uns für die Geduld.", 'fallback' => 'PDF-Versand an Enterprise-Kunden bei größeren Incidents.', 'sort' => 2, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Pressemeldung bei Sicherheitsvorfall', 'audience' => 'public', 'channel' => 'press', 'subject' => 'Pressemitteilung – Sicherheitsvorfall bei CloudMetric Analytics', 'body' => "Berlin, {{ datum }} – CloudMetric Analytics GmbH informiert: Am {{ zeitpunkt }} wurde ein Sicherheitsvorfall festgestellt. Erste Maßnahmen waren {{ massnahmen }}. Eine Meldung an die Berliner Beauftragte für Datenschutz ist erfolgt. Betroffene Kundinnen und Kunden wurden direkt informiert. Untersuchung und Kommunikation laufen transparent über unsere Statuspage.\n\nKontakt für Rückfragen: presse@cloudmetric.example", 'fallback' => null, 'sort' => 3, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'name' => 'Erstmeldung Engineering (Slack/PagerDuty)', 'audience' => 'employees', 'channel' => 'chat', 'subject' => 'P1-Incident – {{ komponente }}', 'body' => "P1-Incident eröffnet ({{ zeitpunkt }}).\nKomponente: {{ komponente }}\nAuswirkung: {{ auswirkung }}\nIncident Commander: {{ ic }}\nKommunikation: Maria Schultz (CSM)\nKanal: #incident-{{ id }}\nStatuspage: gleich aktualisieren.\n\nNicht ausführen ohne IC-Freigabe: Migrationen, kostenintensive Cloud-Aktionen.", 'fallback' => 'Telefonkette über On-Call-Lead.', 'sort' => 4, 'scenario_id' => null, 'created_at' => $ts, 'updated_at' => $ts],
            ],

            'emergency_resources' => [
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'On-Call-Schedule (PagerDuty + Papier-Backup)', 'description' => 'Wöchentlicher Rotationsplan SRE/DevOps mit Vertretungen.', 'location' => 'PagerDuty + Druckversion Office Berlin', 'access_holders' => 'Engineering Lead, alle SRE/DevOps', 'last_check_at' => Helpers::date(-7), 'next_check_at' => Helpers::date(0), 'notes' => 'Wöchentlich aktualisieren.', 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Runbooks für Service-Wiederanlauf', 'description' => 'AWS-DR, EKS-Recovery, DB-Failover, Auth0-Outage, manuelles Deploy.', 'location' => 'Confluence + Markdown-Mirror im Git-Repo + PDF im Tresor', 'access_holders' => 'gesamtes Engineering-Team', 'last_check_at' => Helpers::date(-21), 'next_check_at' => Helpers::date(70), 'notes' => 'Quartalsweise reviewen.', 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Incident-Response-Playbooks', 'description' => 'Playbooks für Datenleck, DDoS, Erpressung, Lieferanten-Outage. IC-Rolle, Eskalationsweg, Versicherungsmeldung.', 'location' => 'Confluence + verschlüsselter S3-Mirror + Papier im Tresor', 'access_holders' => 'CTO, Engineering Lead, SRE-Lead, CSM, GF', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Statuspage-Templates + Customer-Communication-Templates', 'description' => 'Vorbereitete Templates für Investigating/Identified/Monitoring/Resolved sowie Datenleck-Mail.', 'location' => 'Confluence + Backup als PDF im Tresor', 'access_holders' => 'CSM, Engineering Lead, SRE-Lead', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(75), 'notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_backup', 'name' => 'Backup-Restore-Test-Daten', 'description' => 'Anonymisierter PITR-Snapshot im separaten Sandbox-Account für Restore-Übungen.', 'location' => 'AWS-Sandbox-Account + Beschreibung im Runbook', 'access_holders' => 'SRE-Lead, DevOps-Team', 'last_check_at' => Helpers::date(-30), 'next_check_at' => Helpers::date(60), 'notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'offline_docs', 'name' => 'Notfallhandbuch (Papier)', 'description' => 'Druckversion mit Telefonkette, Hyperscaler-Eskalation, IR-Codewort.', 'location' => '1× Office Berlin, 1× Privatadresse Julia Sommer, 1× Privatadresse Sven Krämer', 'access_holders' => 'GF, CTO, Engineering Lead, SRE-Lead', 'last_check_at' => Helpers::date(-14), 'next_check_at' => Helpers::date(180), 'notes' => null, 'sort' => 5, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'handbook_tests' => [
                ['id' => Helpers::uuid(), 'type' => 'contact_check', 'name' => 'Quartals-Check Telefonliste + On-Call', 'description' => 'Erreichbarkeit aller Krisenrollen, Pager-Alerts, Hyperscaler-Eskalation prüfen.', 'interval' => 'quarterly', 'last_executed_at' => Helpers::date(-60), 'next_due_at' => Helpers::date(30), 'responsible_employee_id' => $emp['devops2'], 'result_notes' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Region-Outage eu-central-1', 'description' => 'Schreibtisch-Übung: Komplettausfall AWS-Region. Failover, Kunden-Statuspage, Postmortem.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-100), 'next_due_at' => Helpers::date(80), 'responsible_employee_id' => $emp['eng_lead'], 'result_notes' => null, 'sort' => 1, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'tabletop', 'name' => 'Tabletop Datenleck mit DSGVO-Meldung', 'description' => 'Übung: SQL-Injection führt zu PII-Leak. DSGVO Art. 33 + 34, Kunden-Massenmail, Pressemeldung.', 'interval' => 'yearly', 'last_executed_at' => Helpers::date(-150), 'next_due_at' => Helpers::date(215), 'responsible_employee_id' => $emp['cto'], 'result_notes' => null, 'sort' => 2, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'backup_restore', 'name' => 'Restore-Test PostgreSQL PITR', 'description' => 'Point-in-Time-Recovery in Sandbox-Account, Daten-Konsistenz prüfen.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-120), 'next_due_at' => Helpers::date(60), 'responsible_employee_id' => $emp['devops1'], 'result_notes' => null, 'sort' => 3, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
                ['id' => Helpers::uuid(), 'type' => 'communication', 'name' => 'Test Statuspage-Update + Subscriber-Notification', 'description' => 'Trockenlauf Statuspage-Update mit Test-Subscribern.', 'interval' => 'biannually', 'last_executed_at' => Helpers::date(-80), 'next_due_at' => Helpers::date(100), 'responsible_employee_id' => $emp['csm'], 'result_notes' => null, 'sort' => 4, 'created_at' => $ts, 'updated_at' => $ts, 'last_reminder_sent_at' => null],
            ],

            'system_tasks' => [
                ['id' => Helpers::uuid(), 'system_id' => $sys['cloud'], 'title' => 'Kosten- und Quoten-Review AWS', 'description' => 'Monatlicher Review von Service-Quoten und Reserved Instances.', 'due_date' => Helpers::date(15), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['db'], 'title' => 'PITR-Restore-Test', 'description' => 'Point-in-Time-Restore eines anonymisierten Snapshots.', 'due_date' => Helpers::date(30), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['k8s'], 'title' => 'Kubernetes Upgrade-Pfad prüfen', 'description' => 'Releasepfad für nächstes Minor-Upgrade des EKS-Clusters validieren.', 'due_date' => Helpers::date(45), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['cicd'], 'title' => 'CI/CD-Secrets rotieren', 'description' => 'Deployment-Token, Cloud-Keys und Webhook-Secrets quartalsweise rotieren.', 'due_date' => Helpers::date(20), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['sso'], 'title' => 'Auth0-Tenant Conditional-Access-Review', 'description' => 'Login-Regeln, MFA-Coverage, Privileged Access prüfen.', 'due_date' => Helpers::date(40), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['cdn'], 'title' => 'Cloudflare WAF-Regeln review', 'description' => 'Custom WAF-Rules mit Pentester abgleichen, False-Positives bereinigen.', 'due_date' => Helpers::date(60), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['statuspage'], 'title' => 'Statuspage-Komponenten aktualisieren', 'description' => 'Komponentenliste mit neuen Services synchronisieren.', 'due_date' => Helpers::date(25), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['apm'], 'title' => 'Alert-Schwellenwerte tunen', 'description' => 'Letzte 30 Tage Alerts auswerten und Schwellen anpassen.', 'due_date' => Helpers::date(50), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['sourcecode'], 'title' => 'Branch-Protection-Audit', 'description' => 'Branch-Protection und Code-Owner-Regeln auf allen Repos prüfen.', 'due_date' => Helpers::date(70), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
                ['id' => Helpers::uuid(), 'system_id' => $sys['logs'], 'title' => 'Log-Retention-Politik prüfen', 'description' => 'Retention pro Index und Datenschutz-Klassifizierung review.', 'due_date' => Helpers::date(-5), 'completed_at' => null, 'sort' => 0, 'created_at' => $ts, 'updated_at' => $ts],
            ],
        ]);
    }
}
