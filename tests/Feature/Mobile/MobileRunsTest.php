<?php

use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\MobileDevice;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use App\Models\User;
use App\Support\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: string}
 */
function runSession(): array
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

function activeRun(Company $company, User $user, bool $ended = false): ScenarioRun
{
    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);

    $run = ScenarioRun::factory()->for($company)->create([
        'scenario_id' => $scenario->id,
        'started_by_user_id' => $user->id,
        'title' => 'Stromausfall · Übung',
        'started_at' => now(),
        'ended_at' => $ended ? now() : null,
        'aborted_at' => null,
    ]);

    $run->steps()->create(['sort' => 1, 'title' => 'Notstrom prüfen', 'responsible' => 'IT']);
    $run->steps()->create(['sort' => 2, 'title' => 'Team informieren', 'responsible' => 'Leitung']);

    return $run;
}

test('the sync bundle exposes active runs with their shared step state', function () {
    [$user, $company, $token] = runSession();
    $run = activeRun($company, $user);
    $run->steps()->first()->forceFill(['checked_at' => now(), 'checked_by_user_id' => $user->id])->save();

    $data = test()->withToken($token)->getJson('/api/mobile/sync')->json('data');

    expect($data['active_runs'])->toHaveCount(1);
    $bundleRun = $data['active_runs'][0];

    expect($bundleRun['id'])->toBe($run->id)
        ->and($bundleRun['title'])->toBe('Stromausfall · Übung')
        ->and($bundleRun['started_by'])->toBe($user->name)
        ->and($bundleRun['steps'])->toHaveCount(2)
        ->and($bundleRun['steps'][0]['checked'])->toBeTrue()
        ->and($bundleRun['steps'][0]['checked_by'])->toBe($user->name)
        ->and($bundleRun['steps'][1]['checked'])->toBeFalse();
});

test('an ended run is not part of the bundle', function () {
    [$user, $company, $token] = runSession();
    activeRun($company, $user, ended: true);

    $data = test()->withToken($token)->getJson('/api/mobile/sync')->json('data');

    expect($data['active_runs'])->toHaveCount(0);
});

test('checking a run step writes it back with the user and alarms other devices', function () {
    [$user, $company, $token] = runSession();
    MobileDevice::create(['fcm_token' => 'tok-run', 'user_id' => $user->id, 'company_id' => $company->id]);
    $run = activeRun($company, $user);
    $step = $run->steps()->orderBy('sort')->first();

    $sender = Mockery::spy(PushSender::class);
    $sender->shouldReceive('send')->andReturn([]);
    app()->instance(PushSender::class, $sender);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/steps/{$step->id}", ['checked' => true])
        ->assertOk()
        ->assertJson(['checked' => true, 'checked_by' => $user->name]);

    $step = ScenarioRunStep::find($step->id);
    expect($step->checked_at)->not->toBeNull()
        ->and($step->checked_by_user_id)->toBe($user->id);

    $sender->shouldHaveReceived('send')->withArgs(
        fn ($tokens, $data) => in_array('tok-run', $tokens, true) && ($data['type'] ?? null) === 'sync',
    );
});

test('a broadcast outage does not fail the step write-back', function () {
    [$user, $company, $token] = runSession();
    $run = activeRun($company, $user);
    $step = $run->steps()->orderBy('sort')->first();

    // Broadcast-Server unerreichbar simulieren (Reverb/Pusher down) → das
    // ShouldBroadcastNow-Event würde werfen; der Schreibvorgang muss trotzdem gelingen.
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher' => [
            'driver' => 'pusher',
            'key' => 'k',
            'secret' => 's',
            'app_id' => '1',
            'options' => ['host' => '127.0.0.1', 'port' => 1, 'scheme' => 'http', 'useTLS' => false],
        ],
    ]);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/steps/{$step->id}", ['checked' => true])
        ->assertOk()
        ->assertJson(['checked' => true]);

    expect(ScenarioRunStep::find($step->id)->checked_at)->not->toBeNull();
});

test('unchecking a run step clears it', function () {
    [$user, $company, $token] = runSession();
    $run = activeRun($company, $user);
    $step = $run->steps()->orderBy('sort')->first();
    $step->forceFill(['checked_at' => now(), 'checked_by_user_id' => $user->id])->save();

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/steps/{$step->id}", ['checked' => false])
        ->assertOk()
        ->assertJson(['checked' => false]);

    expect(ScenarioRunStep::find($step->id)->checked_at)->toBeNull();
});

test('a run from another company cannot be touched', function () {
    [$user, $company, $token] = runSession();
    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $run = activeRun($otherCompany, $otherUser->fresh());
    $step = $run->steps()->first();

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/steps/{$step->id}", ['checked' => true])
        ->assertNotFound();

    expect(ScenarioRunStep::find($step->id)->checked_at)->toBeNull();
});

test('a step of an ended run cannot be toggled', function () {
    [$user, $company, $token] = runSession();
    $run = activeRun($company, $user, ended: true);
    $step = $run->steps()->first();

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/steps/{$step->id}", ['checked' => true])
        ->assertStatus(409);
});

test('completing a run ends it, removes it from the bundle and alarms devices', function () {
    [$user, $company, $token] = runSession();
    MobileDevice::create(['fcm_token' => 'tok-end', 'user_id' => $user->id, 'company_id' => $company->id]);
    // Gerät eines anderen Nutzers – dieses soll die Beendet-Meldung erhalten.
    $colleague = User::factory()->create();
    MobileDevice::create(['fcm_token' => 'tok-end-colleague', 'user_id' => $colleague->id, 'company_id' => $company->id]);
    $run = activeRun($company, $user);

    $sender = Mockery::spy(PushSender::class);
    $sender->shouldReceive('send')->andReturn([]);
    app()->instance(PushSender::class, $sender);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/close", ['outcome' => 'completed'])
        ->assertOk()
        ->assertJson(['outcome' => 'completed', 'active' => false]);

    $fresh = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->find($run->id);
    expect($fresh->ended_at)->not->toBeNull()
        ->and($fresh->aborted_at)->toBeNull();

    // Nicht mehr im Sync-Bundle.
    $data = test()->withToken($token)->getJson('/api/mobile/sync')->json('data');
    expect($data['active_runs'])->toHaveCount(0);

    // Das Gerät des Beendenden wird ausgeschlossen, das des Kollegen benachrichtigt.
    $sender->shouldHaveReceived('send')->withArgs(
        fn ($tokens, $data) => ($data['type'] ?? null) === 'incident_ended'
            && in_array('tok-end-colleague', $tokens, true)
            && ! in_array('tok-end', $tokens, true),
    );
});

test('aborting a run sets aborted_at', function () {
    [$user, $company, $token] = runSession();
    $run = activeRun($company, $user);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/close", ['outcome' => 'aborted'])
        ->assertOk()
        ->assertJson(['outcome' => 'aborted']);

    $fresh = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->find($run->id);
    expect($fresh->aborted_at)->not->toBeNull()
        ->and($fresh->ended_at)->toBeNull();
});

test('an already closed run cannot be closed again', function () {
    [$user, $company, $token] = runSession();
    $run = activeRun($company, $user, ended: true);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/close", ['outcome' => 'completed'])
        ->assertStatus(409);
});

test('a run from another company cannot be closed', function () {
    [$user, $company, $token] = runSession();
    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $run = activeRun($otherCompany, $otherUser->fresh());

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/close", ['outcome' => 'completed'])
        ->assertNotFound();

    expect(ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->find($run->id)->isActive())->toBeTrue();
});
