<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\SystemRoleProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new company auto-provisions one system role per CrisisRole', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $systemRoles = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->whereNotNull('system_key')
        ->get();

    expect($systemRoles)->toHaveCount(count(CrisisRole::cases()));

    foreach (CrisisRole::cases() as $case) {
        $match = $systemRoles->firstWhere('system_key', $case->value);
        expect($match)->not->toBeNull();
        expect($match->name)->toBe($case->label());
        expect($match->isSystem())->toBeTrue();
    }
});

test('SystemRoleProvisioner is idempotent', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $countBefore = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->whereNotNull('system_key')
        ->count();

    SystemRoleProvisioner::ensureFor($company);
    SystemRoleProvisioner::ensureFor($company);

    $countAfter = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->whereNotNull('system_key')
        ->count();

    expect($countAfter)->toBe($countBefore);
});

test('system roles cannot be deleted via the UI', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $systemRole = Role::where('company_id', $company->id)
        ->whereNotNull('system_key')
        ->first();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->set('deletingId', $systemRole->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Role::find($systemRole->id))->not->toBeNull();
});

test('non-system roles can still be deleted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Eigene Rolle',
        'description' => 'Mandanten-spezifisch',
        'sort' => 99,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::roles.index')
        ->set('deletingId', $role->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Role::withoutGlobalScope(CurrentCompanyScope::class)->find($role->id))->toBeNull();
});

test('system roles are mandant-isolated', function () {
    $userA = User::factory()->create();
    $companyA = Company::factory()->for($userA->currentTeam)->create();

    $userB = User::factory()->create();
    $companyB = Company::factory()->for($userB->currentTeam)->create();

    $aRoles = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $companyA->id)
        ->whereNotNull('system_key')
        ->pluck('id');

    $bRoles = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $companyB->id)
        ->whereNotNull('system_key')
        ->pluck('id');

    expect($aRoles->intersect($bRoles))->toBeEmpty();
});
