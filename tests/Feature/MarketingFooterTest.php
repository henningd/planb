<?php

use App\Support\Marketing\GuideCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Der gemeinsame Marketing-Footer (partials/marketing-footer) muss auf allen
 * öffentlichen Seiten erscheinen — inklusive Ratgeber- und Rechtslinks.
 */
test('every public marketing page shows the shared footer', function () {
    $urls = [
        route('home'),
        route('pricing.show'),
        route('guides.show', 'notfallhandbuch'),
        route('feature.show', 'compliance-dashboard'),
        route('legal.imprint'),
    ];

    foreach ($urls as $url) {
        $response = $this->get($url)->assertOk();

        // Ratgeber-Rubrik mit allen Guides
        foreach (GuideCatalog::slugs() as $slug) {
            $response->assertSee(route('guides.show', $slug), false);
        }

        // Rechts- und Compliance-Links
        $response->assertSee(route('legal.imprint'), false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee('Auftragsverarbeitung')
            ->assertSee('Alle Rechte vorbehalten');
    }
});

test('every public marketing page shows the shared top navigation', function () {
    $urls = [
        route('home'),
        route('pricing.show'),
        route('guides.show', 'notfallhandbuch'),
        route('feature.show', 'compliance-dashboard'),
        route('legal.imprint'),
    ];

    foreach ($urls as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee(route('home').'#features', false)
            ->assertSee(route('home').'#compliance', false)
            ->assertSee(route('pricing.show'), false)
            ->assertSee('Anmelden');
    }
});

test('footer anchor links point to the home page so they work on subpages', function () {
    $this->get(route('guides.show', 'notfallhandbuch'))
        ->assertSee(route('home').'#features', false)
        ->assertSee(route('home').'#kontakt', false);
});

test('footer links to the company social profiles', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Folgen')
        ->assertSee('https://www.linkedin.com/company/arento-ai-gmbh', false)
        ->assertSee('https://www.reddit.com/user/ArentoAI', false);
});
