<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('employees are tenant-scoped', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();

    Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $user->currentCompany()->id,
        'first_name' => 'Eigen',
        'last_name' => 'Mitarbeiter',
    ]);
    Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $otherCompany->id,
        'first_name' => 'Fremd',
        'last_name' => 'Mitarbeiter',
    ]);

    $this->actingAs($user->fresh());

    expect(Employee::pluck('first_name')->all())->toBe(['Eigen']);
});

test('employees page renders with search and department filter', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'department' => 'Vertrieb',
        'position' => 'Vertriebsleitung',
        'mobile_phone' => '0171 1234567',
        'is_key_personnel' => true,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee('Mitarbeiter')
        ->assertSee('Erika Mustermann')
        ->assertSee('Schlüsselmitarbeiter')
        ->assertSee('Vertriebsleitung')
        ->assertSee('0171 1234567');
});

test('manager self-reference persists correctly', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $chef = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Chef',
        'last_name' => 'Mueller',
    ]);

    $angestellter = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'manager_id' => $chef->id,
    ]);

    expect($angestellter->manager->first_name)->toBe('Chef')
        ->and($chef->reports)->toHaveCount(1);
});

test('employee can be assigned a location and the form persists it', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $hq = Location::factory()->for($company)->create([
        'name' => 'Hauptsitz',
        'is_headquarters' => true,
        'sort' => 0,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->set('first_name', 'Eva')
        ->set('last_name', 'Beispiel')
        ->set('location_id', $hq->id)
        ->call('save')
        ->assertHasNoErrors();

    $employee = Employee::where('first_name', 'Eva')->first();
    expect($employee)->not->toBeNull();
    expect($employee->location_id)->toBe($hq->id);
    expect($employee->location->name)->toBe('Hauptsitz');
});

test('employee location_id is nulled when the location is deleted (cascade)', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $location = Location::factory()->for($company)->create([
        'name' => 'Werkstatt',
        'sort' => 1,
    ]);

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Bernd',
        'last_name' => 'Schneider',
        'location_id' => $location->id,
    ]);

    $location->delete();

    expect($employee->fresh()->location_id)->toBeNull();
});

test('location selector shows only locations of current tenant', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    Location::factory()->for($company)->create([
        'name' => 'Hauptsitz Eigen',
        'sort' => 0,
    ]);

    $other = User::factory()->create();
    $otherCompany = Company::factory()->for($other->currentTeam)->create();
    Location::factory()->for($otherCompany)->create([
        'name' => 'Fremder Standort',
        'sort' => 0,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->assertSee('Hauptsitz Eigen')
        ->assertDontSee('Fremder Standort');
});
