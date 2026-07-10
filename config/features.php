<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature-Schalter
    |--------------------------------------------------------------------------
    |
    | Aktivieren/deaktivieren einzelne Module per .env. Wird ein Feature
    | deaktiviert, verschwindet sowohl der Sidebar-Eintrag als auch die
    | dazugehörige Route (404). Alle Schalter sind standardmäßig aktiv,
    | damit bestehende Installationen nichts verlieren.
    |
    */

    'compliance' => filter_var(env('FEATURE_COMPLIANCE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'departments' => filter_var(env('FEATURE_DEPARTMENTS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'dependencies' => filter_var(env('FEATURE_DEPENDENCIES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'employee_graph_tabs' => filter_var(env('FEATURE_EMPLOYEE_GRAPH_TABS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'incident_mode' => filter_var(env('FEATURE_INCIDENT_MODE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'lessons_learned' => filter_var(env('FEATURE_LESSONS_LEARNED_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'risk_register' => filter_var(env('FEATURE_RISK_REGISTER_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // Verträge / SLA: eigener Bereich für Service-/Wartungsverträge, verknüpft
    // mit Dienstleistern, Systemen und Standorten. Reaktions-/Wiederherstellungs-
    // zeiten und Störungs-Hotline sind im Notfall direkt abrufbar. Default an.
    'contracts' => filter_var(env('FEATURE_CONTRACTS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // Präventivmaßnahmen je System (vorbeugende Kontrollen gegen Ausfall):
    // eigener Menüpunkt, Verwaltungsseite, System-Karteikarte, Aufgaben-Inbox
    // und Reminder-Cron. Default an.
    'preventive_measures' => filter_var(env('FEATURE_PREVENTIVE_MEASURES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // BCMS-Ausbau nach BSI 200-4 / NIS2:
    'bia' => filter_var(env('FEATURE_BIA_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'maturity' => filter_var(env('FEATURE_MATURITY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'supply_chain_risk' => filter_var(env('FEATURE_SUPPLY_CHAIN_RISK_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'bcm_policy' => filter_var(env('FEATURE_BCM_POLICY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'management_review' => filter_var(env('FEATURE_MANAGEMENT_REVIEW_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'training_records' => filter_var(env('FEATURE_TRAINING_RECORDS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'open_items' => filter_var(env('FEATURE_OPEN_ITEMS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'ai_governance' => filter_var(env('FEATURE_AI_GOVERNANCE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'authority_contacts' => filter_var(env('FEATURE_AUTHORITY_CONTACTS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // Bereich „Abteilungen / Rollen" (/roles): Menüpunkt und Seiten. Routen
    // bleiben registriert und liefern 404, damit Verweise aus Onboarding und
    // Compliance-Dashboard nicht brechen.
    'roles' => filter_var(env('FEATURE_ROLES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'monitoring_api' => filter_var(env('FEATURE_MONITORING_API_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // Bibel-Verse im Krisen-Cockpit anzeigen — je ein Vers für „kein Notfall"
    // (Vorsorge/Wachsamkeit) und für „aktiver Notfall" (Stärke/Beistand).
    // Default aus, weil es eine spirituelle Ergänzung ist, die nicht jeder
    // Mandant haben möchte.
    'bible_verses' => filter_var(env('FEATURE_BIBLE_VERSES_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    // Mandanten-Abrechnung über Stripe / Cashier. Default aus, damit das
    // Modul nur sichtbar wird, wenn Stripe-Keys hinterlegt sind und der
    // Betreiber die Abrechnung wirklich nutzen will.
    'billing' => filter_var(env('FEATURE_BILLING_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    // 2FA-Pflicht: Jeder verifizierte Nutzer ohne bestätigtes 2FA wird zur
    // Einrichtung gezwungen, bevor er die App nutzen kann. Default an.
    'enforce_two_factor' => filter_var(env('FEATURE_ENFORCE_2FA', true), FILTER_VALIDATE_BOOLEAN),
];
