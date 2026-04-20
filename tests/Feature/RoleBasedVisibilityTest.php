<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Creates a second user as "Member" on the owner's team and returns them.
 */
function memberOf(User $owner): User
{
    $team = $owner->currentTeam;

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    return $member->fresh();
}

test('isCurrentTeamAdmin is true for owner and false for member', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    $member = memberOf($owner);

    expect($owner->fresh()->isCurrentTeamAdmin())->toBeTrue()
        ->and($member->isCurrentTeamAdmin())->toBeFalse();
});

test('member gets 403 on insurance-policies, handbook-shares, audit-log and communication-templates', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    $member = memberOf($owner);

    foreach ([
        'insurance-policies.index',
        'handbook-shares.index',
        'audit-log.index',
        'communication-templates.index',
    ] as $routeName) {
        $this->actingAs($member)
            ->get(route($routeName))
            ->assertForbidden();
    }
});

test('owner can access all admin sections', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    foreach ([
        'insurance-policies.index',
        'handbook-shares.index',
        'audit-log.index',
        'communication-templates.index',
    ] as $routeName) {
        $this->actingAs($owner->fresh())
            ->get(route($routeName))
            ->assertOk();
    }
});

test('sidebar hides admin sections for member users', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    $member = memberOf($owner);

    $response = $this->actingAs($member)->get(route('dashboard'));

    $response->assertOk()
        ->assertDontSee('Versicherungen')
        ->assertDontSee('Freigabelinks')
        ->assertDontSee('Aktivitäten')
        ->assertDontSee('Vorlagen');
});

test('sidebar shows admin sections for owner', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    $response = $this->actingAs($owner->fresh())->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Versicherungen')
        ->assertSee('Freigabelinks')
        ->assertSee('Aktivitäten');
});

test('member can still access shared domain data like contacts and scenarios', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    $member = memberOf($owner);

    foreach ([
        'contacts.index',
        'scenarios.index',
        'systems.index',
        'employees.index',
        'emergency-levels.index',
    ] as $routeName) {
        $this->actingAs($member)
            ->get(route($routeName))
            ->assertOk();
    }
});
