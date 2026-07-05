<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard active-lage refreshes live when an incident is closed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $scenario = Scenario::factory()->for($company)->create();
    $run = ScenarioRun::factory()->for($company)->create([
        'scenario_id' => $scenario->id,
        'title' => 'Ausfall Klimaanlage',
        'started_at' => now(),
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())->test('pages::dashboard');
    $component->assertSee('Ausfall Klimaanlage');

    // Notfall wird beendet/abgebrochen → das Echtzeit-Event aktualisiert das Dashboard.
    $run->forceFill(['aborted_at' => now()])->save();

    $component->call('refreshIncidentState')->assertDontSee('Ausfall Klimaanlage');
});

test('dashboard shows onboarding hint when no company exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Firmenprofil anlegen')
        ->assertSee('Willkommen');
});

test('dashboard shows company name and crisis-role holder once a company is set up', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma GmbH']);
    Employee::factory()->for($company)->withCrisisRole(CrisisRole::Management)->create([
        'first_name' => 'Erika', 'last_name' => 'Mustermann',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Musterfirma GmbH')
        ->assertSee('Erika Mustermann')
        ->assertSee('Hauptansprechpartner');
});

test('dashboard warns when no management crisis-role is assigned', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Noch keine Geschäftsführung als Krisenrolle hinterlegt');
});
