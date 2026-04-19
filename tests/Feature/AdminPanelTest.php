<?php

use App\Models\Company;
use App\Models\GlobalScenario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non super admins are blocked from the admin area', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('admin.index'))
        ->assertForbidden();
});

test('super admin sees overview with stats', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.index'))
        ->assertOk()
        ->assertSee('Superadmin-Modus')
        ->assertSee('Administration');
});

test('super admin can list all companies across tenants', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $companyA = Company::factory()->for($userA->currentTeam)->create(['name' => 'Firma Alpha']);
    $companyB = Company::factory()->for($userB->currentTeam)->create(['name' => 'Firma Beta']);

    $this->actingAs($admin->fresh())
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertSee('Firma Alpha')
        ->assertSee('Firma Beta');
});

test('super admin can create a global scenario', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.scenarios.index')
        ->set('name', 'Brand im Serverraum')
        ->set('description', 'Feuer im Serverraum')
        ->set('trigger', 'Rauch oder Brandmelder')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(GlobalScenario::where('name', 'Brand im Serverraum')->exists())->toBeTrue();
});

test('new companies receive active global scenarios from the library', function () {
    // The GlobalScenariosSeeder is not run in tests; seed a single global scenario manually.
    $global = GlobalScenario::create([
        'name' => 'Test-Playbook',
        'description' => 'x',
        'trigger' => 'y',
        'is_active' => true,
        'sort' => 1,
    ]);
    $global->steps()->create([
        'sort' => 1,
        'title' => 'Erster Schritt',
        'description' => 'desc',
        'responsible' => 'CEO',
    ]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenarioNames = $company->scenarios()->pluck('name')->all();
    expect($scenarioNames)->toContain('Test-Playbook');

    $scenario = $company->scenarios()->where('name', 'Test-Playbook')->first();
    expect($scenario->steps()->count())->toBe(1);
});

test('inactive global scenarios are not copied to new companies', function () {
    GlobalScenario::create([
        'name' => 'Nicht ausliefern',
        'is_active' => false,
        'sort' => 1,
    ]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    expect($company->scenarios()->where('name', 'Nicht ausliefern')->exists())->toBeFalse();
});
