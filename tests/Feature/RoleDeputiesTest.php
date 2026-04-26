<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('a role can have multiple main and deputy employees', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Test-Rolle',
        'sort' => 0,
    ]);

    $main1 = Employee::factory()->for($company)->create();
    $main2 = Employee::factory()->for($company)->create();
    $dep1 = Employee::factory()->for($company)->create();
    $dep2 = Employee::factory()->for($company)->create();
    $dep3 = Employee::factory()->for($company)->create();

    AssignmentSync::sync($role, $role->employees(), [
        $main1->id => ['is_deputy' => false],
        $main2->id => ['is_deputy' => false],
        $dep1->id => ['is_deputy' => true],
        $dep2->id => ['is_deputy' => true],
        $dep3->id => ['is_deputy' => true],
    ]);

    $role->refresh()->load('employees');

    $mains = $role->employees->where('pivot.is_deputy', false)->pluck('id')->all();
    $deputies = $role->employees->where('pivot.is_deputy', true)->pluck('id')->all();

    expect($mains)->toEqualCanonicalizing([$main1->id, $main2->id]);
    expect($deputies)->toEqualCanonicalizing([$dep1->id, $dep2->id, $dep3->id]);
});

test('switching an employee from main to deputy ends the old row and adds a new one (history preserved)', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Wechsler',
        'sort' => 0,
    ]);
    $emp = Employee::factory()->for($company)->create();

    AssignmentSync::sync($role, $role->employees(), [$emp->id => ['is_deputy' => false]]);

    $rowsAfterFirst = DB::table('employee_role')->where('role_id', $role->id)->where('employee_id', $emp->id)->get();
    expect($rowsAfterFirst)->toHaveCount(1);

    AssignmentSync::sync($role, $role->employees(), [$emp->id => ['is_deputy' => true]]);

    $allRows = DB::table('employee_role')->where('role_id', $role->id)->where('employee_id', $emp->id)->get();
    expect($allRows)->toHaveCount(2);

    $active = $allRows->whereNull('removed_at');
    expect($active)->toHaveCount(1);
    expect((bool) $active->first()->is_deputy)->toBeTrue();

    $ended = $allRows->whereNotNull('removed_at');
    expect($ended)->toHaveCount(1);
    expect((bool) $ended->first()->is_deputy)->toBeFalse();
});

test('roles index page renders the correct cycle through livewire', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Cycle-Test',
        'sort' => 0,
    ]);
    $emp = Employee::factory()->for($company)->create();

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->call('openEdit', $role->id);

    // Initial: nicht zugeordnet
    expect($component->get('assignmentMode'))->toBe([]);

    // → main
    $component->call('cycleAssignment', $emp->id);
    expect($component->get('assignmentMode'))->toBe([$emp->id => 'main']);

    // → deputy
    $component->call('cycleAssignment', $emp->id);
    expect($component->get('assignmentMode'))->toBe([$emp->id => 'deputy']);

    // → entfernt
    $component->call('cycleAssignment', $emp->id);
    expect($component->get('assignmentMode'))->toBe([]);
});

test('save persists deputy flag from livewire form', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Save-Test',
        'sort' => 0,
    ]);
    $main = Employee::factory()->for($company)->create();
    $dep = Employee::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->call('openEdit', $role->id)
        ->call('cycleAssignment', $main->id) // → main
        ->call('cycleAssignment', $dep->id)  // → main
        ->call('cycleAssignment', $dep->id)  // → deputy
        ->call('save')
        ->assertHasNoErrors();

    $role->load('employees');
    $mains = $role->employees->where('pivot.is_deputy', false)->pluck('id')->all();
    $deputies = $role->employees->where('pivot.is_deputy', true)->pluck('id')->all();
    expect($mains)->toBe([$main->id]);
    expect($deputies)->toBe([$dep->id]);
});
