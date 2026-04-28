<?php

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\MonitoringAlert;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function setupApiTokenAndSystem(?array $monitoringKeys = ['srv-prod-01']): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
        'monitoring_keys' => $monitoringKeys,
    ]);

    $issued = ApiToken::issue($company->id, 'Test', ['monitoring.write'], $user->id);

    return [$user, $company, $system, $issued['token']];
}

it('rejects requests without an authorization header', function () {
    $this->postJson('/api/v1/webhooks/zabbix', ['host' => 'srv-prod-01'])
        ->assertStatus(401)
        ->assertJson(['error' => 'missing_token']);
});

it('rejects requests with an invalid bearer token', function () {
    $this->postJson('/api/v1/webhooks/zabbix', ['host' => 'srv-prod-01'], [
        'Authorization' => 'Bearer planb_definitelynotvalid',
    ])->assertStatus(401)->assertJson(['error' => 'invalid_token']);
});

it('rejects revoked tokens', function () {
    [, $company, , $token] = setupApiTokenAndSystem();
    ApiToken::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->update(['revoked_at' => now()]);

    $this->postJson('/api/v1/webhooks/zabbix', ['host' => 'srv-prod-01'], [
        'Authorization' => 'Bearer '.$token,
    ])->assertStatus(401);
});

it('rejects tokens lacking the monitoring scope', function () {
    [, $company] = setupApiTokenAndSystem();
    $issued = ApiToken::issue($company->id, 'Read-Only', ['some.other.scope'], null);

    $this->postJson('/api/v1/webhooks/zabbix', ['host' => 'srv-prod-01'], [
        'Authorization' => 'Bearer '.$issued['token'],
    ])->assertStatus(403);
});

it('creates an incident from a Zabbix problem alert with high severity', function () {
    [, $company, $system, $token] = setupApiTokenAndSystem();

    $this->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => 'evt-1',
        'severity' => 'high',
        'status' => 'PROBLEM',
        'subject' => 'Disk full',
    ], ['Authorization' => 'Bearer '.$token])
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'created_incident');

    $alert = MonitoringAlert::query()->withoutGlobalScope(CurrentCompanyScope::class)->first();
    expect($alert)->not->toBeNull();
    expect($alert->system_id)->toBe($system->id);
    expect($alert->incident_report_id)->not->toBeNull();

    $incident = IncidentReport::query()->withoutGlobalScope(CurrentCompanyScope::class)->find($alert->incident_report_id);
    expect($incident->title)->toContain('Disk full');
    expect($incident->company_id)->toBe($company->id);
});

it('does not create an incident when severity is below threshold', function () {
    [, , , $token] = setupApiTokenAndSystem();

    $this->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => 'evt-low-1',
        'severity' => 'information',
        'status' => 'PROBLEM',
        'subject' => 'Info only',
    ], ['Authorization' => 'Bearer '.$token])
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'severity_below_threshold');

    expect(IncidentReport::query()->withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});

it('reports no_system_match when host does not map to a system', function () {
    [, , , $token] = setupApiTokenAndSystem(['some-other-host']);

    $this->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'unknown-host',
        'event_id' => 'evt-2',
        'severity' => 'high',
        'status' => 'PROBLEM',
        'subject' => 'X',
    ], ['Authorization' => 'Bearer '.$token])
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'no_system_match');
});

it('is idempotent on the same event_id', function () {
    [, , , $token] = setupApiTokenAndSystem();

    $payload = [
        'host' => 'srv-prod-01',
        'event_id' => 'evt-idem',
        'severity' => 'high',
        'status' => 'PROBLEM',
        'subject' => 'Down',
    ];

    $this->postJson('/api/v1/webhooks/zabbix', $payload, ['Authorization' => 'Bearer '.$token])->assertStatus(202);
    $this->postJson('/api/v1/webhooks/zabbix', $payload, ['Authorization' => 'Bearer '.$token])->assertStatus(202);

    expect(MonitoringAlert::query()->withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(1);
    expect(IncidentReport::query()->withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(1);
});

it('attaches a follow-up alert to an existing recent incident on the same system', function () {
    [, , , $token] = setupApiTokenAndSystem();

    $this->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => 'evt-A',
        'severity' => 'disaster',
        'status' => 'PROBLEM',
        'subject' => 'Erstmeldung',
    ], ['Authorization' => 'Bearer '.$token])->assertStatus(202);

    $this->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => 'evt-B',
        'severity' => 'critical',
        'status' => 'PROBLEM',
        'subject' => 'Folgemeldung',
    ], ['Authorization' => 'Bearer '.$token])->assertStatus(202);

    expect(IncidentReport::query()->withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(1);
    expect(MonitoringAlert::query()->withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});

it('parses a Prometheus alertmanager payload with multiple alerts', function () {
    [, , , $token] = setupApiTokenAndSystem();

    $payload = [
        'alerts' => [
            [
                'fingerprint' => 'fp-1',
                'status' => 'firing',
                'labels' => ['alertname' => 'NodeDown', 'instance' => 'srv-prod-01:9100', 'severity' => 'critical'],
                'annotations' => ['summary' => 'Node ist nicht erreichbar'],
            ],
            [
                'fingerprint' => 'fp-2',
                'status' => 'firing',
                'labels' => ['alertname' => 'OtherAlert', 'instance' => 'unknown:9100', 'severity' => 'warning'],
                'annotations' => ['summary' => 'Other'],
            ],
        ],
    ];

    $this->postJson('/api/v1/webhooks/prometheus', $payload, ['Authorization' => 'Bearer '.$token])
        ->assertStatus(202)
        ->assertJson(['received' => 2]);

    $alerts = MonitoringAlert::query()->withoutGlobalScope(CurrentCompanyScope::class)->orderBy('created_at')->get();
    expect($alerts)->toHaveCount(2);
    expect($alerts[0]->handling)->toBe('created_incident');
    expect($alerts[1]->handling)->toBeIn(['no_system_match', 'severity_below_threshold']);
});

it('does not open a new incident on a resolved alert without prior incident', function () {
    [, , , $token] = setupApiTokenAndSystem();

    $this->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => 'evt-res',
        'severity' => 'high',
        'status' => 'OK',
        'subject' => 'recovered',
    ], ['Authorization' => 'Bearer '.$token])
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'ignored');

    expect(IncidentReport::query()->withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});

it('updates last_used_at on the token after an authenticated call', function () {
    [, , , $token] = setupApiTokenAndSystem();

    $before = ApiToken::query()->withoutGlobalScope(CurrentCompanyScope::class)->first()->last_used_at;
    expect($before)->toBeNull();

    $this->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => 'evt-touch',
        'severity' => 'high',
        'status' => 'PROBLEM',
        'subject' => 'X',
    ], ['Authorization' => 'Bearer '.$token])->assertStatus(202);

    $after = ApiToken::query()->withoutGlobalScope(CurrentCompanyScope::class)->first()->last_used_at;
    expect($after)->not->toBeNull();
});

it('hides the API page when the feature flag is off', function () {
    config(['features.monitoring_api' => false]);

    expect(Route::has('api-tokens.index'))->toBeFalse();
})->skip('Routes are registered at boot; flag-flip mid-test is not supported.');
