<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('departments page renders with create button and existing entries', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'IT',
        'description' => 'Alles rund um interne IT',
        'sort' => 1,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('departments.index'))
        ->assertOk()
        ->assertSeeText('Abteilungen')
        ->assertSeeText('Neue Abteilung')
        ->assertSeeText('IT')
        ->assertSeeText('Alles rund um interne IT');
});

test('departments are tenant-scoped', function () {
    $userA = User::factory()->create();
    $companyA = Company::factory()->for($userA->currentTeam)->create();
    Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $companyA->id, 'name' => 'Aabbcc-Tenant-A-Dept',
    ]);

    $userB = User::factory()->create();
    $companyB = Company::factory()->for($userB->currentTeam)->create();
    Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $companyB->id, 'name' => 'Xxyyzz-Tenant-B-Dept',
    ]);

    $this->actingAs($userA->fresh());

    expect(Department::pluck('name')->all())->toBe(['Aabbcc-Tenant-A-Dept']);
});

test('employee can be assigned to a department via department_id', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $dept = Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'Buchhaltung',
    ]);

    $emp = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'department_id' => $dept->id,
    ]);

    expect($emp->fresh()->department->name)->toBe('Buchhaltung');
});

test('deleting a department nulls the department_id on employees but keeps them', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $dept = Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'Buchhaltung',
    ]);
    $emp = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'department_id' => $dept->id,
    ]);

    $dept->delete();

    expect($emp->fresh())->not->toBeNull()
        ->and($emp->fresh()->department_id)->toBeNull();
});

test('employees list filters by department name', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $deptIt = Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'IT',
    ]);
    $deptVerwaltung = Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'Verwaltung',
    ]);

    Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Anna', 'last_name' => 'ITMensch', 'department_id' => $deptIt->id,
    ]);
    Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Bert', 'last_name' => 'Verwalter', 'department_id' => $deptVerwaltung->id,
    ]);

    $itEmployees = Employee::with('department')
        ->whereHas('department', fn ($q) => $q->where('name', 'IT'))
        ->get();

    expect($itEmployees->pluck('first_name')->all())->toBe(['Anna']);
});
