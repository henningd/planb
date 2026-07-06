<?php

use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\MobileDevice;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use App\Models\User;
use App\Support\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: string}
 */
function ackSession(): array
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

function ackRun(Company $company, User $user): ScenarioRun
{
    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);

    return ScenarioRun::factory()->for($company)->create([
        'scenario_id' => $scenario->id,
        'started_by_user_id' => $user->id,
        'title' => 'Stromausfall · Ernstfall',
        'mode' => 'real',
        'started_at' => now(),
    ]);
}

test('a first acknowledgement is stored and returned', function () {
    [$user, $company, $token] = ackSession();
    $run = ackRun($company, $user);

    $response = test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])
        ->assertOk()
        ->assertJsonPath('data.run_id', $run->id)
        ->assertJsonPath('data.status', 'seen');

    expect($response->json('data.acknowledged_at'))->toBeString();

    $ack = ScenarioRunAcknowledgement::firstOrFail();
    expect($ack->scenario_run_id)->toBe($run->id)
        ->and($ack->user_id)->toBe($user->id)
        ->and($ack->status)->toBe('seen')
        ->and($ack->acknowledged_at)->not->toBeNull();
});

test('taking_over upgrades an existing seen acknowledgement without duplicating it', function () {
    [$user, $company, $token] = ackSession();
    $run = ackRun($company, $user);

    test()->withToken($token)->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])->assertOk();
    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'taking_over'])
        ->assertOk()
        ->assertJsonPath('data.status', 'taking_over');

    expect(ScenarioRunAcknowledgement::count())->toBe(1)
        ->and(ScenarioRunAcknowledgement::firstOrFail()->status)->toBe('taking_over');
});

test('a later seen never downgrades a taking_over acknowledgement', function () {
    [$user, $company, $token] = ackSession();
    $run = ackRun($company, $user);

    test()->withToken($token)->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'taking_over'])->assertOk();

    // Die Antwort spiegelt den gespeicherten (nicht den gesendeten) Stand wider.
    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])
        ->assertOk()
        ->assertJsonPath('data.status', 'taking_over');

    expect(ScenarioRunAcknowledgement::count())->toBe(1)
        ->and(ScenarioRunAcknowledgement::firstOrFail()->status)->toBe('taking_over');
});

test('repeating the same status keeps a single acknowledgement per user and run', function () {
    [$user, $company, $token] = ackSession();
    $run = ackRun($company, $user);

    test()->withToken($token)->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])->assertOk();
    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])
        ->assertOk()
        ->assertJsonPath('data.status', 'seen');

    expect(ScenarioRunAcknowledgement::count())->toBe(1);
});

test('an invalid status is rejected', function () {
    [$user, $company, $token] = ackSession();
    $run = ackRun($company, $user);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'ignored'])
        ->assertUnprocessable();

    expect(ScenarioRunAcknowledgement::count())->toBe(0);
});

test('a run of another company cannot be acknowledged', function () {
    [$user, $company, $token] = ackSession();
    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $foreignRun = ackRun($otherCompany, $otherUser->fresh());

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$foreignRun->id}/ack", ['status' => 'seen'])
        ->assertNotFound();

    expect(ScenarioRunAcknowledgement::count())->toBe(0);
});

test('an acknowledgement alerts the other devices to re-sync', function () {
    [$user, $company, $token] = ackSession();
    MobileDevice::create(['fcm_token' => 'tok-ack', 'user_id' => $user->id, 'company_id' => $company->id]);
    $run = ackRun($company, $user);

    $sender = Mockery::spy(PushSender::class);
    $sender->shouldReceive('send')->andReturn([]);
    app()->instance(PushSender::class, $sender);

    test()->withToken($token)->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])->assertOk();

    $sender->shouldHaveReceived('send')->withArgs(
        fn ($tokens, $data) => in_array('tok-ack', $tokens, true) && ($data['type'] ?? null) === 'sync',
    );
});

test('the sync bundle exposes acknowledgements per active run', function () {
    [$user, $company, $token] = ackSession();
    $run = ackRun($company, $user);

    $colleague = User::factory()->create(['name' => 'Erika Beispiel']);
    ScenarioRunAcknowledgement::create([
        'scenario_run_id' => $run->id,
        'user_id' => $colleague->id,
        'status' => 'taking_over',
        'acknowledged_at' => now(),
    ]);

    test()->withToken($token)->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])->assertOk();

    $data = test()->withToken($token)->getJson('/api/mobile/sync')->json('data');
    $bundleRun = $data['active_runs'][0];

    expect($bundleRun['is_drill'])->toBeFalse()
        ->and($bundleRun['acknowledgements'])->toHaveCount(2);

    $byPerson = collect($bundleRun['acknowledgements'])->keyBy('person');
    expect($byPerson[$user->name]['status'])->toBe('seen')
        ->and($byPerson['Erika Beispiel']['status'])->toBe('taking_over')
        ->and($byPerson[$user->name]['acknowledged_at'])->toBeString()
        // `user_id` im selben Format wie `user.id` im Login-Response, damit die
        // App den eigenen Eintrag eindeutig (nicht per Namensvergleich) zuordnet.
        ->and($byPerson[$user->name]['user_id'])->toBe((string) $user->id)
        ->and($byPerson['Erika Beispiel']['user_id'])->toBe((string) $colleague->id);
});

test('a new acknowledgement changes the sync version fingerprint', function () {
    [$user, $company, $token] = ackSession();
    $run = ackRun($company, $user);

    $version = test()->withToken($token)->getJson('/api/mobile/sync')->json('data.version');

    test()->withToken($token)->postJson("/api/mobile/runs/{$run->id}/ack", ['status' => 'seen'])->assertOk();

    $res = test()->withToken($token)
        ->getJson('/api/mobile/sync?version='.urlencode($version))
        ->assertOk()
        ->assertJsonPath('data.unchanged', false);

    expect($res->json('data.version'))->not->toBe($version);
});
