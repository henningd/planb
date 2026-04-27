<?php

use App\Enums\SystemOwnership;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Compliance\Evaluator;
use App\Support\SystemOwnershipGroups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('system form persists ownership_kind and is_deputy for employees', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $owner = Employee::factory()->for($company)->create(['first_name' => 'Owner', 'last_name' => 'Person']);
    $deputy = Employee::factory()->for($company)->create(['first_name' => 'Deputy', 'last_name' => 'Person']);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('responsibles', [
            ['employee_id' => $owner->id, 'ownership_kind' => SystemOwnership::Owner->value, 'is_deputy' => false, 'note' => ''],
            ['employee_id' => $deputy->id, 'ownership_kind' => SystemOwnership::Owner->value, 'is_deputy' => true, 'note' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $employees = $system->fresh()->employees;
    $primary = $employees->firstWhere('id', $owner->id);
    $alt = $employees->firstWhere('id', $deputy->id);

    expect($primary->pivot->ownership_kind)->toBe(SystemOwnership::Owner->value)
        ->and((bool) $primary->pivot->is_deputy)->toBeFalse()
        ->and($alt->pivot->ownership_kind)->toBe(SystemOwnership::Owner->value)
        ->and((bool) $alt->pivot->is_deputy)->toBeTrue();
});

test('system form persists ownership for providers and roles', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $provider = ServiceProvider::factory()->for($company)->create(['name' => 'ACME']);
    $role = Role::factory()->for($company)->create(['name' => 'IT-Team']);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Mail',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('providerAssignments', [
            ['provider_id' => $provider->id, 'ownership_kind' => SystemOwnership::Operator->value, 'is_deputy' => false, 'note' => ''],
        ])
        ->set('roleAssignments', [
            ['role_id' => $role->id, 'ownership_kind' => SystemOwnership::ContactPerson->value, 'is_deputy' => false, 'note' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $system->fresh()->load(['serviceProviders', 'roles']);

    expect($fresh->serviceProviders->first()->pivot->ownership_kind)->toBe(SystemOwnership::Operator->value)
        ->and($fresh->roles->first()->pivot->ownership_kind)->toBe(SystemOwnership::ContactPerson->value);
});

test('SystemOwnershipGroups separates primaries from deputies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $primary = Employee::factory()->for($company)->create();
    $deputy = Employee::factory()->for($company)->create();

    $system = System::factory()->for($company)->create();
    $system->employees()->attach($primary->id, ['id' => (string) Str::uuid(), 'ownership_kind' => SystemOwnership::Owner->value, 'is_deputy' => false, 'sort' => 0]);
    $system->employees()->attach($deputy->id, ['id' => (string) Str::uuid(), 'ownership_kind' => SystemOwnership::Owner->value, 'is_deputy' => true, 'sort' => 1]);

    $groups = SystemOwnershipGroups::group($system->fresh()->employees);

    expect($groups[SystemOwnership::Owner->value]['primaries']->count())->toBe(1)
        ->and($groups[SystemOwnership::Owner->value]['deputies']->count())->toBe(1)
        ->and($groups[SystemOwnership::Operator->value]['total'])->toBe(0);
});

test('compliance check rewards system with owner and operator', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $owner = Employee::factory()->for($company)->create();
    $op = Employee::factory()->for($company)->create();

    $system = System::factory()->for($company)->create();
    $system->employees()->attach($owner->id, ['id' => (string) Str::uuid(), 'ownership_kind' => SystemOwnership::Owner->value, 'is_deputy' => false, 'sort' => 0]);
    $system->employees()->attach($op->id, ['id' => (string) Str::uuid(), 'ownership_kind' => SystemOwnership::Operator->value, 'is_deputy' => false, 'sort' => 1]);

    $report = Evaluator::for($company);
    $check = collect($report->items)->firstWhere('check.key', 'systems.ownership');

    expect($check['result']->status->value)->toBe('pass');
});
