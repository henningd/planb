<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Compliance\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('passes the missing role IDs as ?focus param when deputies are missing', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $check = collect(Catalog::all())->firstWhere('key', 'roles.system.deputies');
    $result = ($check->evaluator)($company->refresh());

    expect($result->action)->not->toBeNull()
        ->and($result->action['route'])->toBe('roles.index')
        ->and($result->action['params']['reason'] ?? null)->toBe('missing_deputy')
        ->and($result->action['params']['focus'] ?? null)->not->toBeNull();

    $focusIds = explode(',', $result->action['params']['focus']);
    expect(count($focusIds))->toBeGreaterThan(0);

    foreach ($focusIds as $id) {
        $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->find($id);
        expect($role)->not->toBeNull()
            ->and($role->system_key)->not->toBeNull('Focus enthält nur System-Rollen');
    }
});

it('highlights focused roles on the index page and shows the compliance banner', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $emergencyRole = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('system_key', CrisisRole::EmergencyOfficer->value)
        ->firstOrFail();

    $this->actingAs($user->fresh())
        ->get(route('roles.index', ['focus' => $emergencyRole->id, 'reason' => 'missing_deputy']))
        ->assertOk()
        ->assertSee('Compliance-Hinweis: Vertretung fehlt')
        ->assertSee($emergencyRole->name)
        ->assertSee('Markierung entfernen')
        // ring-Klasse für Highlight muss am Karten-DOM-Element vorhanden sein:
        ->assertSee('ring-amber-400', false);
});

it('clears focus state when clearFocus is called', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $emergencyRole = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('system_key', CrisisRole::EmergencyOfficer->value)
        ->firstOrFail();

    Livewire\Livewire::actingAs($user->fresh())
        ->withQueryParams(['focus' => $emergencyRole->id, 'reason' => 'missing_deputy'])
        ->test('pages::roles.index')
        ->assertSet('focusedRoleIds', [$emergencyRole->id])
        ->assertSet('focusReason', 'missing_deputy')
        ->call('clearFocus')
        ->assertSet('focusedRoleIds', [])
        ->assertSet('focusReason', '');
});
