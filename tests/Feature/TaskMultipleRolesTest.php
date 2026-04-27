<?php

use App\Enums\RaciRole;
use App\Models\Company;
use App\Models\Role;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('saving a task with multiple roles assigned persists all of them', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $system = System::factory()->for($company)->create();
    $role1 = Role::factory()->for($company)->create(['name' => 'Role-A']);
    $role2 = Role::factory()->for($company)->create(['name' => 'Role-B']);
    $role3 = Role::factory()->for($company)->create(['name' => 'Role-C']);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', 'Test Multi-Roles')
        ->set('newTaskRoles', [
            ['role_id' => $role1->id, 'raci_role' => RaciRole::Accountable->value, 'is_deputy' => false],
            ['role_id' => $role2->id, 'raci_role' => RaciRole::Responsible->value, 'is_deputy' => false],
            ['role_id' => $role3->id, 'raci_role' => RaciRole::Consulted->value, 'is_deputy' => false],
        ])
        ->call('addTask')
        ->assertHasNoErrors();

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('system_id', $system->id)
        ->first();

    expect($task)->not->toBeNull()
        ->and($task->roleAssignees)->toHaveCount(3);

    $assigned = $task->roleAssignees->pluck('id')->all();
    expect($assigned)->toContain($role1->id, $role2->id, $role3->id);
});

test('the same role can be assigned with multiple RACI codes', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $system = System::factory()->for($company)->create();
    $role = Role::factory()->for($company)->create(['name' => 'Geschäftsleitung']);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', 'Doppel-RACI')
        ->set('newTaskRoles', [
            ['role_id' => $role->id, 'raci_role' => RaciRole::Accountable->value, 'is_deputy' => false],
            ['role_id' => $role->id, 'raci_role' => RaciRole::Consulted->value, 'is_deputy' => false],
        ])
        ->call('addTask')
        ->assertHasNoErrors();

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('system_id', $system->id)
        ->first();

    expect($task->roleAssignees)->toHaveCount(2);
    $codes = $task->roleAssignees->map(fn ($r) => $r->pivot->raci_role)->sort()->values()->all();
    expect($codes)->toBe(['A', 'C']);
});

test('editing a task and assigning multiple roles persists all of them', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $system = System::factory()->for($company)->create();
    $task = SystemTask::create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'title' => 'Edit Multi',
    ]);
    $role1 = Role::factory()->for($company)->create();
    $role2 = Role::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.show', ['system' => $system])
        ->call('openEditTask', $task->id)
        ->set('editRoles', [
            ['role_id' => $role1->id, 'raci_role' => RaciRole::Accountable->value, 'is_deputy' => false],
            ['role_id' => $role2->id, 'raci_role' => RaciRole::Responsible->value, 'is_deputy' => false],
        ])
        ->call('saveEditTask')
        ->assertHasNoErrors();

    $task->refresh()->load('roleAssignees');
    expect($task->roleAssignees)->toHaveCount(2);
});
