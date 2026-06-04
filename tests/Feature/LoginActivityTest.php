<?php

use App\Models\AuditLogEntry;
use App\Models\AuthActivity;
use App\Models\Company;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Settings\CompanySetting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function userWithCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

function authActivities(): Collection
{
    return AuthActivity::withoutGlobalScope(CurrentCompanyScope::class)->get();
}

test('a successful login is recorded for the current company', function () {
    [$user, $company] = userWithCompany();

    event(new Login('web', $user, false));

    $entry = authActivities()->first();

    expect($entry)->not->toBeNull()
        ->and($entry->event)->toBe('login')
        ->and($entry->user_id)->toBe($user->id)
        ->and($entry->company_id)->toBe($company->id)
        ->and($entry->email)->toBe($user->email);
});

test('a logout is recorded', function () {
    [$user] = userWithCompany();

    event(new Logout('web', $user));

    expect(authActivities()->pluck('event')->all())->toBe(['logout']);
});

test('a failed login for a known user is recorded', function () {
    [$user] = userWithCompany();

    event(new Failed('web', $user, ['email' => $user->email, 'password' => 'wrong']));

    $entry = authActivities()->first();

    expect($entry)->not->toBeNull()
        ->and($entry->event)->toBe('failed')
        ->and($entry->user_id)->toBe($user->id);
});

test('a failed login for an unknown email is not recorded', function () {
    userWithCompany();

    event(new Failed('web', null, ['email' => 'nobody@example.test', 'password' => 'wrong']));

    expect(authActivities())->toHaveCount(0);
});

test('a login without a company is not recorded', function () {
    $user = User::factory()->create();

    event(new Login('web', $user->fresh(), false));

    expect(authActivities())->toHaveCount(0);
});

test('auth activity is scoped to the current company', function () {
    [$userA, $companyA] = userWithCompany();
    [$userB] = userWithCompany();

    event(new Login('web', $userA, false));
    event(new Login('web', $userB, false));

    $this->actingAs($userA);

    expect(AuthActivity::pluck('company_id')->unique()->all())->toBe([$companyA->id]);
});

test('the login activity page renders entries', function () {
    [$user] = userWithCompany();
    event(new Login('web', $user, false));

    $this->actingAs($user);

    $this->get(route('login-activity.index'))
        ->assertOk()
        ->assertSee('Anmeldungen')
        ->assertSee('Angemeldet')
        ->assertSee($user->name);
});

test('cleanup deletes entries older than the retention cap per tenant', function () {
    [$user, $company] = userWithCompany();
    $this->actingAs($user);

    // Even with a retention far above the cap, entries beyond 360 days are pruned.
    CompanySetting::for($company)->set('audit_retention_days', 3650);

    $old = AuthActivity::create(['company_id' => $company->id, 'user_id' => $user->id, 'event' => 'login']);
    $recent = AuthActivity::create(['company_id' => $company->id, 'user_id' => $user->id, 'event' => 'login']);

    AuthActivity::withoutGlobalScope(CurrentCompanyScope::class)
        ->whereKey($old->id)->update(['created_at' => now()->subDays(400)]);

    // An aged change-log entry must be pruned by the same command.
    $system = System::create(['name' => 'ERP', 'category' => 'basisbetrieb']);
    AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)
        ->update(['created_at' => now()->subDays(400)]);

    $this->artisan('app:cleanup-audit-log')->assertSuccessful();

    $remaining = authActivities();
    expect($remaining->pluck('id')->all())->toBe([$recent->id])
        ->and(AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});
