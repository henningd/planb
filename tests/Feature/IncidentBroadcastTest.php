<?php

use App\Enums\ScenarioRunMode;
use App\Events\IncidentStarted;
use App\Models\Company;
use App\Models\Scenario;
use App\Models\User;
use App\Support\Scenarios\StartScenarioRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('broadcasts IncidentStarted when a real incident is triggered', function () {
    Event::fake([IncidentStarted::class]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);

    app(StartScenarioRun::class)->handle($scenario, (int) $user->id, ScenarioRunMode::Real);

    Event::assertDispatched(IncidentStarted::class, function (IncidentStarted $e) use ($company, $scenario, $user) {
        return $e->companyId === $company->id
            && $e->scenarioId === $scenario->id
            && $e->scenarioTitle === 'Stromausfall'
            && $e->startedBy === $user->name;
    });
});

it('does not broadcast IncidentStarted for a drill', function () {
    Event::fake([IncidentStarted::class]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $scenario = Scenario::factory()->for($company)->create();

    app(StartScenarioRun::class)->handle($scenario, (int) $user->id, ScenarioRunMode::Drill);

    Event::assertNotDispatched(IncidentStarted::class);
});

it('shows a banner in the dashboard when an incident is broadcast', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('incident-alert')
        ->assertSet('companyId', $company->id)
        ->call('onIncidentStarted', [
            'run_id' => 'run-9',
            'scenario_id' => 'scn-9',
            'scenario_title' => 'Stromausfall',
            'started_by' => 'Max',
        ])
        ->assertSet('alert.title', 'Stromausfall')
        ->assertSee('Notfall ausgelöst')
        ->assertSee('Stromausfall')
        ->assertSee('Max')
        ->call('dismiss')
        ->assertSet('alert', null);
});

it('broadcasts on the private company channel with the incident payload', function () {
    $event = new IncidentStarted('comp-1', 'run-1', 'scn-1', 'Brand im Serverraum', 'Anna Test');

    expect($event->broadcastOn()[0]->name)->toBe('private-company.comp-1')
        ->and($event->broadcastAs())->toBe('incident.started')
        ->and($event->broadcastWith())->toMatchArray([
            'run_id' => 'run-1',
            'scenario_id' => 'scn-1',
            'scenario_title' => 'Brand im Serverraum',
            'started_by' => 'Anna Test',
        ]);
});
