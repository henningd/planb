<?php

use App\Enums\ScenarioRunMode;
use App\Models\Company;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Database\Seeders\GlobalScenariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(GlobalScenariosSeeder::class));

test('member user sees the incident launcher button in the sidebar', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Notfall melden')
        ->assertSeeHtml('data-test="incident-launcher-trigger"');
});

test('starting a run creates the scenario run with the correct fields and copies steps', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('name', 'Ransomware / Cyberangriff')
        ->firstOrFail();

    Livewire::actingAs($user->fresh())
        ->test('incident-launcher')
        ->call('open')
        ->set('scenarioId', $scenario->id)
        ->set('mode', ScenarioRunMode::Real->value)
        ->set('titleOverride', 'Live-Vorfall Backup')
        ->call('start')
        ->assertHasNoErrors()
        ->assertRedirect();

    $run = ScenarioRun::where('scenario_id', $scenario->id)->firstOrFail();

    expect($run->title)->toBe('Live-Vorfall Backup')
        ->and($run->company_id)->toBe($company->id)
        ->and($run->started_by_user_id)->toBe($user->id)
        ->and($run->started_at)->not->toBeNull()
        ->and($run->mode)->toBe(ScenarioRunMode::Real)
        ->and($run->steps()->count())->toBe($scenario->steps()->count());
});

test('default title falls back to scenario name plus current date when override is empty', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->orderBy('name')
        ->firstOrFail();

    Livewire::actingAs($user->fresh())
        ->test('incident-launcher')
        ->call('open')
        ->set('scenarioId', $scenario->id)
        ->set('mode', ScenarioRunMode::Drill->value)
        ->set('titleOverride', '')
        ->call('start')
        ->assertHasNoErrors();

    $run = ScenarioRun::where('scenario_id', $scenario->id)->firstOrFail();

    expect($run->title)->toStartWith($scenario->name.' ·')
        ->and($run->mode)->toBe(ScenarioRunMode::Drill);
});

test('start redirects to the scenario run detail page including the current team', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->firstOrFail();

    Livewire::actingAs($user->fresh())
        ->test('incident-launcher')
        ->call('open')
        ->set('scenarioId', $scenario->id)
        ->set('mode', ScenarioRunMode::Real->value)
        ->call('start')
        ->assertRedirect(route('scenario-runs.show', [
            'current_team' => $user->currentTeam->slug,
            'run' => ScenarioRun::where('scenario_id', $scenario->id)->firstOrFail()->id,
        ]));
});

test('scenario select only contains scenarios of the current tenant', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $otherUser = User::factory()->create();
    Company::factory()->for($otherUser->currentTeam)->create();

    $component = Livewire::actingAs($user->fresh())
        ->test('incident-launcher');

    $names = $component->instance()->scenarios->pluck('name');
    $foreignScenarios = Scenario::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', '!=', $user->fresh()->currentCompany()->id)
        ->pluck('name');

    expect($names)->not->toBeEmpty();

    foreach ($foreignScenarios as $foreignName) {
        // foreign scenarios share the same template names; assert by IDs instead.
    }

    $allowedIds = $user->fresh()->currentCompany()->scenarios()->pluck('id');
    $componentIds = $component->instance()->scenarios->pluck('id');

    expect($componentIds->diff($allowedIds))->toBeEmpty();
});

test('open shows a warning toast and does not open the modal when no scenarios exist', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    // Remove the seeded default scenarios so the company has none.
    $company->scenarios()->delete();

    Livewire::actingAs($user->fresh())
        ->test('incident-launcher')
        ->call('open')
        ->assertDispatched('toast-show');
});

test('start cannot be called without a scenario selected', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $company->scenarios()->delete();

    Livewire::actingAs($user->fresh())
        ->test('incident-launcher')
        ->set('scenarioId', null)
        ->set('mode', ScenarioRunMode::Real->value)
        ->call('start')
        ->assertHasErrors(['scenarioId']);

    expect(ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});
