<?php

use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: ScenarioRun}
 */
function incidentWithSteps(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $run = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Serverausfall Rechenzentrum',
        'mode' => 'real',
        'started_at' => now()->subMinutes(30),
    ]);

    ScenarioRunStep::create(['scenario_run_id' => $run->id, 'sort' => 1, 'title' => 'Notstrom prüfen', 'checked_at' => now()]);
    ScenarioRunStep::create(['scenario_run_id' => $run->id, 'sort' => 2, 'title' => 'Dienstleister anrufen']);

    return [$user->fresh(), $company, $run];
}

test('sharing generates a token and the public status page renders read-only', function () {
    [$user, , $run] = incidentWithSteps();

    Livewire::actingAs($user)
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->call('shareLage')
        ->assertHasNoErrors();

    $run->refresh();
    expect($run->share_token)->not->toBeNull();

    // Öffentlich, ohne Login erreichbar.
    $this->get(route('incident.status', ['token' => $run->share_token]))
        ->assertOk()
        ->assertSee('Serverausfall Rechenzentrum')
        ->assertSee('Dienstleister anrufen')   // offener Schritt
        ->assertSee('1 / 2 Schritte');          // Fortschritt
});

test('an invalid or revoked token returns 404', function () {
    [$user, , $run] = incidentWithSteps();

    $this->get(route('incident.status', ['token' => 'doesnotexist123']))->assertNotFound();

    Livewire::actingAs($user)->test('pages::scenario-runs.show', ['run' => $run])->call('shareLage');
    $token = $run->refresh()->share_token;
    $this->get(route('incident.status', ['token' => $token]))->assertOk();

    Livewire::actingAs($user)->test('pages::scenario-runs.show', ['run' => $run])->call('unshareLage');
    $this->get(route('incident.status', ['token' => $token]))->assertNotFound();
});
