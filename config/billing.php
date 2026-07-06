<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plan-Definitionen
    |--------------------------------------------------------------------------
    |
    | Single Source of Truth für Tarife: Anzeige (Pricing-Page, Settings →
    | Abrechnung) und Verknüpfung mit Stripe-Price-IDs aus dem Test- oder
    | Live-Mode. Enterprise hat keine Stripe-Preise — Buchung auf Anfrage.
    |
    */

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'monthly_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY'),
            'yearly_price_id' => env('STRIPE_PRICE_STARTER_YEARLY'),
            'monthly_amount' => 4900, // Cents — nur für UI-Fallback ohne Stripe.
            'yearly_amount' => 49000,
        ],
        'advanced' => [
            'name' => 'Advanced',
            'monthly_price_id' => env('STRIPE_PRICE_ADVANCED_MONTHLY'),
            'yearly_price_id' => env('STRIPE_PRICE_ADVANCED_YEARLY'),
            'monthly_amount' => 38900,
            'yearly_amount' => 389000,
        ],
        // Kommunal: Advanced-Umfang + Notfall-App für Städte/Gemeinden/Eigenbetriebe.
        // Angebot & Rechnung (vergabefreundlich), kein self-service Checkout.
        'kommunal' => [
            'name' => 'Kommunal',
            'monthly_price_id' => null,
            'yearly_price_id' => null,
            'monthly_amount' => null,
            'yearly_amount' => null,
        ],
        // Enterprise: Vertrieb, kein self-service Checkout.
        'enterprise' => [
            'name' => 'Enterprise',
            'monthly_price_id' => null,
            'yearly_price_id' => null,
            'monthly_amount' => null,
            'yearly_amount' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-ons
    |--------------------------------------------------------------------------
    |
    | Einmalzahlungen (workshop, coaching_hour, extra_user) und der
    | Coaching-Retainer als wiederkehrendes Abo. Mode bestimmt, ob Stripe
    | Checkout im Modus `payment` oder `subscription` läuft.
    |
    */

    'addons' => [
        'workshop' => [
            'name' => 'Onboarding-Workshop (2h, remote)',
            'price_id' => env('STRIPE_PRICE_ADDON_WORKSHOP'),
            'mode' => 'payment',
            'description' => 'Live-Workshop mit einem unserer BCM-Berater. Strukturierte Einrichtung Ihres Notfallhandbuchs auf Basis Ihrer Stammdaten.',
        ],
        'coaching_hour' => [
            'name' => 'Coaching-Stunde (60 min)',
            'price_id' => env('STRIPE_PRICE_ADDON_COACHING_HOUR'),
            'mode' => 'payment',
            'allow_quantity' => true,
            'description' => 'Beratung nach Bedarf — Tabletop-Übung begleiten, Compliance-Score-Review, NIS2-Sparring.',
        ],
        'coaching_retainer' => [
            'name' => 'Coaching-Retainer (4h/Monat)',
            'price_id' => env('STRIPE_PRICE_ADDON_COACHING_RETAINER'),
            'mode' => 'subscription',
            'description' => 'Monatlich 4 Stunden Beratung im Retainer — günstiger als Einzelstunden, jederzeit kündbar.',
        ],
        'extra_user' => [
            'name' => 'Zusatz-User (über Plan-Limit hinaus)',
            'price_id' => env('STRIPE_PRICE_ADDON_EXTRA_USER'),
            'mode' => 'payment',
            'allow_quantity' => true,
            'description' => 'Pro zusätzlichem App-User über das Plan-Limit hinaus. Wird auf den nächsten Plan-Wechsel angerechnet.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial / Read-Only
    |--------------------------------------------------------------------------
    */

    'trial_days' => 14,

    // Trial wird nur bei Auswahl dieses Plans im Onboarding aktiviert.
    'trial_plan' => 'advanced',

    // Nach Trial-Ende ohne Zahlungsmethode wird der Mandant in einen
    // Read-Only-Zustand versetzt: Daten lesbar/exportierbar, aber keine
    // Schreibvorgänge mehr.
    'freeze_after_trial' => true,

    /*
    |--------------------------------------------------------------------------
    | Tax / Reverse-Charge
    |--------------------------------------------------------------------------
    |
    | automatic_tax aktiviert Stripe Tax — Stripe ermittelt USt-Satz und
    | wendet Reverse-Charge bei B2B-Kunden mit gültiger USt-IdNr an.
    | tax_id_collection blendet das USt-IdNr-Feld im Checkout ein.
    |
    */

    'automatic_tax' => true,
    'tax_id_collection' => true,
];
