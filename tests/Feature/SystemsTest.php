<?php

use App\Models\Company;
use App\Models\System;
use App\Models\SystemPriority;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
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

test('systems page renders and groups by category', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $priority = $company->systemPriorities()->where('sort', 1)->first();

    System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
        'system_priority_id' => $priority->id,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('systems.index'))
        ->assertOk()
        ->assertSee('Systeme & Betriebskontinuität')
        ->assertSee('Warenwirtschaft')
        ->assertSee('Geschäftsbetrieb')
        ->assertSee('Basisbetrieb')
        ->assertSee('Unterstützend')
        ->assertSee('Kritisch');
});
