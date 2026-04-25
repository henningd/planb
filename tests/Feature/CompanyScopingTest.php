<?php

use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\Employee;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('queries are automatically scoped to the authenticated user\'s company', function () {
    [$userA, $companyA] = makeUserWithCompany();
    [$userB, $companyB] = makeUserWithCompany();

    Employee::factory()->for($companyA)->create(['first_name' => 'Alice', 'last_name' => 'A']);
    Employee::factory()->for($companyB)->create(['first_name' => 'Bob', 'last_name' => 'B']);

    $this->actingAs($userA);
    expect(Employee::pluck('first_name')->all())->toBe(['Alice']);

    $this->actingAs($userB);
    expect(Employee::pluck('first_name')->all())->toBe(['Bob']);
});

test('company_id is auto-filled on create from the authenticated user', function () {
    [$user, $company] = makeUserWithCompany();

    $this->actingAs($user);

    $employee = Employee::create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'position' => 'Geschäftsführung',
    ]);

    expect($employee->company_id)->toBe($company->id);
});

test('emergency levels are scoped per company', function () {
    [$userA, $companyA] = makeUserWithCompany();
    [$userB, $companyB] = makeUserWithCompany();

    $this->actingAs($userA);
    expect(EmergencyLevel::pluck('company_id')->unique()->all())->toBe([$companyA->id]);

    $this->actingAs($userB);
    expect(EmergencyLevel::pluck('company_id')->unique()->all())->toBe([$companyB->id]);
});

test('scope can be bypassed explicitly for admin or console contexts', function () {
    [$userA, $companyA] = makeUserWithCompany();
    [, $companyB] = makeUserWithCompany();

    Employee::factory()->for($companyA)->create();
    Employee::factory()->for($companyB)->create();

    $this->actingAs($userA);

    expect(Employee::count())->toBe(1)
        ->and(Employee::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});

/**
 * Helper: creates a user whose personal team has a Company profile attached.
 *
 * @return array{0: User, 1: Company}
 */
function makeUserWithCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}
