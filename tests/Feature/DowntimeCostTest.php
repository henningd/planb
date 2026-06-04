<?php

use App\Models\Company;
use App\Models\System;
use App\Models\User;
use App\Support\DowntimeCost;
use App\Support\Incident\Cockpit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Baut das Beispiel auf: Stromversorgung (Träger, 200 €/h) mit abhängigen
 * Systemen Computer (100), Telefon (50) und – transitiv – Server (300).
 *
 * @return array{company: Company, power: System, computer: System, phone: System, server: System}
 */
function powerScenario(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    test()->actingAs($user->fresh());

    $power = System::create(['name' => 'Stromversorgung', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 200, 'downtime_cost_from_dependents' => true]);
    $computer = System::create(['name' => 'Computer', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 100]);
    $phone = System::create(['name' => 'Telefonanlage', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 50]);
    $server = System::create(['name' => 'Server', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 300]);

    // Computer & Telefon hängen von Strom ab; Server hängt vom Computer ab.
    $computer->dependencies()->attach($power->id, ['sort' => 0]);
    $phone->dependencies()->attach($power->id, ['sort' => 0]);
    $server->dependencies()->attach($computer->id, ['sort' => 0]);

    return compact('company', 'power', 'computer', 'phone', 'server');
}

test('carrier own cost is excluded and total has no double counting', function () {
    ['company' => $company] = powerScenario();

    $dc = DowntimeCost::forCompany($company);

    // 100 (Computer) + 50 (Telefon) + 300 (Server) + 0 (Strom = Träger) = 450
    expect($dc->totalHourly())->toBe(450);
});

test('carrier derived cost is the transitive sum of dependents', function () {
    ['company' => $company, 'power' => $power] = powerScenario();

    $dc = DowntimeCost::forCompany($company);

    expect($dc->effectiveOwnHourly($power->id))->toBe(0)
        ->and($dc->isCarrier($power->id))->toBeTrue()
        ->and($dc->derivedHourly($power->id))->toBe(450)
        ->and(count($dc->transitiveDependentIds($power->id)))->toBe(3);
});

test('cockpit damage rate uses the deduplicated total', function () {
    ['company' => $company] = powerScenario();

    $cockpit = Cockpit::for($company);

    expect((int) $cockpit->damageRatePerHourEur)->toBe(450);
});

test('selecting only the carrier in the cost calculator pulls in its dependents', function () {
    ['power' => $power, 'computer' => $computer, 'phone' => $phone, 'server' => $server] = powerScenario();

    $summary = Livewire\Livewire::test('pages::systems.cost-calculator')
        ->set('selectedSystemIds', [$power->id])
        ->set('durationHours', 2.0)
        ->get('summary');

    // Strom allein gewählt → abhängige Systeme werden einbezogen: 100+50+300 = 450/h
    expect($summary['hourly_total'])->toBe(450)
        ->and($summary['total'])->toBe(900);
});

test('a cycle in dependencies does not cause infinite recursion', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    test()->actingAs($user->fresh());

    $a = System::create(['name' => 'A', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 10, 'downtime_cost_from_dependents' => true]);
    $b = System::create(['name' => 'B', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 20]);
    $a->dependencies()->attach($b->id, ['sort' => 0]);
    $b->dependencies()->attach($a->id, ['sort' => 0]);

    $dc = DowntimeCost::forCompany($company);

    expect($dc->totalHourly())->toBe(20); // A=Träger(0) + B(20)
});

test('the dependencies tab shows downtime costs per hour', function () {
    ['power' => $power, 'computer' => $computer] = powerScenario();

    // Computer-Seite: Strom (Träger) ist eine Abhängigkeit → abgeleiteter Wert.
    $this->get(route('systems.show', ['system' => $computer]))
        ->assertOk()
        ->assertSee('abgeleitet')
        ->assertSee('450 €/h');

    // Strom-Seite: abhängige Systeme erscheinen mit ihren eigenen Kosten.
    $this->get(route('systems.show', ['system' => $power]))
        ->assertOk()
        ->assertSee('100 €/h')
        ->assertSee('50 €/h');
});

test('adding a costed dependency target prompts to deactivate its costs', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $power = System::create(['name' => 'Stromversorgung', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 200]);
    $computer = System::create(['name' => 'Computer', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 100]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $computer])
        ->call('addDependencyById', $power->id)
        ->assertSet('pendingCarrierId', $power->id)
        ->call('confirmDeactivateCarrier')
        ->assertSet('pendingCarrierId', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($power->fresh()->downtime_cost_from_dependents)->toBeTrue()
        ->and($computer->fresh()->dependencies()->whereKey($power->id)->exists())->toBeTrue();
});

test('no prompt when the dependency target has no downtime cost', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $rack = System::create(['name' => 'Rack', 'category' => 'basisbetrieb']);
    $computer = System::create(['name' => 'Computer', 'category' => 'basisbetrieb', 'downtime_cost_per_hour' => 100]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $computer])
        ->call('addDependencyById', $rack->id)
        ->assertSet('pendingCarrierId', null);
});
