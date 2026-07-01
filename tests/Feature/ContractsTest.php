<?php

use App\Models\Company;
use App\Models\Contract;
use App\Models\Location;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function bootContractTenant(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

it('links a contract to provider, systems and locations', function () {
    [, $company] = bootContractTenant();

    $provider = ServiceProvider::factory()->for($company)->create();
    $system = System::factory()->for($company)->create(['name' => 'Klimaanlage']);
    $location = Location::factory()->for($company)->create(['name' => 'Zentrale']);

    $contract = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create();
    $contract->systems()->sync([$system->id]);
    $contract->locations()->sync([$location->id]);

    expect($contract->serviceProvider->id)->toBe($provider->id)
        ->and($contract->systems->pluck('name')->all())->toBe(['Klimaanlage'])
        ->and($contract->locations->pluck('name')->all())->toBe(['Zentrale']);
});

it('computes status from the end date', function () {
    [, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create();

    $active = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create();
    $expiring = Contract::factory()->for($company)->for($provider, 'serviceProvider')->expiringSoon()->create();
    $expired = Contract::factory()->for($company)->for($provider, 'serviceProvider')->expired()->create();

    expect($active->status())->toBe('active')
        ->and($expiring->status())->toBe('expiring')
        ->and($expired->status())->toBe('expired');
});

it('lists contracts of the current company only', function () {
    [$user, $company] = bootContractTenant();
    $other = Company::factory()->for(Team::factory())->create();

    $provider = ServiceProvider::factory()->for($company)->create();
    Contract::factory()->for($company)->for($provider, 'serviceProvider')->create(['title' => 'Eigener Vertrag']);

    $otherProvider = ServiceProvider::factory()->for($other)->create();
    Contract::factory()->for($other)->for($otherProvider, 'serviceProvider')->create(['title' => 'Fremder Vertrag']);

    $this->actingAs($user)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertSee('Eigener Vertrag')
        ->assertDontSee('Fremder Vertrag');
});

it('creates a contract and syncs systems and locations', function () {
    [$user, $company] = bootContractTenant();

    $provider = ServiceProvider::factory()->for($company)->create();
    $system = System::factory()->for($company)->create(['name' => 'Klimaanlage']);
    $location = Location::factory()->for($company)->create(['name' => 'Zentrale']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::contracts.edit')
        ->set('title', 'Wartung Klimaanlage')
        ->set('service_provider_id', $provider->id)
        ->set('coverage', 'business_hours')
        ->set('response_time_minutes', 240)
        ->set('resolution_time_minutes', 1440)
        ->set('availability_percent', '99.90')
        ->set('emergency_hotline', '0800 111222')
        ->set('system_ids', [$system->id])
        ->set('location_ids', [$location->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $contract = Contract::first();
    expect($contract)->not->toBeNull()
        ->and($contract->title)->toBe('Wartung Klimaanlage')
        ->and($contract->service_provider_id)->toBe($provider->id)
        ->and($contract->company_id)->toBe($company->id)
        ->and($contract->response_time_minutes)->toBe(240)
        ->and($contract->systems->pluck('id')->all())->toBe([$system->id])
        ->and($contract->locations->pluck('id')->all())->toBe([$location->id]);
});

it('requires a title and provider', function () {
    [$user] = bootContractTenant();

    Livewire\Livewire::actingAs($user)
        ->test('pages::contracts.edit')
        ->set('title', '')
        ->set('service_provider_id', '')
        ->call('save')
        ->assertHasErrors(['title', 'service_provider_id']);
});

it('edits an existing contract', function () {
    [$user, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create();
    $contract = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create(['title' => 'Alt']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::contracts.edit', ['contract' => $contract])
        ->assertSet('title', 'Alt')
        ->set('title', 'Neu')
        ->call('save')
        ->assertHasNoErrors();

    expect($contract->fresh()->title)->toBe('Neu');
});

it('shows the emergency hotline, falling back to the provider hotline', function () {
    [$user, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create(['hotline' => '0800 PROVIDER']);
    $contract = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create([
        'title' => 'Klima-Wartung',
        'emergency_hotline' => null,
        'response_time_minutes' => 240,
    ]);

    $this->actingAs($user)
        ->get(route('contracts.show', $contract))
        ->assertOk()
        ->assertSee('Klima-Wartung')
        ->assertSee('0800 PROVIDER')
        ->assertSee('allgemeine Dienstleister-Hotline');
});

it('deletes a contract', function () {
    [$user, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create();
    $contract = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create();

    Livewire\Livewire::actingAs($user)
        ->test('pages::contracts.show', ['contract' => $contract])
        ->call('deleteContract')
        ->assertRedirect(route('contracts.index'));

    expect(Contract::find($contract->id))->toBeNull();
});

it('surfaces the linked contract SLA on the system page', function () {
    [$user, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create();
    $system = System::factory()->for($company)->create(['name' => 'Klimaanlage']);
    $contract = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create([
        'title' => 'Klima-Wartung',
        'emergency_hotline' => '0800 KLIMA',
        'response_time_minutes' => 240,
    ]);
    $contract->systems()->sync([$system->id]);

    $this->actingAs($user)
        ->get(route('systems.show', $system))
        ->assertOk()
        ->assertSee('Klima-Wartung')
        ->assertSee('0800 KLIMA')
        ->assertSee('4 Stunden');
});

it('surfaces contracts on the service provider page', function () {
    [$user, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create();
    Contract::factory()->for($company)->for($provider, 'serviceProvider')->create(['title' => 'Rahmenvertrag Netz']);

    $this->actingAs($user)
        ->get(route('service-providers.show', $provider))
        ->assertOk()
        ->assertSee('Rahmenvertrag Netz');
});

it('surfaces contracts on the locations page', function () {
    [$user, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create(['name' => 'Kälte-Service GmbH']);
    $location = Location::factory()->for($company)->create(['name' => 'Zentrale']);
    $contract = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create([
        'title' => 'Klima-Wartung',
        'response_time_minutes' => 240,
    ]);
    $contract->locations()->sync([$location->id]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::locations.index')
        ->assertSee('Zentrale')
        ->assertSee('Klima-Wartung')
        ->assertSee('Kälte-Service GmbH');
});

it('includes contracts with SLA in the printed handbook', function () {
    [$user, $company] = bootContractTenant();
    $provider = ServiceProvider::factory()->for($company)->create(['name' => 'Kälte-Service GmbH']);
    $system = System::factory()->for($company)->create(['name' => 'Klimaanlage']);
    $contract = Contract::factory()->for($company)->for($provider, 'serviceProvider')->create([
        'title' => 'Klima-Wartung',
        'emergency_hotline' => '0800 KLIMA',
        'response_time_minutes' => 240,
    ]);
    $contract->systems()->sync([$system->id]);

    $this->actingAs($user)
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('Verträge &amp; SLA-Zeiten', false)
        ->assertSee('Klima-Wartung')
        ->assertSee('0800 KLIMA');
});
