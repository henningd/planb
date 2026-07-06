<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('kommunen page renders with reasons, modules and cta', function () {
    $this->get(route('kommunen.show'))
        ->assertOk()
        ->assertSee('Kommunen')
        ->assertSee('NIS2')
        ->assertSee('Bürgerdienste')
        ->assertSee('Notfallaushang')
        ->assertSee('Krisenstab');
});

test('kommunen page shows the app section with offline, alarm and sms messaging', function () {
    $this->get(route('kommunen.show'))
        ->assertOk()
        ->assertSee('Die Notfall-App (iOS & Android)')
        ->assertSee('Alle Daten offline auf dem Gerät')
        ->assertSee('und Abhaken funktionieren ohne')
        ->assertSee('Alarme, die wirklich ankommen')
        ->assertSee('zeitkritisch')
        ->assertSee('Nicht stören')
        ->assertSee('Automatische SMS-Eskalation')
        ->assertSee('SMS an den')
        ->assertSee('Offline verfügbar')
        ->assertSee('Stromausfall Rathaus');
});

test('landing page highlights kommunen and links to the kommunen page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Für Kommunen')
        ->assertSee('Kommunen &amp; Eigenbetriebe', false)
        ->assertSee(route('kommunen.show'), false);
});

test('marketing header and footer link to the kommunen page', function () {
    $this->get(route('pricing.show'))
        ->assertOk()
        ->assertSee(route('kommunen.show'), false);
});

test('pricing page shows the kommunal plan with app included', function () {
    $this->get(route('pricing.show'))
        ->assertOk()
        ->assertSee('Kommunal')
        ->assertSee('Für Städte, Gemeinden & Eigenbetriebe')
        ->assertSee('Notfall-App für iOS & Android inklusive')
        ->assertSee('Angebot anfragen')
        // App-Nutzung in Business enthalten (Advanced/Enterprise erben via „Alles aus …")
        ->assertSee('Notfall-App für iOS & Android – Handbuch, Kontakte & Checklisten offline')
        // Vergleichstabelle: App-Zeile
        ->assertSee('Notfall-App (iOS & Android) – offline & QR-Aushang-Scan');
});

test('kommunen page links to the kommunal plan on the pricing page', function () {
    $this->get(route('kommunen.show'))
        ->assertOk()
        ->assertSee(route('pricing.show').'#kommunal', false);
});

test('kommunal plan exists in billing config without self-service checkout', function () {
    $plan = config('billing.plans.kommunal');

    expect($plan)->not->toBeNull()
        ->and($plan['name'])->toBe('Kommunal')
        ->and($plan['monthly_price_id'])->toBeNull()
        ->and($plan['yearly_price_id'])->toBeNull();
});

test('sitemap contains the kommunen page', function () {
    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('kommunen.show'), false);
});
