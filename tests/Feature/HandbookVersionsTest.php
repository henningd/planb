<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create a handbook version', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-versions.index')
        ->set('version', '1.0')
        ->set('changed_at', '2026-04-01')
        ->set('change_reason', 'Erstversion')
        ->call('save')
        ->assertHasNoErrors();

    expect(HandbookVersion::count())->toBe(1);
});

test('non-admin cannot reach the route', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($owner->currentTeam);

    $this->actingAs($member->fresh())
        ->get(route('handbook-versions.index'))
        ->assertForbidden();
});

test('current handbook version returns latest approved', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    HandbookVersion::factory()->for($company)->create([
        'version' => '1.0',
        'changed_at' => '2026-01-01',
        'approved_at' => '2026-01-05',
        'approved_by_name' => 'GF',
    ]);
    HandbookVersion::factory()->for($company)->create([
        'version' => '1.1',
        'changed_at' => '2026-04-01',
        'approved_at' => '2026-04-05',
        'approved_by_name' => 'GF',
    ]);
    HandbookVersion::factory()->for($company)->create([
        'version' => '1.2',
        'changed_at' => '2026-04-20',
        'approved_at' => null,
    ]);

    expect($company->currentHandbookVersion()?->version)->toBe('1.1');
});

test('versions are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    HandbookVersion::factory()->for($company)->create(['version' => 'eigen']);
    HandbookVersion::factory()->for($other)->create(['version' => 'fremd']);

    $this->actingAs($user);

    expect(HandbookVersion::pluck('version')->all())->toBe(['eigen']);

    expect(HandbookVersion::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});
