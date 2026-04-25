<?php

use App\Models\Company;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use App\Support\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('QrCode support class returns an inline svg for the given string', function () {
    $svg = QrCode::svg('https://example.test/abc', 120);

    expect($svg)->toStartWith('<svg');
});

test('system sticker page renders with QR and system info', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $priority = $company->systemPriorities()->where('sort', 1)->first();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Serverraum-USV',
        'category' => 'basisbetrieb',
        'system_priority_id' => $priority->id,
        'rto_minutes' => 60,
        'rpo_minutes' => 15,
    ]);

    $provider = ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'IT-Notdienst GmbH',
        'hotline' => '0800 999 111',
    ]);
    AssignmentSync::attach($system, $system->serviceProviders(), $provider->id);

    $this->actingAs($user->fresh())
        ->get(route('systems.sticker', ['system' => $system->id]))
        ->assertOk()
        ->assertSee('Notfall-Aushang')
        ->assertSee('Serverraum-USV')
        ->assertSee('IT-Notdienst GmbH')
        ->assertSee('0800 999 111')
        ->assertSee('1 Stunde')
        ->assertSee('<svg', escape: false);
});

test('system sticker route requires team membership', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Fremd',
        'category' => 'basisbetrieb',
    ]);

    $stranger = User::factory()->create();

    $this->actingAs($stranger->fresh())
        ->get(route('systems.sticker', ['current_team' => $owner->currentTeam->slug, 'system' => $system->id]))
        ->assertForbidden();
});
