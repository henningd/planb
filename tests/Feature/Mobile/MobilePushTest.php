<?php

use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\MobileAccessCode;
use App\Models\MobileDevice;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Push\FcmPushSender;
use App\Support\Push\PushNotifier;
use App\Support\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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
    $sender->shouldReceive('send')->andReturn([]);
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
    $sender->shouldReceive('send')->andReturn([]);
    app()->instance(PushSender::class, $sender);

    HandbookVersion::factory()->for($company)->create([
        'version' => '1.0',
        'approved_at' => '2026-07-05',
    ]);

    $sender->shouldHaveReceived('send')->withArgs(
        fn ($tokens, $data) => in_array('tok-2', $tokens, true) && ($data['type'] ?? null) === 'sync',
    );
});

test('triggering an incident from the app starts a run and alarms the other devices', function () {
    [$user, $company, $token] = pushSession();
    MobileDevice::create(['fcm_token' => 'tok-3', 'user_id' => $user->id, 'company_id' => $company->id]);

    // Gerät eines anderen Nutzers derselben Firma – dieses soll den Alarm erhalten.
    $colleague = User::factory()->create();
    MobileDevice::create(['fcm_token' => 'tok-colleague', 'user_id' => $colleague->id, 'company_id' => $company->id]);

    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);
    $scenario->steps()->create(['sort' => 1, 'title' => 'Notstrom prüfen', 'responsible' => 'IT']);

    $sender = Mockery::spy(PushSender::class);
    $sender->shouldReceive('send')->andReturn([]);
    app()->instance(PushSender::class, $sender);

    $response = test()->withToken($token)->postJson('/api/mobile/incidents', [
        'scenario_id' => $scenario->id,
    ])->assertCreated();

    $run = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->firstOrFail();

    expect($run->scenario_id)->toBe($scenario->id)
        ->and($run->company_id)->toBe($company->id)
        ->and($run->started_by_user_id)->toBe($user->id)
        ->and($run->steps()->count())->toBe(1)
        ->and($response->json('run_id'))->toBe($run->id);

    // Das Gerät des Auslösers wird ausgeschlossen, das des Kollegen alarmiert.
    $sender->shouldHaveReceived('send')->withArgs(
        fn ($tokens, $data) => ($data['type'] ?? null) === 'incident'
            && in_array('tok-colleague', $tokens, true)
            && ! in_array('tok-3', $tokens, true),
    );
});

test('a drill run does not alarm the devices', function () {
    [$user, $company, $token] = pushSession();
    MobileDevice::create(['fcm_token' => 'tok-4', 'user_id' => $user->id, 'company_id' => $company->id]);

    $scenario = Scenario::factory()->for($company)->create();

    $sender = Mockery::spy(PushSender::class);
    app()->instance(PushSender::class, $sender);

    test()->withToken($token)->postJson('/api/mobile/incidents', [
        'scenario_id' => $scenario->id,
        'mode' => 'drill',
    ])->assertCreated();

    $sender->shouldNotHaveReceived('send');
});

test('an UNREGISTERED token reported by FCM deletes the device', function () {
    [$user, $company] = pushSession();
    MobileDevice::create(['fcm_token' => 'tok-dead', 'user_id' => $user->id, 'company_id' => $company->id]);
    MobileDevice::create(['fcm_token' => 'tok-live', 'user_id' => $user->id, 'company_id' => $company->id]);

    $keyResource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyResource, $privateKeyPem);

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake-access-token']),
        'fcm.googleapis.com/*' => function ($request) {
            $token = $request->data()['message']['token'] ?? null;

            return $token === 'tok-dead'
                ? Http::response(['error' => ['status' => 'UNREGISTERED']], 404)
                : Http::response(['name' => 'ok']);
        },
    ]);

    $sender = new FcmPushSender('demo-project', [
        'client_email' => 'svc@example.com',
        'private_key' => $privateKeyPem,
    ]);
    app()->instance(PushSender::class, $sender);

    app(PushNotifier::class)->incident($company->fresh(), 'scn-1', 'Stromausfall');

    expect(MobileDevice::where('fcm_token', 'tok-dead')->exists())->toBeFalse()
        ->and(MobileDevice::where('fcm_token', 'tok-live')->exists())->toBeTrue();
});

test('a scenario from another company cannot be triggered', function () {
    [$user, $company, $token] = pushSession();
    $otherScenario = Scenario::factory()
        ->for(Company::factory()->for(User::factory()->create()->currentTeam))
        ->create();

    test()->withToken($token)->postJson('/api/mobile/incidents', [
        'scenario_id' => $otherScenario->id,
    ])->assertNotFound();

    expect(ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});
