<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
        ->assertSee('Erika Mustermann')
        ->assertSee('Schlüsselmitarbeiter')
        ->assertSee('Vertriebsleitung')
        ->assertSee('0171 1234567');
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
        'manager_id' => $chef->id,
    ]);

    expect($angestellter->manager->first_name)->toBe('Chef')
        ->and($chef->reports)->toHaveCount(1);
});
