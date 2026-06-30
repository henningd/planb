<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('the pricing page renders the four plans with their prices', function () {
    get('/preise')
        ->assertOk()
        ->assertSee('Starter')
        ->assertSee('Business')
        ->assertSee('Advanced')
        ->assertSee('Enterprise')
        // Monatspreise (Jahresansicht ist Standard, daher /12 gerundet)
        ->assertSee('49 €')   // Starter monatlich
        ->assertSee('149 €')  // Business monatlich
        ->assertSee('389 €')  // Advanced monatlich
        ->assertSee('individuell'); // Enterprise
});

test('the comparison table marks Business-tier boundaries', function () {
    get('/preise')
        ->assertOk()
        ->assertSee('Anwendung &amp; Stammdaten', false)
        ->assertSee('Business Impact Analyse')
        ->assertSee('bis 3') // Business: bis 3 Nutzer/Standorte
        ->assertSee('bis 10'); // Advanced: bis 10 Nutzer
});
