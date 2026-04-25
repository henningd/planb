<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\Employee;
use App\Models\Team;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a company seeds three default emergency levels', function () {
    $company = Company::factory()->for(Team::factory())->create();

    $levels = EmergencyLevel::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->orderBy('sort')
        ->pluck('name')
        ->all();

    expect($levels)->toBe(['Kritisch', 'Wichtig', 'Beobachten']);
});

test('default levels have descriptions and reactions populated', function () {
    $company = Company::factory()->for(Team::factory())->create();

    $levels = EmergencyLevel::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->get();

    foreach ($levels as $level) {
        expect($level->description)->not->toBeEmpty()
            ->and($level->reaction)->not->toBeEmpty();
    }
});

test('the management crisis-role holder is the primary contact', function () {
    $company = Company::factory()->for(Team::factory())->create();

    Employee::factory()->for($company)->withCrisisRole(CrisisRole::Management)->create([
        'first_name' => 'Erika', 'last_name' => 'Mustermann',
    ]);

    expect($company->hasPrimaryContact())->toBeTrue()
        ->and($company->primaryContact()->fullName())->toBe('Erika Mustermann');
});

test('a company without management crisis-role has no primary contact', function () {
    $company = Company::factory()->for(Team::factory())->create();

    Employee::factory()->for($company)->create();

    expect($company->hasPrimaryContact())->toBeFalse()
        ->and($company->primaryContact())->toBeNull();
});
