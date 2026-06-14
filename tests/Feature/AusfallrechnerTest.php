<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Die Startseite enthält den interaktiven Ausfallrechner – inklusive der
 * Eingabefelder und des serverseitig vorbefüllten Ergebnisses (funktioniert
 * auch ohne JavaScript).
 */
test('the homepage shows the interactive downtime cost calculator', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Ausfallrechner')
        ->assertSee('Was kostet Sie ein IT-Ausfall')
        ->assertSee('id="ar-employees"', false)
        ->assertSee('type="range"', false)
        ->assertSee('id="ar-total-result"', false)
        ->assertSee('Geschätzte Ausfallkosten');
});
