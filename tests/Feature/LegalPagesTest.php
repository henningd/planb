<?php

use App\Support\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('renders the imprint page with the configured text', function () {
    SystemSetting::set('platform_imprint', "Anbieter: ACME GmbH\nMusterstr. 1, 12345 Musterstadt");

    $this->get('/impressum')
        ->assertOk()
        ->assertSeeText('Impressum')
        ->assertSeeText('ACME GmbH');
});

it('renders the imprint page with an empty-state hint when no text is configured', function () {
    SystemSetting::set('platform_imprint', '');

    $this->get('/impressum')
        ->assertOk()
        ->assertSeeText('Impressum')
        ->assertSeeText('Inhalt steht aus.')
        ->assertSeeText('platform_imprint');
});

it('renders the privacy page', function () {
    SystemSetting::set('platform_privacy', 'Wir verarbeiten personenbezogene Daten gemäß DSGVO.');

    $this->get('/datenschutz')
        ->assertOk()
        ->assertSeeText('Datenschutzerklärung')
        ->assertSeeText('DSGVO');
});

it('renders the terms page', function () {
    SystemSetting::set('platform_terms', 'AGB-Inhalt für die Plattform.');

    $this->get('/agb')
        ->assertOk()
        ->assertSeeText('Allgemeine Geschäftsbedingungen')
        ->assertSeeText('AGB-Inhalt');
});

it('renders the AVV page with markdown content', function () {
    $this->get('/auftragsverarbeitung')
        ->assertOk()
        ->assertSeeText('Auftragsverarbeitung')
        ->assertSeeText('Subunternehmer')
        ->assertSeeText('Arento AI GmbH');
});

it('renders the TOM page', function () {
    $this->get('/tom')
        ->assertOk()
        ->assertSeeText('Technische und organisatorische Maßnahmen')
        ->assertSeeText('TLS')
        ->assertSeeText('bcrypt');
});

it('renders the subprocessors page with a table', function () {
    $this->get('/subprocessors')
        ->assertOk()
        ->assertSeeText('Subprocessors')
        ->assertSeeText('DigitalOcean')
        ->assertSeeText('Strato')
        ->assertSee('<table>', false);
});

it('serves a security.txt under /.well-known/security.txt', function () {
    $response = $this->get('/.well-known/security.txt');
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/plain');
    expect($response->getContent())
        ->toContain('Contact: mailto:')
        ->toContain('Expires:')
        ->toContain('Preferred-Languages: de, en');
});

it('exposes the new compliance route names', function () {
    expect(Route::has('legal.av_contract'))->toBeTrue();
    expect(Route::has('legal.tom'))->toBeTrue();
    expect(Route::has('legal.subprocessors'))->toBeTrue();
    expect(Route::has('legal.security_txt'))->toBeTrue();
});

it('links the compliance routes from the welcome page footer', function () {
    $response = $this->get('/');
    $response->assertOk()
        ->assertSee(route('legal.av_contract'), false)
        ->assertSee(route('legal.tom'), false)
        ->assertSee(route('legal.subprocessors'), false);
});

it('exposes legal routes by name', function () {
    expect(Route::has('legal.imprint'))->toBeTrue();
    expect(Route::has('legal.privacy'))->toBeTrue();
    expect(Route::has('legal.terms'))->toBeTrue();
});

it('shows configured contact email and phone on the home page', function () {
    SystemSetting::set('platform_contact_email', 'demo@example.com');
    SystemSetting::set('platform_contact_phone', '+49 30 123456');

    $this->get('/')
        ->assertOk()
        ->assertSee('mailto:demo@example.com', false)
        ->assertSeeText('+49 30 123456');
});

it('falls back to a hint when no contact data and no self-service registration are available', function () {
    SystemSetting::set('platform_contact_email', '');
    SystemSetting::set('platform_contact_phone', '');
    SystemSetting::set('registration_enabled', false);

    $this->get('/')
        ->assertOk()
        ->assertSeeText('Kontaktdaten werden in den Plattform-Einstellungen hinterlegt');
});

it('shows register and login CTAs when self-service registration is enabled', function () {
    SystemSetting::set('registration_enabled', true);

    $this->get('/')
        ->assertOk()
        ->assertSeeText('Kostenlos starten')
        ->assertSeeText('Anmelden')
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

it('shows the company name in the footer unobtrusively', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Arento AI GmbH i. G.')
        ->assertSeeText('Ein Produkt der');
});

it('links the legal routes from the welcome page footer', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('legal.imprint'), false)
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('legal.terms'), false);
});

it('shows the compliance & audit section on the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Compliance, Audit & Operations')
        ->assertSeeText('Compliance-Dashboard')
        ->assertSeeText('Risiko-Register')
        ->assertSeeText('Lessons Learned')
        ->assertSeeText('Live-Krisenstab')
        ->assertSeeText('Audit-Log')
        ->assertSeeText('Monitoring-Integration')
        ->assertSee('id="compliance"', false);
});
