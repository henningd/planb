<?php

use App\Support\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('renders the status page with operational state and a green banner', function () {
    SystemSetting::set('platform_status_state', 'operational');

    $this->get('/status')
        ->assertOk()
        ->assertSeeText('Alle Systeme funktionieren')
        ->assertSee('bg-emerald-50', false)
        ->assertSee('border-emerald-300', false)
        ->assertSee('text-emerald-900', false);
});

it('renders the status page with degraded state and a yellow banner', function () {
    SystemSetting::set('platform_status_state', 'degraded');

    $this->get('/status')
        ->assertOk()
        ->assertSeeText('Eingeschränkt')
        ->assertSee('bg-amber-50', false)
        ->assertSee('border-amber-300', false)
        ->assertSee('text-amber-900', false);
});

it('renders the status page with outage state and a red banner', function () {
    SystemSetting::set('platform_status_state', 'outage');

    $this->get('/status')
        ->assertOk()
        ->assertSeeText('Störung')
        ->assertSee('bg-rose-50', false)
        ->assertSee('border-rose-300', false)
        ->assertSee('text-rose-900', false);
});

it('renders the status page with maintenance state and a blue banner', function () {
    SystemSetting::set('platform_status_state', 'maintenance');

    $this->get('/status')
        ->assertOk()
        ->assertSeeText('Wartungsfenster')
        ->assertSee('bg-sky-50', false)
        ->assertSee('border-sky-300', false)
        ->assertSee('text-sky-900', false);
});

it('renders the markdown body from platform_status_incidents', function () {
    SystemSetting::set('platform_status_incidents', "### 2026-04-01 — Beispiel-Incident\n\n*Status:* Behoben — Test-Beschreibung mit Sonder-Marker XYZ-12345.");

    $this->get('/status')
        ->assertOk()
        ->assertSee('<h3', false)
        ->assertSeeText('Beispiel-Incident')
        ->assertSeeText('XYZ-12345');
});

it('exposes the legal.status route name', function () {
    expect(Route::has('legal.status'))->toBeTrue();
});
