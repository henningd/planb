<?php

use App\Enums\ScenarioRunMode;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\Location;
use App\Models\MobileAccessCode;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\ScenarioStep;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: string}
 */
function syncSession(): array
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

test('the sync bundle returns the mandant data', function () {
    [$user, $company, $token] = syncSession();

    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);
    ScenarioStep::factory()->for($scenario)->create(['sort' => 1, 'title' => 'Notstrom prüfen']);
    Location::factory()->for($company)->create(['name' => 'Hauptsitz', 'is_headquarters' => true]);
    ServiceProvider::factory()->for($company)->create(['name' => 'IT-Dienstleister', 'hotline' => '0800-123']);

    test()->withToken($token)->getJson('/api/mobile/sync')
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'synced_at', 'handbook', 'company' => ['id', 'name'],
            'locations', 'crisis_roles', 'service_providers',
            'emergency_resources', 'recovery_order', 'scenarios', 'aushang_codes',
        ]])
        ->assertJsonPath('data.company.id', $company->id)
        ->assertJsonFragment(['title' => 'Stromausfall'])
        ->assertJsonFragment(['name' => 'Hauptsitz', 'is_headquarters' => true])
        ->assertJsonFragment(['name' => 'IT-Dienstleister', 'emergency_phone' => '0800-123'])
        ->assertJsonFragment(['scenario_id' => $scenario->id, 'location_id' => null]);
});

test('the sync bundle is strictly scoped to the token mandant', function () {
    [$user, $company, $token] = syncSession();

    $other = Company::factory()->create();
    Scenario::factory()->for($other)->create(['name' => 'FremdSzenario']);

    test()->withToken($token)->getJson('/api/mobile/sync')
        ->assertOk()
        ->assertJsonMissing(['title' => 'FremdSzenario']);
});

test('sync requires a bearer token', function () {
    test()->getJson('/api/mobile/sync')->assertStatus(401);
});

test('sync records the last-synced timestamp on the device token', function () {
    [$user, $company, $token] = syncSession();

    expect(ApiToken::findActiveByPlainToken($token)?->last_synced_at)->toBeNull();

    test()->withToken($token)->getJson('/api/mobile/sync')->assertOk();

    expect(ApiToken::findActiveByPlainToken($token)?->last_synced_at)->not->toBeNull();
});

test('an unchanged sync short-circuits via the version fingerprint', function () {
    [$user, $company, $token] = syncSession();
    Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);

    $first = test()->withToken($token)->getJson('/api/mobile/sync')->assertOk();
    $version = $first->json('data.version');
    expect($version)->toBeString()
        ->and($first->json('data.unchanged'))->toBeFalse();

    $second = test()->withToken($token)
        ->getJson('/api/mobile/sync?version='.urlencode($version))
        ->assertOk()
        ->assertJsonPath('data.unchanged', true)
        ->assertJsonPath('data.version', $version);

    // Kompakte Antwort: keine Inhaltsdaten.
    expect($second->json('data'))->not->toHaveKey('scenarios');
});

test('a change yields a new version and the full bundle', function () {
    [$user, $company, $token] = syncSession();
    Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);
    $version = test()->withToken($token)->getJson('/api/mobile/sync')->json('data.version');

    Scenario::factory()->for($company)->create(['name' => 'Cyberangriff']);

    $res = test()->withToken($token)
        ->getJson('/api/mobile/sync?version='.urlencode($version))
        ->assertOk()
        ->assertJsonPath('data.unchanged', false)
        ->assertJsonFragment(['title' => 'Cyberangriff']);

    expect($res->json('data.version'))->not->toBe($version);
});

test('an escalation changes the version and exposes escalated_at on the active run', function () {
    [$user, $company, $token] = syncSession();

    $run = ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Real,
        'started_at' => now()->subMinutes(15),
    ]);

    $first = test()->withToken($token)->getJson('/api/mobile/sync')->assertOk();
    $version = $first->json('data.version');

    expect($first->json('data.active_runs.0.id'))->toBe($run->id)
        ->and($first->json('data.active_runs.0.escalated_at'))->toBeNull();

    $run->forceFill(['escalated_at' => now()])->save();

    test()->withToken($token)
        ->getJson('/api/mobile/sync?version='.urlencode($version))
        ->assertOk()
        ->assertJsonPath('data.unchanged', false)
        ->assertJsonPath('data.active_runs.0.escalated_at', $run->refresh()->escalated_at->toIso8601String());
});

test('a deletion also changes the version so it propagates offline', function () {
    [$user, $company, $token] = syncSession();
    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);
    $version = test()->withToken($token)->getJson('/api/mobile/sync')->json('data.version');

    $scenario->delete();

    $res = test()->withToken($token)
        ->getJson('/api/mobile/sync?version='.urlencode($version))
        ->assertOk()
        ->assertJsonPath('data.unchanged', false)
        ->assertJsonMissing(['title' => 'Stromausfall']);

    expect($res->json('data.version'))->not->toBe($version);
});

test('the handbook falls back to a live current version when none is approved', function () {
    [$user, $company, $token] = syncSession();

    $res = test()->withToken($token)->getJson('/api/mobile/sync')->assertOk();

    expect($res->json('data.handbook.version_id'))->toBe('current')
        ->and($res->json('data.handbook.pdf_url'))->toContain('/handbook/current/pdf')
        ->and($res->json('data.handbook.hash'))->toBeString();
});

test('the current handbook renders a live pdf', function () {
    [$user, $company, $token] = syncSession();

    $res = test()->withToken($token)->get('/api/mobile/handbook/current/pdf')->assertOk();

    expect(substr((string) $res->getContent(), 0, 4))->toBe('%PDF');
});

test('the handbook pdf endpoint rejects a foreign or unknown version', function () {
    [$user, $company, $token] = syncSession();

    test()->withToken($token)
        ->get('/api/mobile/handbook/'.Str::uuid()->toString().'/pdf')
        ->assertNotFound();
});

test('scenarios carry description and the alarm chain in the bundle', function () {
    [$user, $company, $token] = syncSession();

    Scenario::factory()->for($company)->create([
        'name' => 'Cyber-Angriff',
        'description' => 'Ransomware oder Datenabfluss.',
        'trigger' => 'Monitoring-Alarm oder Meldung',
        'alarm_chain_detector' => 'IT-Monitoring / Mitarbeitende',
        'alarm_chain_lead_role' => 'Notfallbeauftragte/r',
    ]);

    $data = test()->withToken($token)->getJson('/api/mobile/sync')
        ->assertOk()
        ->json('data.scenarios');

    $scenario = collect($data)->firstWhere('title', 'Cyber-Angriff');

    expect($scenario['description'])->toBe('Ransomware oder Datenabfluss.')
        ->and($scenario['trigger'])->toBe('Monitoring-Alarm oder Meldung')
        ->and($scenario['alarm_chain'])->toBe([
            'detector' => 'IT-Monitoring / Mitarbeitende',
            'lead_role' => 'Notfallbeauftragte/r',
        ]);

    // Ohne gefüllte Felder bleibt alarm_chain null (Apps blenden den Abschnitt aus).
    Scenario::factory()->for($company)->create([
        'name' => 'Ohne Kette',
        'alarm_chain_detector' => null,
    ]);
    $bare = collect(test()->withToken($token)->getJson('/api/mobile/sync')->json('data.scenarios'))
        ->firstWhere('title', 'Ohne Kette');
    expect($bare['alarm_chain'])->toBeNull();
});

test('the recovery order carries levels and metrics for the apps', function () {
    [$user, $company, $token] = syncSession();

    $level = EmergencyLevel::factory()->for($company)->create([
        'name' => 'Stufe 1 (kritisch)',
        'description' => 'Sofort wiederherstellen.',
        'reaction' => 'Innerhalb von 60 Minuten reagieren.',
        'sort' => 1,
    ]);
    $system = System::factory()->for($company)->create([
        'name' => 'ERP',
        'emergency_level_id' => $level->id,
        'rto_minutes' => 120,
        'rpo_minutes' => 30,
        'fallback_process' => 'Papierliste im Lager',
        'runbook_reference' => 'Wiki: ERP-Restore',
    ]);
    SystemTask::factory()->for($system)->for($company)->create(['title' => 'Restore starten']);

    $row = collect(test()->withToken($token)->getJson('/api/mobile/sync')->json('data.recovery_order'))
        ->firstWhere('system', 'ERP');

    expect($row['level'])->toBe('Stufe 1 (kritisch)')
        ->and($row['level_description'])->toBe('Sofort wiederherstellen.')
        ->and($row['level_reaction'])->toBe('Innerhalb von 60 Minuten reagieren.')
        ->and($row['rpo_minutes'])->toBe(30)
        ->and($row['open_tasks'])->toBe(1)
        ->and($row['total_tasks'])->toBe(1)
        ->and($row['fallback_process'])->toBe('Papierliste im Lager')
        ->and($row['runbook_reference'])->toBe('Wiki: ERP-Restore')
        ->and($row['tasks'])->toBe([['title' => 'Restore starten', 'done' => false]])
        ->and($row['stage'])->toBe(1);
});
