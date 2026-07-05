<?php

use App\Jobs\SendCompanyPush;
use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\MobileDevice;
use App\Models\Scenario;
use App\Models\User;
use App\Support\Push\FcmPushSender;
use App\Support\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: string}
 */
function queuePushSession(): array
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

test('triggering a real incident queues the push instead of sending inline', function () {
    Queue::fake();

    [$user, $company, $token] = queuePushSession();
    MobileDevice::create(['fcm_token' => 'tok-queue', 'user_id' => $user->id, 'company_id' => $company->id]);

    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);
    $scenario->steps()->create(['sort' => 1, 'title' => 'Notstrom prüfen', 'responsible' => 'IT']);

    test()->withToken($token)->postJson('/api/mobile/incidents', [
        'scenario_id' => $scenario->id,
    ])->assertCreated();

    Queue::assertPushed(SendCompanyPush::class);
});

test('the job resolves company tokens, sends and prunes UNREGISTERED tokens', function () {
    [$user, $company] = queuePushSession();
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

    (new SendCompanyPush($company->id, ['type' => 'incident'], 'Notfall gemeldet', 'Stromausfall'))
        ->handle($sender);

    expect(MobileDevice::where('fcm_token', 'tok-dead')->exists())->toBeFalse()
        ->and(MobileDevice::where('fcm_token', 'tok-live')->exists())->toBeTrue();
});

test('a 401 refreshes the OAuth token and retries the send once', function () {
    $keyResource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyResource, $privateKeyPem);

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fresh-token']),
        'fcm.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['status' => 'UNAUTHENTICATED']], 401)
            ->push(['name' => 'ok'], 200),
    ]);

    $sender = new FcmPushSender('demo-project', [
        'client_email' => 'svc@example.com',
        'private_key' => $privateKeyPem,
    ]);

    $dead = $sender->send(['tok-1'], ['type' => 'sync']);

    // 401 → Token neu geholt → erneuter Versand 200 → Token nicht als tot markiert.
    expect($dead)->toBe([]);
    // 2× OAuth (initial + Refresh) + 2× FCM (401 + Retry).
    Http::assertSentCount(4);
});

test('the job exits early and never calls the sender when the company has no devices', function () {
    [$user, $company] = queuePushSession();

    $sender = Mockery::spy(PushSender::class);

    (new SendCompanyPush($company->id, ['type' => 'sync']))->handle($sender);

    $sender->shouldNotHaveReceived('send');
});
