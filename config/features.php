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

    'dependencies' => filter_var(env('FEATURE_DEPENDENCIES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'incident_mode' => filter_var(env('FEATURE_INCIDENT_MODE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'lessons_learned' => filter_var(env('FEATURE_LESSONS_LEARNED_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
