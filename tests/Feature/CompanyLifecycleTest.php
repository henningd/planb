<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\EmergencyLevel;
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

test('the first contact of a company becomes the primary contact automatically', function () {
    $company = Company::factory()->for(Team::factory())->create();

    $first = Contact::withoutGlobalScope(CurrentCompanyScope::class)
        ->create([
            'company_id' => $company->id,
            'name' => 'Erika Mustermann',
            'type' => 'intern',
        ]);

    expect($first->is_primary)->toBeTrue()
        ->and($company->hasPrimaryContact())->toBeTrue();
});

test('subsequent contacts do not overwrite the existing primary', function () {
    $company = Company::factory()->for(Team::factory())->create();

    Contact::withoutGlobalScope(CurrentCompanyScope::class)
        ->create(['company_id' => $company->id, 'name' => 'Erika', 'type' => 'intern']);

    $second = Contact::withoutGlobalScope(CurrentCompanyScope::class)
        ->create(['company_id' => $company->id, 'name' => 'Max', 'type' => 'intern']);

    expect($second->fresh()->is_primary)->toBeFalse()
        ->and($company->primaryContact()->name)->toBe('Erika');
});
