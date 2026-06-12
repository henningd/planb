<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Die Default-Rechtstexte aus dem SettingsCatalog (greifen, solange keine
 * Override-Werte in system_settings stehen) müssen vollständige
 * Pflichtangaben und aktuelle Stand-Daten tragen.
 */
test('the imprint default contains the vat id instead of a placeholder', function () {
    $this->get(route('legal.imprint'))
        ->assertOk()
        ->assertSee('DE462705376')
        ->assertDontSee('Umsatzsteuergesetz:
folgt.', false);
});

test('terms and privacy defaults are dated may 2026', function () {
    $this->get(route('legal.terms'))->assertOk()->assertSee('Stand: Mai 2026');
    $this->get(route('legal.privacy'))->assertOk()->assertSee('Stand: Mai 2026');
});
