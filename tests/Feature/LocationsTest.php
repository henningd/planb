<?php

use App\Models\Company;
use App\Models\Location;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create a location via the livewire page', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::locations.index')
        ->set('name', 'Hauptsitz')
        ->set('street', 'Musterstraße 1')
        ->set('postal_code', '70173')
        ->set('city', 'Stuttgart')
        ->set('country', 'DE')
        ->set('is_headquarters', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Location::count())->toBe(1);
});

test('setting a new headquarters resets the previous one', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $first = Location::factory()->for($company)->headquarters()->create();
    $second = Location::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::locations.index')
        ->call('openEdit', $second->id)
        ->set('is_headquarters', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($first->fresh()->is_headquarters)->toBeFalse()
        ->and($second->fresh()->is_headquarters)->toBeTrue();
});

test('locations are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    Location::factory()->for($company)->create(['name' => 'Eigener Sitz']);
    Location::factory()->for($other)->create(['name' => 'Fremder Sitz']);

    $this->actingAs($user->fresh());

    expect(Location::pluck('name')->all())->toBe(['Eigener Sitz']);
});

test('user can delete a location', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $location = Location::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::locations.index')
        ->call('confirmDelete', $location->id)
        ->call('delete');

    expect(Location::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});
