<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('saving an employee syncs role assignments with deputy flag', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::factory()->for($company)->create();
    $roleA = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Werkstatt',
        'sort' => 0,
    ]);
    $roleB = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'IT',
        'sort' => 1,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.edit', ['employee' => $employee])
        ->set("roleAssignments.{$roleA->id}", 'main')
        ->set("roleAssignments.{$roleB->id}", 'deputy')
        ->call('save')
        ->assertHasNoErrors();

    $employee->refresh()->load('roles');
    expect($employee->roles)->toHaveCount(2);

    $main = $employee->roles->firstWhere('id', $roleA->id);
    $deputy = $employee->roles->firstWhere('id', $roleB->id);
    expect((bool) $main->pivot->is_deputy)->toBeFalse()
        ->and((bool) $deputy->pivot->is_deputy)->toBeTrue();
});

test('clearing a role assignment removes the link', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::factory()->for($company)->create();
    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Werkstatt',
        'sort' => 0,
    ]);

    AssignmentSync::attach($employee, $employee->roles(), $role->id, ['is_deputy' => false]);
    expect($employee->fresh()->roles)->toHaveCount(1);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.edit', ['employee' => $employee])
        ->set("roleAssignments.{$role->id}", '')
        ->call('save')
        ->assertHasNoErrors();

    expect($employee->fresh()->roles)->toHaveCount(0);
});

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
        ->assertSee('Mustermann, Erika')
        ->assertSee('Schlüsselmitarbeiter')
        ->assertSee('Vertriebsleitung')
        ->assertSee('0171 1234567');
});

test('employees list shows names as "Lastname, Firstname" sorted by lastname', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    foreach ([
        ['Anton', 'Zimmermann'],
        ['Berta', 'Albrecht'],
        ['Carl', 'Müller'],
    ] as [$first, $last]) {
        Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
            'company_id' => $company->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    $response = $this->actingAs($user->fresh())->get(route('employees.index'));
    $response->assertOk();

    $body = $response->getContent();
    $albrechtPos = strpos($body, 'Albrecht, Berta');
    $muellerPos = strpos($body, 'Müller, Carl');
    $zimmermannPos = strpos($body, 'Zimmermann, Anton');

    expect($albrechtPos)->not->toBeFalse()
        ->and($muellerPos)->not->toBeFalse()
        ->and($zimmermannPos)->not->toBeFalse()
        ->and($albrechtPos)->toBeLessThan($muellerPos)
        ->and($muellerPos)->toBeLessThan($zimmermannPos);
});

test('employees hierarchy view renders cytoscape canvas with graph data', function () {
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
    ]);
    $angestellter->managers()->attach($chef->id);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->set('viewMode', 'hierarchy')
        ->assertSee('employee-hierarchy-canvas', false)
        ->assertSee($chef->id, false)
        ->assertSee($angestellter->id, false)
        ->assertSee("edge-{$chef->id}-{$angestellter->id}", false);
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
    ]);
    $angestellter->managers()->attach($chef->id);

    expect($angestellter->managers->first()->first_name)->toBe('Chef')
        ->and($chef->reports)->toHaveCount(1);
});

test('employee can have multiple managers (fachlich + disziplinarisch)', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $chef1 = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Fachlicher',
        'last_name' => 'Vorgesetzter',
    ]);
    $chef2 = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Disziplinarischer',
        'last_name' => 'Vorgesetzter',
    ]);

    $angestellter = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Carla',
        'last_name' => 'Untergebene',
    ]);
    $angestellter->managers()->sync([$chef1->id, $chef2->id]);

    expect($angestellter->managers)->toHaveCount(2);
    expect($chef1->reports->pluck('id')->all())->toContain($angestellter->id);
    expect($chef2->reports->pluck('id')->all())->toContain($angestellter->id);
});

test('employees form saves multiple managers via the multi-select', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $chef1 = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Fachlicher', 'last_name' => 'V',
    ]);
    $chef2 = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Disziplinarischer', 'last_name' => 'V',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.edit')
        ->set('first_name', 'Anna')
        ->set('last_name', 'Beispiel')
        ->set('manager_ids', [$chef1->id, $chef2->id])
        ->call('save')
        ->assertHasNoErrors();

    $anna = Employee::where('first_name', 'Anna')->first();
    expect($anna->managers->pluck('id')->all())->toContain($chef1->id, $chef2->id);
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
        ->test('pages::employees.edit')
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
        ->test('pages::employees.edit')
        ->assertSee('Hauptsitz Eigen')
        ->assertDontSee('Fremder Standort');
});
