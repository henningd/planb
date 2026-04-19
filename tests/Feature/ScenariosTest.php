<?php

use App\Models\Company;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a company seeds default scenarios with steps', function () {
    $company = Company::factory()->for(Team::factory())->create();

    $scenarios = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->with('steps')
        ->get();

    expect($scenarios->pluck('name')->all())->toContain(
        'Ransomware / Cyberangriff',
        'Serverausfall',
        'Stromausfall',
        'Internet- oder Telefonausfall',
        'Datenpanne / Datenleck',
        'Ausfall wichtiger Dienstleister',
    );

    foreach ($scenarios as $scenario) {
        expect($scenario->steps)->not->toBeEmpty();
    }
});

test('scenarios page renders with seeded playbooks', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('scenarios.index'))
        ->assertOk()
        ->assertSee('Ransomware / Cyberangriff')
        ->assertSee('Serverausfall');
});

test('starting a scenario creates a run with copied steps', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('name', 'Ransomware / Cyberangriff')
        ->firstOrFail();

    $user = $user->fresh();
    $this->actingAs($user);

    $run = ScenarioRun::create([
        'scenario_id' => $scenario->id,
        'started_by_user_id' => $user->id,
        'title' => 'Test-Drill',
        'mode' => 'drill',
        'started_at' => now(),
    ]);

    foreach ($scenario->steps as $step) {
        $run->steps()->create([
            'sort' => $step->sort,
            'title' => $step->title,
            'description' => $step->description,
            'responsible' => $step->responsible,
        ]);
    }

    expect($run->fresh()->steps)->toHaveCount($scenario->steps->count())
        ->and($run->isActive())->toBeTrue();
});

test('run show page renders and allows checking a step', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->firstOrFail();

    $user = $user->fresh();
    $this->actingAs($user);

    $run = ScenarioRun::create([
        'company_id' => $company->id,
        'scenario_id' => $scenario->id,
        'started_by_user_id' => $user->id,
        'title' => 'Smoke Run',
        'mode' => 'drill',
        'started_at' => now(),
    ]);

    $firstStep = $scenario->steps->first();
    $run->steps()->create([
        'sort' => $firstStep->sort,
        'title' => $firstStep->title,
        'description' => $firstStep->description,
        'responsible' => $firstStep->responsible,
    ]);

    $this->get(route('scenario-runs.show', $run))
        ->assertOk()
        ->assertSee('Smoke Run')
        ->assertSee($firstStep->title);
});

test('scenarios page can create a new scenario and redirects to edit page', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::scenarios.index')
        ->set('newName', 'Brand im Serverraum')
        ->set('newDescription', 'Feuer-/Rauchentwicklung im Serverraum')
        ->set('newTrigger', 'Rauchmelder / Sichtkontrolle')
        ->call('createScenario')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('name', 'Brand im Serverraum')->exists())->toBeTrue();
});

test('scenario show page saves metadata', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)->first();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::scenarios.show', ['scenario' => $scenario])
        ->set('name', 'Ransomware (überarbeitet)')
        ->set('description', 'Unser interner Name')
        ->call('saveMeta')
        ->assertHasNoErrors();

    expect($scenario->fresh()->name)->toBe('Ransomware (überarbeitet)');
});

test('scenario show page can add and delete steps', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)->first();
    $initialCount = $scenario->steps()->count();

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::scenarios.show', ['scenario' => $scenario])
        ->set('stepTitle', 'Backup auf externen Server kopieren')
        ->set('stepResponsible', 'IT-Dienstleister')
        ->set('stepSort', 99)
        ->call('saveStep')
        ->assertHasNoErrors();

    expect($scenario->fresh()->steps()->count())->toBe($initialCount + 1);

    $newStep = $scenario->fresh()->steps()->where('sort', 99)->first();
    expect($newStep->title)->toBe('Backup auf externen Server kopieren');

    $component->call('confirmDeleteStep', $newStep->id)
        ->call('deleteStep');

    expect($scenario->fresh()->steps()->count())->toBe($initialCount);
});

test('scenario show page blocks access for other tenants', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $foreignScenario = $company->scenarios()->first();

    $otherUser = User::factory()->create();
    Company::factory()->for($otherUser->currentTeam)->create();

    // The global CurrentCompanyScope filters the route-model binding to
    // the current tenant, so foreign IDs return 404 instead of 403.
    $this->actingAs($otherUser->fresh())
        ->get(route('scenarios.show', $foreignScenario))
        ->assertNotFound();
});
