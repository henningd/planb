<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'portal' => [
        // Öffentliche URL des PlanB Portals (Anbieter-Marktplatz) für Cross-Links.
        'url' => env('PORTAL_URL', 'https://portal.notfallhandbuch.eu'),
    ],

    'indexnow' => [
        // IndexNow-Schlüssel (Bing/Yandex & Co. sofort über URL-Änderungen
        // informieren). Leer = Feature deaktiviert.
        'key' => env('INDEXNOW_KEY', ''),
    ],

    'sevenio' => [
        'key' => env('SEVENIO_API_KEY'),
        'sender' => env('SEVENIO_SENDER'), // optional: Absender (z. B. Firmenname, max. 11 Zeichen)
        'endpoint' => env('SEVENIO_ENDPOINT', 'https://gateway.seven.io/api/sms'),
    ],

    'mobile' => [
        // Optionaler App-Key für die Notfall-App (Header X-App-Key). Leer =
        // kein Key erzwungen (offen). Muss mit dem in der App hinterlegten Key
        // übereinstimmen, sobald gesetzt.
        'app_key' => env('MOBILE_APP_KEY', ''),
    ],

    'firebase' => [
        // Firebase-Projekt-ID und Pfad zur Service-Account-JSON für den
        // Push-Versand (FCM HTTP v1). Leer = keine Zustellung (nur Log).
        'project_id' => env('FIREBASE_PROJECT_ID', ''),
        'credentials' => env('FIREBASE_CREDENTIALS', ''),
    ],

];
