<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Die öffentlichen Marketing-Seiten müssen auf das PlanB Portal
 * (Anbieter-Marktplatz) verlinken — Header, Footer und der
 * Portal-Abschnitt auf der Startseite.
 */
beforeEach(function () {
    config(['services.portal.url' => 'https://portal.notfallhandbuch.eu']);
});

test('the home page explains and links the provider portal', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://portal.notfallhandbuch.eu/anbieter', false)
        ->assertSee('https://portal.notfallhandbuch.eu/register', false)
        ->assertSee('Anbieter-Portal')
        ->assertSee('PlanB Portal')
        ->assertSee('Anbieter-Verzeichnis öffnen');
});

test('header and footer link the portal on all public marketing pages', function () {
    $urls = [
        route('pricing.show'),
        route('guides.show', 'notfallhandbuch'),
        route('legal.imprint'),
    ];

    foreach ($urls as $url) {
        $this->get($url)
            ->assertOk()
            // Nav-Punkt führt auf die Portal-Startseite (exakter href, da die
            // Basis-URL als Substring in jedem Deep-Link steckt).
            ->assertSee('href="https://portal.notfallhandbuch.eu"', false)
            ->assertSee('Anbieter-Portal')
            ->assertSee('https://portal.notfallhandbuch.eu/anbieter', false)
            ->assertSee('https://portal.notfallhandbuch.eu/register', false)
            ->assertSee('Als Dienstleister registrieren');
    }
});

test('the footer links the portal explainer section via anchor', function () {
    $this->get(route('guides.show', 'notfallhandbuch'))
        ->assertOk()
        ->assertSee(route('home').'#portal', false)
        ->assertSee('Was ist das Portal?');

    // Der Anker muss auf der Startseite existieren.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('id="portal"', false);
});
