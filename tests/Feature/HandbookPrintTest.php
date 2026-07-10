<?php

use App\Enums\CrisisRole;
use App\Enums\EmergencyResourceType;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\Employee;
use App\Models\OpenItem;
use App\Models\Scenario;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Database\Seeders\GlobalScenariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(GlobalScenariosSeeder::class));

test('handbook print view renders with full data', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma GmbH']);

    Employee::factory()->for($company)->withCrisisRole(CrisisRole::Management)->create([
        'first_name' => 'Erika', 'last_name' => 'Mustermann',
    ]);

    $provider = ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'IT-Partner XY',
        'hotline' => '0800 111222',
    ]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
    ]);

    AssignmentSync::attach($system, $system->serviceProviders(), $provider->id);

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('Notfall- und Krisenhandbuch')
        ->assertSee('Musterfirma GmbH')
        ->assertSee('Erika Mustermann')
        ->assertSee('IT-Partner XY')
        ->assertSee('Warenwirtschaft')
        ->assertSee('Ransomware / Cyberangriff');
});

test('handbook print includes RACI matrix and tasks per system', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $emp = Employee::factory()->for($company)->create([
        'first_name' => 'Anna', 'last_name' => 'Bei-Spiel',
    ]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Backup-System-X',
        'category' => 'geschaeftsbetrieb',
    ]);

    AssignmentSync::attach($system, $system->employees(), $emp->id, ['raci_role' => 'A']);

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'title' => 'Tagliche-Pruefung-Z',
        'description' => 'Status checken',
        'sort' => 0,
    ]);
    AssignmentSync::attach($task, $task->assignees(), $emp->id, ['raci_role' => 'R']);

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('Verantwortlichkeiten und Aufgaben pro System')
        ->assertSee('Backup-System-X')
        ->assertSee('Anna Bei-Spiel')
        ->assertSee('Tagliche-Pruefung-Z');
});

test('handbook print describes the crisis team structure and deputy rule', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('Krisenorganisation und Krisenstab')
        ->assertSee('Funktionen im Krisenstab')
        ->assertSee('Krisenstabsleitung')
        ->assertSee('Lagebild-Funktion')
        ->assertSee('Protokollführung')
        ->assertSee('Fachberater je Lage')
        ->assertSee('Automatische Vertretung')
        ->assertSee('übernimmt die hinterlegte Vertretung automatisch die Aufgaben und Befugnisse');
});

test('handbook print shows the open items chapter when items exist', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    OpenItem::factory()->for($company)->create([
        'title' => 'Ausweichstandort noch nicht abgestimmt',
        'relevance' => 'Ohne abgestimmten Ausweichstandort ist der Wiederanlauf nicht gesichert.',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('14. Offene Punkte / Klärpunkte')
        ->assertSee('Ausweichstandort noch nicht abgestimmt')
        ->assertSee('Governance- und Audit-Nachweis');
});

test('handbook print omits the open items chapter when there are none', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertDontSee('Offene Punkte / Klärpunkte');
});

test('handbook print shows the crisis room chapter when configured', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'crisis_room_primary' => 'Hauptsitz, Gebäude A, Raum 1.12',
        'crisis_room_secondary' => 'Standort Süd, Konferenzraum EG',
        'crisis_room_equipment' => ['phone', 'whiteboard'],
        'crisis_room_preparation' => 'Haustechnik richtet die Technik ein.',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('4.4 Lagezentrum und Krisenraum')
        ->assertSee('Hauptsitz, Gebäude A, Raum 1.12')
        ->assertSee('Standort Süd, Konferenzraum EG')
        ->assertSee('Telefon')
        ->assertSee('Whiteboard');
});

test('handbook print omits the crisis room chapter when not configured', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertDontSee('4.4 Lagezentrum und Krisenraum');
});

test('handbook print includes the FORDEC decision guide', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('4.5 FORDEC-Leitfaden für Krisenentscheidungen')
        ->assertSee('Was wissen wir sicher?')
        ->assertSee('Wann prüfen wir die Entscheidung erneut?')
        ->assertSee('Beispiel');
});

test('handbook print shows a scenario alarm chain when maintained', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Brandfall nachts',
        'trigger' => 'Brandmeldeanlage schlägt außerhalb der Geschäftszeiten an.',
        'alarm_chain_detector' => 'Nachtwache am Empfang',
        'alarm_chain_comms_approval' => 'Geschäftsführung',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('Brandfall nachts')
        ->assertSee('Alarmkette')
        ->assertSee('Wer erkennt / meldet?')
        ->assertSee('Nachtwache am Empfang')
        ->assertSee('Wer gibt die Kommunikation frei?');
});

test('handbook print redirects with 404 when no company exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('handbook.print'))
        ->assertNotFound();
});

test('handbook 8.3 emergency resources table shows the configured budget', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => EmergencyResourceType::EmergencyCash,
        'name' => 'Notfallkasse',
        'available_budget' => 5000,
    ]);

    EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => EmergencyResourceType::Other,
        'name' => 'Reserve-Notebook',
        'available_budget' => null,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('8.3 Verfügbare Sofortmittel und Ressourcen')
        ->assertSee('Sofort verfügbares Budget')
        ->assertSee('5.000 €')
        ->assertSee('Reserve-Notebook');
});
