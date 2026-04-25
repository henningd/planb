<?php

use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\Employee;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemPriority;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a company seeds three default system priorities', function () {
    $company = Company::factory()->for(Team::factory())->create();

    $names = SystemPriority::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->orderBy('sort')
        ->pluck('name')
        ->all();

    expect($names)->toBe(['Kritisch', 'Hoch', 'Normal']);
});

test('systems are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $otherCompany = Company::factory()->for(Team::factory())->create();

    System::withoutGlobalScope(CurrentCompanyScope::class)
        ->create(['company_id' => $company->id, 'name' => 'Warenwirtschaft', 'category' => 'geschaeftsbetrieb']);
    System::withoutGlobalScope(CurrentCompanyScope::class)
        ->create(['company_id' => $otherCompany->id, 'name' => 'Stranger-System', 'category' => 'basisbetrieb']);

    $this->actingAs($user->fresh());

    expect(System::pluck('name')->all())->toBe(['Warenwirtschaft']);
});

test('industry template imports systems with mapped priorities and durations', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.index')
        ->set('templateKey', 'handwerk')
        ->call('loadTemplate')
        ->assertHasNoErrors();

    $systems = System::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->with('priority')
        ->get();

    expect($systems)->not->toBeEmpty();

    $software = $systems->firstWhere('name', 'Handwerkersoftware');
    expect($software)->not->toBeNull()
        ->and($software->category->value)->toBe('geschaeftsbetrieb')
        ->and($software->priority?->name)->toBe('Kritisch')
        ->and($software->rto_minutes)->toBe(240)
        ->and($software->rpo_minutes)->toBe(60);
});

test('re-running the template skips duplicates', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.index')
        ->set('templateKey', 'handwerk')
        ->call('loadTemplate')
        ->call('loadTemplate');

    $count = System::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('name', 'Handwerkersoftware')
        ->count();

    expect($count)->toBe(1);
});

test('json import creates systems with validated data', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $json = json_encode([
        'version' => 1,
        'systems' => [
            [
                'name' => 'SCADA',
                'description' => 'Zentrale Parküberwachung',
                'category' => 'basisbetrieb',
                'priority' => 'Kritisch',
                'rto_minutes' => 60,
                'rpo_minutes' => 15,
            ],
            [
                'name' => 'Direktvermarkter-API',
                'category' => 'geschaeftsbetrieb',
                'priority' => 'Kritisch',
                'rto_minutes' => 240,
                'rpo_minutes' => 60,
            ],
        ],
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.index')
        ->set('importJson', $json)
        ->call('import')
        ->assertHasNoErrors();

    $systems = System::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->get();

    expect($systems->pluck('name')->all())->toContain('SCADA', 'Direktvermarkter-API');

    $scada = $systems->firstWhere('name', 'SCADA');
    expect($scada->priority?->name)->toBe('Kritisch')
        ->and($scada->rto_minutes)->toBe(60)
        ->and($scada->rpo_minutes)->toBe(15);
});

test('json import rejects unknown category', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $json = json_encode([
        'systems' => [
            ['name' => 'Foo', 'category' => 'unsinn'],
        ],
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.index')
        ->set('importJson', $json)
        ->call('import')
        ->assertHasErrors('importJson');
});

test('json import accepts a bare array without version wrapper', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $json = json_encode([
        ['name' => 'Einfaches System', 'category' => 'unterstuetzend'],
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.index')
        ->set('importJson', $json)
        ->call('import')
        ->assertHasNoErrors();

    expect(System::withoutGlobalScope(CurrentCompanyScope::class)->where('name', 'Einfaches System')->exists())
        ->toBeTrue();
});

test('systems page renders and groups by category', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $priority = $company->systemPriorities()->where('sort', 1)->first();

    System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
        'system_priority_id' => $priority->id,
        'rto_minutes' => 240,
        'rpo_minutes' => 60,
        'downtime_cost_per_hour' => 1500,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('systems.index'))
        ->assertOk()
        ->assertSee('Systeme & Betriebskontinuität')
        ->assertSee('Warenwirtschaft')
        ->assertSee('Geschäftsbetrieb')
        ->assertSee('Basisbetrieb')
        ->assertSee('Unterstützend')
        ->assertSee('Kritisch')
        ->assertSee('Max. Ausfall')
        ->assertSee('4 Stunden')
        ->assertSee('1 Stunde')
        ->assertSee('1.500 € / h');
});

test('json export returns all systems of the current tenant in versioned format', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma']);

    $priority = $company->systemPriorities()->where('sort', 1)->first();

    System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'description' => 'ERP',
        'category' => 'geschaeftsbetrieb',
        'system_priority_id' => $priority->id,
        'rto_minutes' => 240,
        'rpo_minutes' => 60,
    ]);

    $response = $this->actingAs($user->fresh())->get(route('systems.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('application/json');

    $payload = json_decode($response->streamedContent(), true);

    expect($payload['version'])->toBe(1)
        ->and($payload['company'])->toBe('Musterfirma')
        ->and($payload['systems'])->toHaveCount(1);

    $exported = $payload['systems'][0];
    expect($exported['name'])->toBe('Warenwirtschaft')
        ->and($exported['category'])->toBe('geschaeftsbetrieb')
        ->and($exported['priority'])->toBe('Kritisch')
        ->and($exported['rto_minutes'])->toBe(240)
        ->and($exported['rpo_minutes'])->toBe(60);
});

test('exported json can be re-imported without loss', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $priority = $company->systemPriorities()->where('sort', 1)->first();

    System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'SCADA',
        'category' => 'basisbetrieb',
        'system_priority_id' => $priority->id,
        'rto_minutes' => 60,
        'rpo_minutes' => 15,
        'downtime_cost_per_hour' => 5000,
    ]);

    $response = $this->actingAs($user->fresh())->get(route('systems.export'));
    $json = $response->streamedContent();

    // Simulate a fresh tenant importing the exported payload.
    $otherUser = User::factory()->create();
    Company::factory()->for($otherUser->currentTeam)->create();

    Livewire\Livewire::actingAs($otherUser->fresh())
        ->test('pages::systems.index')
        ->set('importJson', $json)
        ->call('import')
        ->assertHasNoErrors();

    $imported = System::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $otherUser->currentCompany()->id)
        ->where('name', 'SCADA')
        ->first();

    expect($imported)->not->toBeNull()
        ->and($imported->priority?->name)->toBe('Kritisch')
        ->and($imported->rto_minutes)->toBe(60)
        ->and($imported->rpo_minutes)->toBe(15)
        ->and($imported->downtime_cost_per_hour)->toBe(5000);
});

test('systems within a category are sorted by priority then name', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $kritisch = $company->systemPriorities()->where('sort', 1)->first();
    $hoch = $company->systemPriorities()->where('sort', 2)->first();
    $normal = $company->systemPriorities()->where('sort', 3)->first();

    foreach ([
        ['Zebra', $normal->id],
        ['Apfel', $hoch->id],
        ['Mango', $kritisch->id],
        ['Banane', $kritisch->id],
    ] as [$name, $priorityId]) {
        System::withoutGlobalScope(CurrentCompanyScope::class)->create([
            'company_id' => $company->id,
            'name' => $name,
            'category' => 'basisbetrieb',
            'system_priority_id' => $priorityId,
        ]);
    }

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.index');

    $names = $component->instance()->systemsByCategory['basisbetrieb']->pluck('name')->all();

    expect($names)->toBe(['Banane', 'Mango', 'Apfel', 'Zebra']);
});

test('system form assigns responsible employees with notes and order', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $first = Employee::factory()->for($company)->create([
        'first_name' => 'Martina',
        'last_name' => 'Beispiel',
        'position' => 'IT-Leiterin',
    ]);

    $backup = Employee::factory()->for($company)->create([
        'first_name' => 'Paul',
        'last_name' => 'Vertretung',
        'position' => 'Systemadmin',
    ]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('responsibles', [
            ['employee_id' => $first->id, 'note' => 'nur werktags'],
            ['employee_id' => $backup->id, 'note' => ''],
        ])
        ->call('save');

    $ordered = $system->fresh()->employees;

    expect($ordered->pluck('id')->all())->toBe([$first->id, $backup->id])
        ->and($ordered[0]->pivot->sort)->toBe(0)
        ->and($ordered[0]->pivot->note)->toBe('nur werktags')
        ->and($ordered[1]->pivot->sort)->toBe(1)
        ->and($ordered[1]->pivot->note)->toBeNull();
});

test('addResponsibleById appends and employee search filters available list', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $iris = Employee::factory()->for($company)->create(['first_name' => 'Iris', 'last_name' => 'Schmitt', 'position' => 'IT-Leiterin']);
    $leo = Employee::factory()->for($company)->create(['first_name' => 'Leo', 'last_name' => 'Wagner', 'position' => 'Lager']);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('employeeSearch', 'iris');

    $available = $component->instance()->availableEmployees;
    expect($available->pluck('id')->all())->toBe([$iris->id]);

    $component->call('addResponsibleById', $iris->id);

    expect($component->get('responsibles'))->toBe([
        ['employee_id' => $iris->id, 'raci_role' => '', 'note' => ''],
    ])->and($component->get('employeeSearch'))->toBe('');

    $component->set('employeeSearch', '')
        ->call('addResponsibleById', $leo->id)
        ->call('save');

    expect($system->fresh()->employees->pluck('id')->all())->toBe([$iris->id, $leo->id]);
});

test('moveResponsibleUp reorders responsibles', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $a = Employee::factory()->for($company)->create(['first_name' => 'A', 'last_name' => 'A']);
    $b = Employee::factory()->for($company)->create(['first_name' => 'B', 'last_name' => 'B']);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'X',
        'category' => 'basisbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('responsibles', [
            ['employee_id' => $a->id, 'note' => ''],
            ['employee_id' => $b->id, 'note' => ''],
        ])
        ->call('moveResponsibleUp', 1)
        ->call('save');

    expect($system->fresh()->employees->pluck('id')->all())->toBe([$b->id, $a->id]);
});

test('system form assigns providers with notes and order', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $first = ServiceProvider::factory()->for($company)->create(['name' => 'ACME-IT', 'hotline' => '0800-12345']);
    $backup = ServiceProvider::factory()->for($company)->create(['name' => 'Backup-GmbH']);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('providerAssignments', [
            ['provider_id' => $first->id, 'note' => '24/7 SLA'],
            ['provider_id' => $backup->id, 'note' => ''],
        ])
        ->call('save');

    $ordered = $system->fresh()->serviceProviders;

    expect($ordered->pluck('id')->all())->toBe([$first->id, $backup->id])
        ->and($ordered[0]->pivot->sort)->toBe(0)
        ->and($ordered[0]->pivot->note)->toBe('24/7 SLA')
        ->and($ordered[1]->pivot->note)->toBeNull();
});

test('addProviderById appends and search filters available list', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $acme = ServiceProvider::factory()->for($company)->create(['name' => 'ACME-IT', 'hotline' => '0800-1']);
    $beta = ServiceProvider::factory()->for($company)->create(['name' => 'Beta-Hoster', 'hotline' => '0800-2']);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Mail',
        'category' => 'geschaeftsbetrieb',
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('providerSearch', 'acme');

    $available = $component->instance()->availableProviders;
    expect($available->pluck('id')->all())->toBe([$acme->id]);

    $component->call('addProviderById', $acme->id);

    expect($component->get('providerAssignments'))->toBe([
        ['provider_id' => $acme->id, 'raci_role' => '', 'note' => ''],
    ])->and($component->get('providerSearch'))->toBe('');

    $component->call('addProviderById', $beta->id)->call('save');

    expect($system->fresh()->serviceProviders->pluck('id')->all())->toBe([$acme->id, $beta->id]);
});

test('system form assigns dependencies with notes and order', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $storage = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Storage',
        'category' => 'basisbetrieb',
    ]);
    $db = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Datenbank',
        'category' => 'basisbetrieb',
    ]);
    $erp = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $erp])
        ->set('dependencyAssignments', [
            ['dependency_id' => $storage->id, 'note' => 'nur Schreibzugriff'],
            ['dependency_id' => $db->id, 'note' => ''],
        ])
        ->call('save');

    $ordered = $erp->fresh()->dependencies;

    expect($ordered->pluck('id')->all())->toBe([$storage->id, $db->id])
        ->and($ordered[0]->pivot->sort)->toBe(0)
        ->and($ordered[0]->pivot->note)->toBe('nur Schreibzugriff')
        ->and($ordered[1]->pivot->note)->toBeNull();
});

test('addDependencyById appends and search filters candidates', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $storage = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Storage-Cluster',
        'category' => 'basisbetrieb',
    ]);
    $backup = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Backup-Server',
        'category' => 'basisbetrieb',
    ]);
    $erp = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $erp])
        ->set('dependencySearch', 'storage');

    $available = $component->instance()->availableDependencies;
    expect($available->pluck('id')->all())->toBe([$storage->id]);

    $component->call('addDependencyById', $storage->id);

    expect($component->get('dependencyAssignments'))->toBe([
        ['dependency_id' => $storage->id, 'note' => ''],
    ])->and($component->get('dependencySearch'))->toBe('');

    $component->call('addDependencyById', $backup->id)->call('save');

    expect($erp->fresh()->dependencies->pluck('id')->all())->toBe([$storage->id, $backup->id]);
});

test('system show page lists employees, providers and dependencies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::factory()->for($company)->create(['first_name' => 'Sabine', 'last_name' => 'Ruf', 'position' => 'IT-Leiterin']);
    $provider = ServiceProvider::factory()->for($company)->create(['name' => 'ACME-IT', 'hotline' => '0800-12345']);

    $storage = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Storage',
        'category' => 'basisbetrieb',
    ]);
    $erp = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
        'rto_minutes' => 240,
    ]);

    AssignmentSync::attach($erp, $erp->employees(), $employee->id, ['sort' => 0, 'note' => 'nur werktags']);
    AssignmentSync::attach($erp, $erp->serviceProviders(), $provider->id);
    $erp->dependencies()->sync([$storage->id]);

    $this->actingAs($user->fresh())
        ->get(route('systems.show', ['system' => $erp]))
        ->assertOk()
        ->assertSee('ERP')
        ->assertSee('Sabine Ruf')
        ->assertSee('nur werktags')
        ->assertSee('ACME-IT')
        ->assertSee('0800-12345')
        ->assertSee('Storage');
});

test('system edit saves fallback process, runbook reference and emergency level', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $level = EmergencyLevel::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->orderBy('sort')
        ->first();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('fallback_process', 'Aufträge werden per Telefon/Papier erfasst.')
        ->set('runbook_reference', 'Wiki: /ops/erp-runbook')
        ->set('emergency_level_id', $level?->id)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $system->fresh();
    expect($fresh->fallback_process)->toBe('Aufträge werden per Telefon/Papier erfasst.')
        ->and($fresh->runbook_reference)->toBe('Wiki: /ops/erp-runbook')
        ->and($fresh->emergency_level_id)->toBe($level?->id);
});

test('system show forbids access to a system from another tenant', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $other = Company::factory()->for(Team::factory())->create();
    $foreign = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'name' => 'Foreign',
        'category' => 'basisbetrieb',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('systems.show', ['system' => $foreign]))
        ->assertNotFound();
});
