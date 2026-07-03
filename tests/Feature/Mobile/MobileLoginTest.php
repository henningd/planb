<?php

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function mobileUserWithCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('a valid code logs in and returns a mandant-scoped token', function () {
    [$user, $company] = mobileUserWithCompany();
    $issued = MobileAccessCode::issue($user, $company);

    $response = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'code' => $issued['code'],
    ])
        ->assertOk()
        ->assertJsonPath('company.id', $company->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email'], 'company' => ['id', 'name']]);

    // Code ist verbraucht.
    expect($issued['model']->fresh()->consumed_at)->not->toBeNull();

    // Ausgestellter Token ist auf die Firma gescoped und trägt den mobile-Scope.
    $token = ApiToken::findActiveByPlainToken($response->json('token'));
    expect($token)->not->toBeNull()
        ->and($token->company_id)->toBe($company->id)
        ->and($token->hasScope('mobile'))->toBeTrue();
});

test('the code input is normalised (case-insensitive, ignores spaces)', function () {
    [$user, $company] = mobileUserWithCompany();
    $issued = MobileAccessCode::issue($user, $company);

    $this->postJson('/api/mobile/login', [
        'email' => strtoupper($user->email),
        'code' => strtolower(substr($issued['code'], 0, 4).' '.substr($issued['code'], 4)),
    ])->assertOk();
});

test('an unknown or wrong code is rejected', function () {
    [$user] = mobileUserWithCompany();

    $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'code' => 'WRONGCOD',
    ])->assertStatus(401);
});

test('an expired code is rejected', function () {
    [$user, $company] = mobileUserWithCompany();
    $issued = MobileAccessCode::issue($user, $company);
    $issued['model']->forceFill(['expires_at' => Carbon::now()->subMinute()])->save();

    $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'code' => $issued['code'],
    ])->assertStatus(401);
});

test('a code can only be redeemed once', function () {
    [$user, $company] = mobileUserWithCompany();
    $issued = MobileAccessCode::issue($user, $company);

    $this->postJson('/api/mobile/login', ['email' => $user->email, 'code' => $issued['code']])->assertOk();
    $this->postJson('/api/mobile/login', ['email' => $user->email, 'code' => $issued['code']])->assertStatus(401);
});

test('the app key is enforced when configured', function () {
    config(['services.mobile.app_key' => 'geheim']);
    [$user, $company] = mobileUserWithCompany();
    $issued = MobileAccessCode::issue($user, $company);

    // Falscher/fehlender App-Key → 401.
    $this->postJson('/api/mobile/login', ['email' => $user->email, 'code' => $issued['code']])
        ->assertStatus(401)
        ->assertJsonPath('error', 'invalid_app_key');

    // Korrekter App-Key → OK.
    $this->withHeader('X-App-Key', 'geheim')
        ->postJson('/api/mobile/login', ['email' => $user->email, 'code' => $issued['code']])
        ->assertOk();
});

test('logout revokes the bearer token', function () {
    [$user, $company] = mobileUserWithCompany();
    $issued = MobileAccessCode::issue($user, $company);
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'code' => $issued['code']])->json('token');

    $this->withToken($token)->postJson('/api/mobile/logout')->assertNoContent();

    expect(ApiToken::findActiveByPlainToken($token))->toBeNull();
});

test('protected mobile routes require a bearer token', function () {
    $this->postJson('/api/mobile/devices/register', ['fcm_token' => 'x'])->assertStatus(401);
});

test('device registration succeeds with a valid token', function () {
    [$user, $company] = mobileUserWithCompany();
    $issued = MobileAccessCode::issue($user, $company);
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'code' => $issued['code']])->json('token');

    $this->withToken($token)
        ->postJson('/api/mobile/devices/register', ['fcm_token' => 'fcm-123', 'platform' => 'ios'])
        ->assertNoContent();
});
