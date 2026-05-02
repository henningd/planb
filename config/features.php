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
];
