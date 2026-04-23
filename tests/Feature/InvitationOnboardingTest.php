<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('full flow: guest invitation → register → auto-accept lands on invited team dashboard', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Invited Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'newbie@example.com',
        'role' => TeamRole::Member,
        'invited_by' => $owner->id,
    ]);

    // 1. Guest visits the accept URL → sees invite landing page, stores intended URL
    $this->get(route('invitations.accept', $invitation->code))
        ->assertOk()
        ->assertSee('Einladung ins Team')
        ->assertSee('Invited Team')
        ->assertSee('Anmelden, um anzunehmen')
        ->assertSee('Jetzt registrieren');

    expect(session('url.intended'))->toBe(route('invitations.accept', $invitation->code));

    // 2. Guest registers a new account (Fortify endpoint)
    $this->post(route('register.store'), [
        'name' => 'Newbie',
        'email' => 'newbie@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('invitations.accept', $invitation->code));

    $newUser = User::where('email', 'newbie@example.com')->firstOrFail();
    expect(Hash::check('secret-password', $newUser->password))->toBeTrue();
    expect($newUser->teams()->where('is_personal', true)->exists())->toBeTrue();

    // 3. Following the redirect (still logged in as the new user) triggers auto-accept
    Livewire::actingAs($newUser)
        ->test('pages::teams.accept-invitation', ['invitation' => $invitation->fresh()])
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

    // 4. Verify end state: membership, accepted timestamp, current team switched
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($newUser->fresh()->belongsToTeam($team))->toBeTrue();
    expect($newUser->fresh()->current_team_id)->toBe($team->id);
});

test('existing user flow: login after clicking invitation link lands on invited team', function () {
    $owner = User::factory()->create();
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'password' => Hash::make('my-password'),
    ]);

    $team = Team::factory()->create(['name' => 'Join Me']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'existing@example.com',
        'role' => TeamRole::Admin,
        'invited_by' => $owner->id,
    ]);

    // 1. As guest, visit → intended URL stored
    $this->get(route('invitations.accept', $invitation->code))->assertOk();
    expect(session('url.intended'))->toBe(route('invitations.accept', $invitation->code));

    // 2. Login via Fortify → redirected to intended URL
    $this->post(route('login.store'), [
        'email' => 'existing@example.com',
        'password' => 'my-password',
    ])->assertRedirect(route('invitations.accept', $invitation->code));

    // 3. Mounting the accept page now auto-accepts the invitation
    Livewire::actingAs($existingUser->fresh())
        ->test('pages::teams.accept-invitation', ['invitation' => $invitation->fresh()])
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

    expect($existingUser->fresh()->belongsToTeam($team))->toBeTrue();
    expect($existingUser->fresh()->teamRole($team))->toBe(TeamRole::Admin);
});

test('logout from accept page clears wrong-user state and returns to invitation', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create(['email' => 'someoneelse@example.com']);
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    Livewire::actingAs($otherUser)
        ->test('pages::teams.accept-invitation', ['invitation' => $invitation])
        ->assertSet('state', 'wrong_user')
        ->call('logout')
        ->assertRedirect(route('invitations.accept', ['invitation' => $invitation->code]));

    expect(auth()->check())->toBeFalse();
});
