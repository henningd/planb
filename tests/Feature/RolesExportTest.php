<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\System;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('roles export returns JSON with employees and systems', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma']);

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Werkstatt',
        'description' => 'Werkstattleitung und Gesellen.',
        'sort' => 3,
    ]);

    $bernd = Employee::factory()->for($company)->create([
        'first_name' => 'Bernd',
        'last_name' => 'Schneider',
        'email' => 'bernd@example.test',
    ]);
    $jonas = Employee::factory()->for($company)->create([
        'first_name' => 'Jonas',
        'last_name' => 'Mueller',
        'email' => 'jonas@example.test',
    ]);

    AssignmentSync::attach($role, $role->employees(), $bernd->id, ['is_deputy' => false]);
    AssignmentSync::attach($role, $role->employees(), $jonas->id, ['is_deputy' => true]);

    $system = System::factory()->for($company)->create(['name' => 'Handwerkersoftware']);
    AssignmentSync::attach($role, $role->systems(), $system->id, ['raci_role' => 'R', 'note' => 'Hauptverantwortung']);

    $response = $this->actingAs($user->fresh())->get(route('roles.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('application/json');

    $payload = json_decode($response->streamedContent(), true);

    expect($payload['version'])->toBe(1)
        ->and($payload['company'])->toBe('Musterfirma');

    $exported = collect($payload['roles'])->firstWhere('name', 'Werkstatt');
    expect($exported)->not->toBeNull()
        ->and($exported['description'])->toBe('Werkstattleitung und Gesellen.')
        ->and($exported['sort'])->toBe(3)
        ->and($exported['is_system_role'])->toBeFalse()
        ->and($exported['employees'])->toHaveCount(2);

    $deputies = collect($exported['employees'])->where('is_deputy', true);
    expect($deputies)->toHaveCount(1)
        ->and($deputies->first()['email'])->toBe('jonas@example.test');

    expect($exported['systems'])->toHaveCount(1)
        ->and($exported['systems'][0])->toMatchArray([
            'name' => 'Handwerkersoftware',
            'raci_role' => 'R',
            'note' => 'Hauptverantwortung',
        ]);
});

test('roles export is scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Eigene Rolle',
        'sort' => 99,
    ]);

    $other = Company::factory()->for(Team::factory())->create();
    Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'name' => 'Fremde Rolle',
        'sort' => 99,
    ]);

    $response = $this->actingAs($user->fresh())->get(route('roles.export'));
    $payload = json_decode($response->streamedContent(), true);

    $names = collect($payload['roles'])->pluck('name')->all();
    expect($names)->toContain('Eigene Rolle')
        ->and($names)->not->toContain('Fremde Rolle');
});
