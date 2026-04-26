<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Employee;
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

test('handbook print redirects with 404 when no company exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('handbook.print'))
        ->assertNotFound();
});
