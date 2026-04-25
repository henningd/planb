<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can assign a crisis role to an employee', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->set('first_name', 'Anna')
        ->set('last_name', 'Beispiel')
        ->set('crisis_role', CrisisRole::EmergencyOfficer->value)
        ->call('save')
        ->assertHasNoErrors();

    $employee = Employee::where('last_name', 'Beispiel')->firstOrFail();
    expect($employee->crisis_role)->toBe(CrisisRole::EmergencyOfficer)
        ->and($employee->is_crisis_deputy)->toBeFalse();
});

test('deputy with same role is allowed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Employee::factory()->for($company)->withCrisisRole(CrisisRole::ItLead)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->set('first_name', 'Bert')
        ->set('last_name', 'Vertreter')
        ->set('crisis_role', CrisisRole::ItLead->value)
        ->set('is_crisis_deputy', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Employee::where('crisis_role', CrisisRole::ItLead->value)->count())->toBe(2);
});

test('second main holder of the same role is rejected', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Employee::factory()->for($company)->withCrisisRole(CrisisRole::Management)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->set('first_name', 'Doppelt')
        ->set('last_name', 'Vergeben')
        ->set('crisis_role', CrisisRole::Management->value)
        ->set('is_crisis_deputy', false)
        ->call('save')
        ->assertHasErrors(['crisis_role']);
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
