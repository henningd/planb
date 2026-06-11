<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('requests to legacy hosts are permanently redirected to the canonical host', function () {
    config()->set('app.canonical_host', 'notfallhandbuch.eu');

    $this->get('https://planb.uniguard.cloud/krisenmanagement?foo=bar')
        ->assertStatus(301)
        ->assertRedirect('https://notfallhandbuch.eu/krisenmanagement?foo=bar');
});

test('the www variant is redirected to the apex domain', function () {
    config()->set('app.canonical_host', 'notfallhandbuch.eu');

    $this->get('https://www.notfallhandbuch.eu/preise')
        ->assertStatus(301)
        ->assertRedirect('https://notfallhandbuch.eu/preise');
});

test('requests to the canonical host are not redirected', function () {
    config()->set('app.canonical_host', 'notfallhandbuch.eu');

    $this->get('https://notfallhandbuch.eu/')->assertOk();
});

test('stripe webhooks and the health check are exempt from the redirect', function () {
    config()->set('app.canonical_host', 'notfallhandbuch.eu');

    // Kein 301 — Stripe und Health-Checker folgen Redirects nicht.
    expect($this->post('https://planb.uniguard.cloud/stripe/webhook')->status())->not->toBe(301)
        ->and($this->get('https://planb.uniguard.cloud/up')->status())->not->toBe(301);
});

test('the redirect is disabled when no canonical host is configured', function () {
    config()->set('app.canonical_host', '');

    $this->get('https://planb.uniguard.cloud/')->assertOk();
});

test('robots.txt points to the sitemap on the canonical domain', function () {
    expect(file_get_contents(public_path('robots.txt')))
        ->toContain('Sitemap: https://notfallhandbuch.eu/sitemap.xml');
});
