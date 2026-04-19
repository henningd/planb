<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Database\Seeders\GlobalScenariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(GlobalScenariosSeeder::class));

test('handbook print view renders with full data', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma GmbH']);

    Contact::factory()->for($company)->create(['name' => 'Erika Mustermann']);

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

    $system->serviceProviders()->attach($provider->id);

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

test('handbook print redirects with 404 when no company exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('handbook.print'))
        ->assertNotFound();
});
