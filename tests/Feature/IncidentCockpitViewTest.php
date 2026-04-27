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
