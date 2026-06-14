<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Tests\TestCase;

/**
 * Provisions a team with a company and a member at the given role, acting as
 * that member. Returns [TestCase, Team].
 *
 * @return array{0: TestCase, 1: Team}
 */
function provisionTeamActor(TeamRole $role): array
{
    $team = Team::factory()->create();
    Company::factory()->for($team)->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [test()->actingAs($user->fresh()), $team];
}

// --- Enum hierarchy & assignability ---------------------------------------

test('the consultant role sits between member and admin in the hierarchy', function () {
    expect(TeamRole::Consultant->label())->toBe('Berater')
        ->and(TeamRole::Consultant->isAtLeast(TeamRole::Member))->toBeTrue()
        ->and(TeamRole::Consultant->isAtLeast(TeamRole::Admin))->toBeFalse()
        ->and(TeamRole::Admin->isAtLeast(TeamRole::Consultant))->toBeTrue();
});

test('the consultant role can be assigned when inviting members', function () {
    $values = collect(TeamRole::assignable())->pluck('value');

    expect($values)->toContain('consultant')
        ->and($values)->not->toContain('owner');
});

// --- Content sections a consultant MAY maintain ---------------------------

test('a consultant can reach the content sections they maintain', function (string $routeName) {
    [$actor, $team] = provisionTeamActor(TeamRole::Consultant);

    $actor->get(route($routeName, ['current_team' => $team->slug]))
        ->assertSuccessful();
})->with([
    'risks.index',
    'insurance-policies.index',
    'communication-templates.index',
]);

test('a plain member cannot reach the consultant content sections', function (string $routeName) {
    [$actor, $team] = provisionTeamActor(TeamRole::Member);

    $actor->get(route($routeName, ['current_team' => $team->slug]))
        ->assertForbidden();
})->with([
    'risks.index',
    'insurance-policies.index',
    'communication-templates.index',
]);

// --- Governance sections a consultant may NOT touch -----------------------

test('a consultant cannot reach admin-only governance sections', function (string $routeName) {
    [$actor, $team] = provisionTeamActor(TeamRole::Consultant);

    $actor->get(route($routeName, ['current_team' => $team->slug]))
        ->assertForbidden();
})->with([
    'audit-log.index',
    'system-settings.index',
    'handbook-shares.index',
]);

test('an admin can reach the governance sections', function (string $routeName) {
    [$actor, $team] = provisionTeamActor(TeamRole::Admin);

    $actor->get(route($routeName, ['current_team' => $team->slug]))
        ->assertSuccessful();
})->with([
    'audit-log.index',
    'system-settings.index',
    'handbook-shares.index',
]);
