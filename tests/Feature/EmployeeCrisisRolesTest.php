<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('factory withCrisisRole attaches the system role pivot', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::factory()
        ->for($company)
        ->withCrisisRole(CrisisRole::EmergencyOfficer)
        ->create(['first_name' => 'Anna', 'last_name' => 'Beispiel']);

    $employee->refresh()->load('roles');
    expect($employee->crisisRole())->toBe(CrisisRole::EmergencyOfficer)
        ->and($employee->isCrisisDeputy())->toBeFalse();
});

test('two employees can hold the same system role with different deputy flags', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Employee::factory()->for($company)->withCrisisRole(CrisisRole::ItLead)->create();
    Employee::factory()->for($company)->withCrisisRole(CrisisRole::ItLead, true)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('system_key', CrisisRole::ItLead->value)
        ->firstOrFail();

    expect($role->employees()->count())->toBe(2)
        ->and($role->employees()->wherePivot('is_deputy', false)->count())->toBe(1)
        ->and($role->employees()->wherePivot('is_deputy', true)->count())->toBe(1);
});

test('company crisis role holder lookup returns the assigned employee', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::DataProtectionOfficer)
        ->create(['first_name' => 'DPO', 'last_name' => 'Person']);

    expect($company->crisisRoleHolder(CrisisRole::DataProtectionOfficer)?->id)->toBe($employee->id)
        ->and($company->crisisRoleHolder(CrisisRole::DataProtectionOfficer, true))->toBeNull();
});

test('roleAssignments via employee form persist as system-role pivot', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $itLeadRole = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('system_key', CrisisRole::ItLead->value)
        ->firstOrFail();

    $employee = Employee::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->call('openEdit', $employee->id)
        ->set("roleAssignments.{$itLeadRole->id}", 'main')
        ->call('save')
        ->assertHasNoErrors();

    $employee->refresh()->load('roles');
    expect($employee->crisisRole())->toBe(CrisisRole::ItLead)
        ->and($employee->isCrisisDeputy())->toBeFalse();
});

test('crisisRoleAssignments returns multiple system roles for a multi-role employee', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::factory()
        ->for($company)
        ->withCrisisRole(CrisisRole::Management)
        ->create();

    $emergencyOfficerRole = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('system_key', CrisisRole::EmergencyOfficer->value)
        ->firstOrFail();

    AssignmentSync::attach($employee, $employee->roles(), $emergencyOfficerRole->id, ['is_deputy' => true]);

    $employee->refresh()->load('roles');
    $assignments = $employee->crisisRoleAssignments();
    expect($assignments)->toHaveCount(2);

    $keys = $assignments->pluck('system_key')->all();
    expect($keys)->toContain(CrisisRole::Management->value, CrisisRole::EmergencyOfficer->value);
});
