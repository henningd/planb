<?php

use App\Enums\TeamRole;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AuditLogFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Builds a team with a company and an owner, acting as that owner.
 *
 * @return array{0: Team, 1: User, 2: Company}
 */
function auditTeam(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $company = Company::factory()->for($team)->create();
    $owner->forceFill(['current_team_id' => $team->id])->save();

    test()->actingAs($owner->fresh());

    return [$team, $owner, $company];
}

/**
 * @return Builder<AuditLogEntry>
 */
function auditEntries(string $action)
{
    return AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)->where('action', $action);
}

test('inviting a member logs a member.invited entry', function () {
    Notification::fake();
    [$team, $owner, $company] = auditTeam();

    Livewire::test('pages::teams.invite-member-modal', ['team' => $team])
        ->set('inviteEmail', 'invited@example.com')
        ->set('inviteRole', TeamRole::Member->value)
        ->call('createInvitation')
        ->assertHasNoErrors();

    $entry = auditEntries('member.invited')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->company_id)->toBe($company->id)
        ->and($entry->entity_type)->toBe('User')
        ->and($entry->entity_label)->toBe('invited@example.com')
        ->and($entry->user_id)->toBe($owner->id)
        ->and($entry->changes['role'])->toBe(TeamRole::Member->value);
});

test('accepting an invitation logs a member.joined entry', function () {
    [$team, $owner, $company] = auditTeam();

    $invited = User::factory()->create(['email' => 'joiner@example.com']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'joiner@example.com',
        'role' => TeamRole::Member,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invited->fresh());

    // mount() auto-accepts when the invitation is ready for the logged-in user.
    Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation]);

    $entry = auditEntries('member.joined')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->company_id)->toBe($company->id)
        ->and($entry->entity_label)->toBe($invited->name)
        ->and($entry->user_id)->toBe($invited->id);
});

test('changing a member role logs a diff', function () {
    [$team, $owner, $company] = auditTeam();

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    Livewire::test('pages::teams.edit', ['team' => $team])
        ->call('updateMember', $member->id, TeamRole::Admin->value)
        ->assertHasNoErrors();

    $entry = auditEntries('member.role_changed')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->company_id)->toBe($company->id)
        ->and($entry->changes['role']['old'])->toBe(TeamRole::Member->value)
        ->and($entry->changes['role']['new'])->toBe(TeamRole::Admin->value);
});

test('removing a member logs a member.removed entry', function () {
    [$team, $owner, $company] = auditTeam();

    $member = User::factory()->create(['name' => 'Weg Damit']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    Livewire::test('pages::teams.remove-member-modal', [
        'team' => $team,
        'memberId' => $member->id,
        'memberName' => $member->name,
    ])->call('removeMember')->assertHasNoErrors();

    $entry = auditEntries('member.removed')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->company_id)->toBe($company->id)
        ->and($entry->entity_label)->toBe('Weg Damit');
});

test('disabling a member logs a member.disabled entry', function () {
    [$team, $owner, $company] = auditTeam();

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    Livewire::test('pages::teams.disable-member-modal', [
        'team' => $team,
        'memberId' => $member->id,
        'memberName' => $member->name,
    ])->call('disableMember')->assertHasNoErrors();

    expect(auditEntries('member.disabled')->where('company_id', $company->id)->exists())->toBeTrue();
});

test('creating a company logs a Company created entry', function () {
    [$team, $owner, $company] = auditTeam();

    $entry = AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('entity_type', 'Company')
        ->where('action', 'created')
        ->where('company_id', $company->id)
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->entity_label)->toBe($company->name);
});

test('enabling and disabling 2FA logs security events', function () {
    [$team, $owner, $company] = auditTeam();

    event(new TwoFactorAuthenticationConfirmed($owner->fresh()));
    event(new TwoFactorAuthenticationDisabled($owner->fresh()));

    expect(auditEntries('security.2fa_enabled')->where('company_id', $company->id)->exists())->toBeTrue()
        ->and(auditEntries('security.2fa_disabled')->where('company_id', $company->id)->exists())->toBeTrue();
});

test('the account filter returns membership and security events', function () {
    Notification::fake();
    [$team, $owner, $company] = auditTeam();

    Livewire::test('pages::teams.invite-member-modal', ['team' => $team])
        ->set('inviteEmail', 'invited@example.com')
        ->set('inviteRole', TeamRole::Member->value)
        ->call('createInvitation');

    $actions = AuditLogFilter::build(['action' => 'account'])
        ->where('company_id', $company->id)
        ->pluck('action')
        ->all();

    expect($actions)->toContain('member.invited')
        ->and($actions)->toContain('created'); // Company created
});
