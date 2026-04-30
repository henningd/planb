<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('detail page lists employees, systems and tasks of a role', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Backend-Team',
        'description' => 'Pflegt die Backend-Services im laufenden Betrieb.',
        'sort' => 0,
    ]);

    $main = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Anna', 'last_name' => 'Hauptperson',
        'position' => 'Lead Backend',
    ]);
    $deputy = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Bob', 'last_name' => 'Vertretung',
    ]);
    AssignmentSync::attach($role, $role->employees(), $main->id, ['is_deputy' => false]);
    AssignmentSync::attach($role, $role->employees(), $deputy->id, ['is_deputy' => true]);

    $system = System::factory()->for($company)->create(['name' => 'BackendSystem']);
    AssignmentSync::attach($role, $role->systems(), $system->id, [
        'ownership_kind' => 'operator', 'is_deputy' => false, 'note' => 'Operator für Daily Ops',
    ]);

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'title' => 'Daily Health-Check',
    ]);
    AssignmentSync::attach($task, $task->roleAssignees(), $role->id, ['raci_role' => 'R']);

    $this->actingAs($user->fresh())
        ->get(route('roles.show', $role))
        ->assertOk()
        ->assertSee('Backend-Team')
        ->assertSee('Pflegt die Backend-Services')
        ->assertSee('Hauptperson, Anna')
        ->assertSee('Lead Backend')
        ->assertSee('Vertretung, Bob')
        ->assertSee('BackendSystem')
        ->assertSee('Operator')
        ->assertSee('Operator für Daily Ops')
        ->assertSee('Daily Health-Check')
        ->assertSee('R · Durchführend');
});

test('mandatory roles cannot be deleted from the detail page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $mandatory = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->whereNotNull('system_key')
        ->firstOrFail();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.show', ['role' => $mandatory])
        ->call('delete')
        ->assertNoRedirect();

    expect(Role::withoutGlobalScope(CurrentCompanyScope::class)->find($mandatory->id))->not->toBeNull();
});

test('custom role can be deleted from the detail page and redirects to the index', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Wegwerf',
        'sort' => 99,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.show', ['role' => $role])
        ->call('delete')
        ->assertRedirect(route('roles.index'));

    expect(Role::withoutGlobalScope(CurrentCompanyScope::class)->find($role->id))->toBeNull();
});

test('roles index dropdown links to the role detail page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Index-Test',
        'sort' => 50,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('roles.index'))
        ->assertOk()
        ->assertSee(route('roles.show', $role), false)
        ->assertSee('Details');
});
