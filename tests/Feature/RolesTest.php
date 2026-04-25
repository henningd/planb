<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create a role with employees', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $emp1 = Employee::factory()->for($company)->create();
    $emp2 = Employee::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->set('name', 'Buchhaltung')
        ->call('toggleAssignedEmployee', $emp1->id)
        ->call('toggleAssignedEmployee', $emp2->id)
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::where('name', 'Buchhaltung')->firstOrFail();
    expect($role->employees->pluck('id')->all())->toContain($emp1->id, $emp2->id);
});

test('toggling an assigned employee removes them', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $emp = Employee::factory()->for($company)->create();

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->call('toggleAssignedEmployee', $emp->id)
        ->call('toggleAssignedEmployee', $emp->id);

    expect($component->get('assignedEmployeeIds'))->toBe([]);
});

test('roles are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    Role::factory()->for($company)->create(['name' => 'Eigene']);
    Role::factory()->for($other)->create(['name' => 'Fremde']);

    $this->actingAs($user->fresh());

    expect(Role::pluck('name')->all())->toBe(['Eigene'])
        ->and(Role::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});

test('role can be edited and assignments updated', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $oldEmp = Employee::factory()->for($company)->create();
    $newEmp = Employee::factory()->for($company)->create();

    $role = Role::factory()->for($company)->create(['name' => 'Vertrieb']);
    $role->employees()->attach($oldEmp);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->call('openEdit', $role->id)
        ->set('name', 'Vertrieb & Marketing')
        ->call('toggleAssignedEmployee', $oldEmp->id) // remove
        ->call('toggleAssignedEmployee', $newEmp->id) // add
        ->call('save')
        ->assertHasNoErrors();

    $role->refresh();
    expect($role->name)->toBe('Vertrieb & Marketing')
        ->and($role->employees->pluck('id')->all())->toBe([$newEmp->id]);
});

test('user can delete a role', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $role = Role::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->call('confirmDelete', $role->id)
        ->call('delete');

    expect(Role::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});

test('roles index page renders with assigned employees', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $emp = Employee::factory()->for($company)->create([
        'first_name' => 'Erika', 'last_name' => 'Beispiel',
    ]);
    $role = Role::factory()->for($company)->create(['name' => 'Buchhaltung']);
    $role->employees()->attach($emp);

    $this->actingAs($user->fresh())
        ->get(route('roles.index'))
        ->assertOk()
        ->assertSee('Buchhaltung')
        ->assertSee('Erika Beispiel');
});
