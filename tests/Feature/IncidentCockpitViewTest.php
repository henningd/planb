<?php

use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('page renders skeleton when no active run', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->assertOk()
        ->assertSee(__('Kein aktiver Notfall'))
        ->assertDontSee(__('Aktiver Notfall:'));
});

test('page shows crisis staff and recovery list when run is active', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $run = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
        'title' => 'Live-Vorfall Test',
    ]);

    ScenarioRunStep::factory()->for($run, 'run')->create([
        'sort' => 1,
        'title' => 'Erster Schritt',
    ]);

    ScenarioRunStep::factory()->for($run, 'run')->create([
        'sort' => 2,
        'title' => 'Zweiter Schritt',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->assertOk()
        ->assertSee(__('Aktiver Notfall:'))
        ->assertSee('Live-Vorfall Test')
        ->assertSee(__('Krisenstab'))
        ->assertSee(__('Wiederanlauf-Reihenfolge'))
        ->assertSee(__('Schritte'))
        ->assertSee(__('Kommunikation'))
        ->assertSee(__('Meldepflichten'))
        ->assertSee('Erster Schritt')
        ->assertSee('Zweiter Schritt');
});

test('endRun closes the active run', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $run = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->call('endRun')
        ->assertHasNoErrors();

    expect($run->fresh()->ended_at)->not->toBeNull();
});

test('toggleStep marks step as checked', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $run = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(2),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    $step = ScenarioRunStep::factory()->for($run, 'run')->create([
        'sort' => 1,
        'title' => 'Stromversorgung prüfen',
        'checked_at' => null,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->call('toggleStep', $step->id)
        ->assertHasNoErrors();

    expect($step->fresh()->checked_at)->not->toBeNull()
        ->and($step->fresh()->checked_by_user_id)->toBe($user->id);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->call('toggleStep', $step->id)
        ->assertHasNoErrors();

    expect($step->fresh()->checked_at)->toBeNull()
        ->and($step->fresh()->checked_by_user_id)->toBeNull();
});

test('with parallel runs the cockpit shows a switcher and defaults to the newest', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $older = ScenarioRun::factory()->for($company)->create([
        'title' => 'Stromausfall Rathaus',
        'started_at' => now()->subHour(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);
    $newer = ScenarioRun::factory()->for($company)->create([
        'title' => 'Ransomware-Verdacht',
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->assertOk()
        ->assertSee('Notfälle gleichzeitig aktiv')
        ->assertSee('Stromausfall Rathaus')
        ->assertSee('Ransomware-Verdacht')
        ->assertSet('activeRunId', $newer->id);
});

test('selectRun switches the whole cockpit to the chosen run', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $older = ScenarioRun::factory()->for($company)->create([
        'title' => 'Stromausfall Rathaus',
        'started_at' => now()->subHour(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);
    ScenarioRunStep::factory()->for($older, 'run')->create(['title' => 'USV prüfen', 'sort' => 1]);
    $newer = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->call('selectRun', $older->id)
        ->assertSet('activeRunId', $older->id)
        ->assertSee('USV prüfen');
});

test('ending the selected run falls back to the next active one', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $older = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subHour(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);
    $newer = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    $component = Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->call('endRun'); // beendet den neuesten (Default-Auswahl)

    expect($newer->fresh()->ended_at)->not->toBeNull()
        ->and($older->fresh()->ended_at)->toBeNull();
    $component->assertSet('activeRunId', $older->id);
});

test('selecting a foreign or unknown run id falls back to the newest own run', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $own = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $foreign = ScenarioRun::factory()->for($otherCompany)->create([
        'started_at' => now(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->call('selectRun', $foreign->id)
        ->assertSet('activeRunId', $own->id);
});

test('a monitoring-triggered run shows the automatic trigger badge instead of Unbekannt', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(5),
        'ended_at' => null,
        'aborted_at' => null,
        'started_by_user_id' => null,
        'source' => 'monitoring',
        'trigger_detail' => 'srv-prod-01',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->assertSee(__('Automatisch · IT-Monitoring'))
        ->assertSee('srv-prod-01')
        ->assertDontSee(__('Unbekannt'));
});
