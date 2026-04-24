<?php

use App\Enums\TeamRole;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('owner can deactivate a member immediately', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $this->actingAs($owner);

    Livewire::test('pages::teams.disable-member-modal', ['team' => $team])
        ->set('memberId', $member->id)
        ->set('disabledOn', null)
        ->call('disableMember')
        ->assertHasNoErrors();

    $membership = $team->memberships()->where('user_id', $member->id)->first();

    expect($membership->disabled_at)->not->toBeNull();
    expect($membership->isDisabled())->toBeTrue();
    expect($member->fresh()->activelyBelongsToTeam($team))->toBeFalse();
    expect($member->fresh()->belongsToTeam($team))->toBeTrue();
});

test('owner can schedule a member deactivation in the future', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $this->actingAs($owner);

    $futureDate = now()->addDays(5)->format('Y-m-d');

    Livewire::test('pages::teams.disable-member-modal', ['team' => $team])
        ->set('memberId', $member->id)
        ->set('disabledOn', $futureDate)
        ->call('disableMember')
        ->assertHasNoErrors();

    $membership = $team->memberships()->where('user_id', $member->id)->first();

    expect($membership->disabled_at)->not->toBeNull();
    expect($membership->isDisabled())->toBeFalse();
    expect($membership->isDisableScheduled())->toBeTrue();
    expect($member->fresh()->activelyBelongsToTeam($team))->toBeTrue();
});

test('owner cannot deactivate another owner', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($ownerA, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($ownerB, ['role' => TeamRole::Owner->value]);

    $this->actingAs($ownerA);

    Livewire::test('pages::teams.disable-member-modal', ['team' => $team])
        ->set('memberId', $ownerB->id)
        ->call('disableMember')
        ->assertForbidden();
});

test('non-owners cannot deactivate members', function () {
    $owner = User::factory()->create();
    $regularMember = User::factory()->create();
    $otherMember = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($regularMember, ['role' => TeamRole::Member->value]);
    $team->members()->attach($otherMember, ['role' => TeamRole::Member->value]);

    $this->actingAs($regularMember);

    Livewire::test('pages::teams.disable-member-modal', ['team' => $team])
        ->set('memberId', $otherMember->id)
        ->call('disableMember')
        ->assertForbidden();
});

test('owner can reactivate a deactivated member via the edit page', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, [
        'role' => TeamRole::Member->value,
        'disabled_at' => now()->subDay(),
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::teams.edit', ['team' => $team])
        ->call('reactivateMember', $member->id)
        ->assertHasNoErrors();

    expect($team->memberships()->where('user_id', $member->id)->first()->disabled_at)->toBeNull();
    expect($member->fresh()->activelyBelongsToTeam($team))->toBeTrue();
});

test('deactivated user on their current team gets switched to fallback by middleware', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, [
        'role' => TeamRole::Member->value,
        'disabled_at' => now()->subMinute(),
    ]);

    // UserFactory already attaches a personal team; that becomes the fallback.
    $personal = $member->personalTeam();

    $member->forceFill(['current_team_id' => $team->id])->save();

    $this->actingAs($member);

    $response = $this->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertRedirect(route('dashboard', ['current_team' => $personal->slug]));

    expect($member->fresh()->current_team_id)->toBe($personal->id);
});

test('deactivated user without fallback team is logged out', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    // Remove the auto-created personal team so the disabled team is the only membership.
    $member->teamMemberships()->delete();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, [
        'role' => TeamRole::Member->value,
        'disabled_at' => now()->subMinute(),
    ]);

    $member->forceFill(['current_team_id' => $team->id])->save();

    $this->actingAs($member);

    $this->get(route('dashboard', ['current_team' => $team->slug]));

    expect(auth()->check())->toBeFalse();
});

test('user without activity can be hard-deleted', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $this->actingAs($owner);

    expect($member->hasActivity())->toBeFalse();

    Livewire::test('pages::teams.delete-user-modal', ['team' => $team])
        ->set('memberId', $member->id)
        ->call('deleteUser')
        ->assertHasNoErrors();

    expect(User::find($member->id))->toBeNull();
    expect($team->memberships()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('user with audit activity cannot be hard-deleted', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $company = Company::factory()->for($team)->create();

    AuditLogEntry::create([
        'company_id' => $company->id,
        'user_id' => $member->id,
        'entity_type' => 'Dummy',
        'entity_id' => 1,
        'entity_label' => 'test',
        'action' => 'created',
        'changes' => null,
    ]);

    expect($member->fresh()->hasActivity())->toBeTrue();

    $this->actingAs($owner);

    $response = Livewire::test('pages::teams.delete-user-modal', ['team' => $team])
        ->set('memberId', $member->id)
        ->call('deleteUser');

    $response->assertStatus(422);

    expect(User::find($member->id))->not->toBeNull();
});
