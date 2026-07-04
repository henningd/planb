<?php

use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\MobileAccessCode;
use App\Models\MobileDevice;
use App\Models\User;
use App\Support\Push\PushNotifier;
use App\Support\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: string}
 */
function pushSession(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $user = $user->fresh();

    $issued = MobileAccessCode::issue($user, $company);
    $token = test()->postJson('/api/mobile/login', [
        'email' => $user->email,
        'code' => $issued['code'],
    ])->json('token');

    return [$user, $company, $token];
}

test('registering a device stores it for the user and company', function () {
    [$user, $company, $token] = pushSession();

    test()->withToken($token)->postJson('/api/mobile/devices/register', [
        'fcm_token' => 'fcm-abc',
        'platform' => 'ios',
        'app_version' => '1.0',
    ])->assertNoContent();

    $device = MobileDevice::first();

    expect($device)->not->toBeNull()
        ->and($device->fcm_token)->toBe('fcm-abc')
        ->and($device->user_id)->toBe($user->id)
        ->and($device->company_id)->toBe($company->id)
        ->and($device->platform)->toBe('ios');
});

test('re-registering the same token updates instead of duplicating', function () {
    [$user, $company, $token] = pushSession();

    foreach (['1.0', '1.1'] as $version) {
        test()->withToken($token)->postJson('/api/mobile/devices/register', [
            'fcm_token' => 'fcm-x',
            'app_version' => $version,
        ])->assertNoContent();
    }

    expect(MobileDevice::count())->toBe(1)
        ->and(MobileDevice::first()->app_version)->toBe('1.1');
});

test('unregistering removes the device', function () {
    [$user, $company, $token] = pushSession();

    test()->withToken($token)->postJson('/api/mobile/devices/register', ['fcm_token' => 'fcm-y'])->assertNoContent();
    test()->withToken($token)->postJson('/api/mobile/devices/unregister', ['fcm_token' => 'fcm-y'])->assertNoContent();

    expect(MobileDevice::count())->toBe(0);
});

test('the notifier sends a sync signal to the company devices', function () {
    [$user, $company] = pushSession();
    MobileDevice::create(['fcm_token' => 'tok-1', 'user_id' => $user->id, 'company_id' => $company->id]);

    $sender = Mockery::spy(PushSender::class);
    app()->instance(PushSender::class, $sender);

    app(PushNotifier::class)->syncCompany($company->fresh());

    $sender->shouldHaveReceived('send')->once()->withArgs(
        fn ($tokens, $data) => $tokens === ['tok-1'] && $data === ['type' => 'sync'],
    );
});

test('approving a handbook version pushes a sync signal to the devices', function () {
    [$user, $company] = pushSession();
    MobileDevice::create(['fcm_token' => 'tok-2', 'user_id' => $user->id, 'company_id' => $company->id]);

    $sender = Mockery::spy(PushSender::class);
    app()->instance(PushSender::class, $sender);

    HandbookVersion::factory()->for($company)->create([
        'version' => '1.0',
        'approved_at' => '2026-07-05',
    ]);

    $sender->shouldHaveReceived('send')->withArgs(
        fn ($tokens, $data) => in_array('tok-2', $tokens, true) && ($data['type'] ?? null) === 'sync',
    );
});
