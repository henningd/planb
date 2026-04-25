<?php

use App\Models\Company;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('service providers are tenant-scoped', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $otherCompany = Company::factory()->for(Team::factory())->create();

    ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)
        ->create(['company_id' => $user->currentCompany()->id, 'name' => 'Eigener Partner']);
    ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)
        ->create(['company_id' => $otherCompany->id, 'name' => 'Fremder Partner']);

    $this->actingAs($user->fresh());

    expect(ServiceProvider::pluck('name')->all())->toBe(['Eigener Partner']);
});

test('service providers page renders with systems', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $provider = ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)
        ->create([
            'company_id' => $company->id,
            'name' => 'Muster IT-Dienstleister',
            'hotline' => '0800 1234567',
            'sla' => '24/7',
        ]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
    ]);

    AssignmentSync::attach($system, $system->serviceProviders(), $provider->id);

    $this->actingAs($user->fresh())
        ->get(route('service-providers.index'))
        ->assertOk()
        ->assertSee('Muster IT-Dienstleister')
        ->assertSee('0800 1234567')
        ->assertSee('24/7')
        ->assertSee('Warenwirtschaft');
});
