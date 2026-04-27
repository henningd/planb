<?php

use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\System;
use App\Models\User;
use App\Support\Incident\Cockpit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('damage rate sums system hourly costs', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    System::factory()->for($company)->create([
        'name' => 'ERP',
        'downtime_cost_per_hour' => 1500,
    ]);

    System::factory()->for($company)->create([
        'name' => 'Webshop',
        'downtime_cost_per_hour' => 500,
    ]);

    System::factory()->for($company)->create([
        'name' => 'Intranet',
        'downtime_cost_per_hour' => null,
    ]);

    $cockpit = Cockpit::for($company);

    expect($cockpit->damageRatePerHourEur)->toBe(2000)
        ->and($cockpit->damageRatePerSystem)->toHaveCount(2)
        ->and($cockpit->damageRatePerSystem[0]['system_name'])->toBe('ERP')
        ->and($cockpit->damageRatePerSystem[0]['hourly'])->toBe(1500)
        ->and($cockpit->damageRatePerSystem[1]['system_name'])->toBe('Webshop')
        ->and($cockpit->damageRatePerSystem[1]['hourly'])->toBe(500);
});

test('damage rate is zero when no systems have a cost', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    System::factory()->for($company)->create([
        'name' => 'A',
        'downtime_cost_per_hour' => null,
    ]);

    System::factory()->for($company)->create([
        'name' => 'B',
        'downtime_cost_per_hour' => 0,
    ]);

    $cockpit = Cockpit::for($company);

    expect($cockpit->damageRatePerHourEur)->toBe(0)
        ->and($cockpit->damageRatePerSystem)->toBe([]);
});

test('view shows the live counter when run is active and cost is set', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
        'title' => 'Live-Vorfall Test',
    ]);

    System::factory()->for($company)->create([
        'name' => 'ERP',
        'downtime_cost_per_hour' => 1234,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->assertOk()
        ->assertSee(__('Aktuell laufender Schaden'))
        ->assertSee('1.234 €/h', false)
        ->assertSee('ERP')
        ->assertSeeHtml('data-test="cockpit-damage-counter"');
});
