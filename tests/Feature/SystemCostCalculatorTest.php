<?php

use App\Models\Company;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function bootCostCalculatorTenant(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('cost calculator renders for authenticated user', function () {
    [$user] = bootCostCalculatorTenant();

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->assertOk()
        ->assertSee(__('Ausfallrechner'));
});

test('cost calculator shows empty-state when no systems exist', function () {
    [$user] = bootCostCalculatorTenant();

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->assertSee(__('Noch keine Systeme erfasst — ohne Systeme kann hier nichts berechnet werden.'));
});

test('selecting one system calculates partial cost', function () {
    [$user, $company] = bootCostCalculatorTenant();

    $erp = System::factory()->for($company)->create([
        'name' => 'ERP',
        'downtime_cost_per_hour' => 1500,
    ]);

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->set('durationHours', 4)
        ->set('selectedSystemIds', [$erp->id])
        ->assertSet('summary.hourly_total', 1500)
        ->assertSet('summary.total', 6000)
        ->assertSet('summary.selected_count', 1)
        ->assertSet('summary.missing_cost_count', 0);
});

test('selecting multiple systems aggregates hourly cost', function () {
    [$user, $company] = bootCostCalculatorTenant();

    $erp = System::factory()->for($company)->create(['name' => 'ERP', 'downtime_cost_per_hour' => 1500]);
    $shop = System::factory()->for($company)->create(['name' => 'Webshop', 'downtime_cost_per_hour' => 500]);
    System::factory()->for($company)->create(['name' => 'Intranet', 'downtime_cost_per_hour' => 100]);

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->set('durationHours', 8)
        ->set('selectedSystemIds', [$erp->id, $shop->id])
        ->assertSet('summary.hourly_total', 2000)
        ->assertSet('summary.total', 16000)
        ->assertSet('summary.selected_count', 2);
});

test('systems with null or zero cost count as zero', function () {
    [$user, $company] = bootCostCalculatorTenant();

    $a = System::factory()->for($company)->create(['name' => 'A', 'downtime_cost_per_hour' => null]);
    $b = System::factory()->for($company)->create(['name' => 'B', 'downtime_cost_per_hour' => 0]);
    $c = System::factory()->for($company)->create(['name' => 'C', 'downtime_cost_per_hour' => 200]);

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->set('durationHours', 2)
        ->set('selectedSystemIds', [$a->id, $b->id, $c->id])
        ->assertSet('summary.hourly_total', 200)
        ->assertSet('summary.total', 400)
        ->assertSet('summary.selected_count', 3)
        ->assertSet('summary.missing_cost_count', 2);
});

test('fractional duration rounds the totals correctly', function () {
    [$user, $company] = bootCostCalculatorTenant();

    $sys = System::factory()->for($company)->create(['name' => 'Telefon', 'downtime_cost_per_hour' => 333]);

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->set('durationHours', 0.5)
        ->set('selectedSystemIds', [$sys->id])
        ->assertSet('summary.hourly_total', 333)
        ->assertSet('summary.total', 167);
});

test('select all with costs picks only systems with positive hourly cost', function () {
    [$user, $company] = bootCostCalculatorTenant();

    $a = System::factory()->for($company)->create(['name' => 'A', 'downtime_cost_per_hour' => 100]);
    $b = System::factory()->for($company)->create(['name' => 'B', 'downtime_cost_per_hour' => 200]);
    System::factory()->for($company)->create(['name' => 'NoCost1', 'downtime_cost_per_hour' => null]);
    System::factory()->for($company)->create(['name' => 'NoCost2', 'downtime_cost_per_hour' => 0]);

    $component = Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->set('durationHours', 1)
        ->call('selectAllWithCosts');

    $selected = $component->get('selectedSystemIds');

    expect($selected)->toHaveCount(2)
        ->and($selected)->toContain($a->id, $b->id);

    $component->assertSet('summary.hourly_total', 300)
        ->assertSet('summary.total', 300);
});

test('clear selection empties the picked systems', function () {
    [$user, $company] = bootCostCalculatorTenant();

    $sys = System::factory()->for($company)->create(['name' => 'X', 'downtime_cost_per_hour' => 50]);

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->set('selectedSystemIds', [$sys->id])
        ->call('clearSelection')
        ->assertSet('selectedSystemIds', [])
        ->assertSet('summary.selected_count', 0)
        ->assertSet('summary.total', 0);
});

test('set duration helper updates the hours value', function () {
    [$user] = bootCostCalculatorTenant();

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->call('setDuration', 24)
        ->assertSet('durationHours', 24.0);
});

test('negative duration is clamped to zero in the summary', function () {
    [$user, $company] = bootCostCalculatorTenant();

    $sys = System::factory()->for($company)->create(['name' => 'X', 'downtime_cost_per_hour' => 100]);

    Livewire::actingAs($user)
        ->test('pages::systems.cost-calculator')
        ->set('durationHours', -5)
        ->set('selectedSystemIds', [$sys->id])
        ->assertSet('summary.duration', 0.0)
        ->assertSet('summary.total', 0);
});

test('cost calculator route is gated behind auth', function () {
    $this->get('/some-team/systems/cost-calculator')
        ->assertRedirect(route('login'));
});
