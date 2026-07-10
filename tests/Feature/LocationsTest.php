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

test('a location can store buildings and areas', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::locations.index')
        ->set('name', 'Seniorenzentrum Sonnengarten')
        ->set('street', 'Gartenweg 5')
        ->set('postal_code', '53773')
        ->set('city', 'Hennef')
        ->set('country', 'DE')
        ->set('building_areas', 'Haus A: Pflegebereich A1 (Pflegeleitstelle); Haus B: Verwaltung EG')
        ->call('save')
        ->assertHasNoErrors();

    expect(Location::first()->building_areas)->toBe('Haus A: Pflegebereich A1 (Pflegeleitstelle); Haus B: Verwaltung EG');
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

test('location detail page renders address and a map with a marker when coordinates exist', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $location = Location::factory()->for($company)->create([
        'name' => 'Seniorenzentrum Sonnengarten',
        'street' => 'Gartenweg 5',
        'city' => 'Hennef',
        'lat' => 50.7753,
        'lng' => 7.2830,
        'building_areas' => 'Haus A: Pflegebereich A1',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('locations.detail', $location))
        ->assertOk()
        ->assertSee('Seniorenzentrum Sonnengarten')
        ->assertSee('Gartenweg 5')
        ->assertSee('Haus A: Pflegebereich A1')
        ->assertSee('openstreetmap.org/export/embed.html', false)
        ->assertSee('Größere Karte öffnen');
});

test('location detail page shows a hint when no coordinates are stored', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $location = Location::factory()->for($company)->create([
        'name' => 'Standort ohne Koordinaten',
        'lat' => null,
        'lng' => null,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('locations.detail', $location))
        ->assertOk()
        ->assertSee('keine Koordinaten hinterlegt')
        ->assertDontSee('export/embed.html', false);
});

test('location detail page blocks access for other tenants', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $foreignLocation = Location::factory()->for($company)->create();

    $otherUser = User::factory()->create();
    Company::factory()->for($otherUser->currentTeam)->create();

    // The global CurrentCompanyScope filters the route-model binding to the
    // current tenant, so foreign IDs return 404 instead of 403.
    $this->actingAs($otherUser->fresh())
        ->get(route('locations.detail', $foreignLocation))
        ->assertNotFound();
});
