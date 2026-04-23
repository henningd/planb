<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('team invitations can be created', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this->actingAs($owner);

    Livewire::test('pages::teams.invite-member-modal', ['team' => $team])
        ->set('inviteEmail', 'invited@example.com')
        ->set('inviteRole', TeamRole::Member->value)
        ->call('createInvitation')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Member->value,
    ]);
});

test('team invitations cannot be created by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $this->actingAs($member);

    Livewire::test('pages::teams.invite-member-modal', ['team' => $team])
        ->set('inviteEmail', 'invited@example.com')
        ->set('inviteRole', TeamRole::Member->value)
        ->call('createInvitation')
        ->assertForbidden();
});

test('team invitations can be cancelled by owner', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::teams.cancel-invitation-modal', ['team' => $team])
        ->set('invitationCode', $invitation->code)
        ->call('cancelInvitation')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('team invitations auto-accept when invited user is logged in', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Member,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser);

    Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation])
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitedUser->fresh()->belongsToTeam($team))->toBeTrue();
    expect($invitedUser->fresh()->current_team_id)->toBe($team->id);
});

test('guests see login/register prompt and invitation is stored as intended url', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation])
        ->assertSet('state', 'guest')
        ->assertSee('Anmelden, um anzunehmen')
        ->assertSee('Jetzt registrieren')
        ->assertNoRedirect();

    expect(session('url.intended'))->toBe(route('invitations.accept', $invitation->code));
});

test('wrong logged-in user sees email mismatch and logout prompt', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create(['email' => 'uninvited@example.com']);
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($otherUser);

    Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation])
        ->assertSet('state', 'wrong_user')
        ->assertSee('Abmelden und Konto wechseln')
        ->assertNoRedirect();

    expect($otherUser->fresh()->belongsToTeam($team))->toBeFalse();
});

test('expired invitation shows expired message and cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser);

    Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation])
        ->assertSet('state', 'expired')
        ->assertSee('Einladung ist abgelaufen')
        ->call('acceptInvitation')
        ->assertForbidden();

    expect($invitedUser->fresh()->belongsToTeam($team))->toBeFalse();
});

test('already accepted invitation shows accepted message', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
        'accepted_at' => now(),
    ]);

    $this->actingAs($invitedUser);

    Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation])
        ->assertSet('state', 'accepted')
        ->assertSee('Diese Einladung wurde bereits angenommen')
        ->call('acceptInvitation')
        ->assertForbidden();
});
