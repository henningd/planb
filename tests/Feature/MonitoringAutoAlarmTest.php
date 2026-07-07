<?php

use App\Enums\ScenarioRunMode;
use App\Jobs\SendCompanyPush;
use App\Models\ApiToken;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\MonitoringAlert;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * @return array{0: Company, 1: System, 2: Scenario, 3: string}
 */
function autoAlarmSetup(bool $withMapping = true): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'IT-Totalausfall',
    ]);
    $scenario->steps()->create(['sort' => 1, 'title' => 'Server prüfen', 'responsible' => 'IT']);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
        'monitoring_keys' => ['srv-prod-01'],
        'emergency_scenario_id' => $withMapping ? $scenario->id : null,
    ]);

    $issued = ApiToken::issue($company->id, 'Zabbix', ['monitoring.write'], $user->id);

    return [$company, $system, $scenario, $issued['token']];
}

function postZabbixAlert(string $token, string $eventId = 'evt-1'): TestResponse
{
    return test()->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => $eventId,
        'severity' => 'disaster',
        'status' => 'PROBLEM',
        'subject' => 'Server down',
    ], ['Authorization' => 'Bearer '.$token]);
}

test('a critical alert with an emergency scenario mapping starts a real run automatically', function () {
    Queue::fake();
    [$company, , $scenario, $token] = autoAlarmSetup();

    postZabbixAlert($token)->assertStatus(202);

    $run = ScenarioRun::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->first();

    expect($run)->not->toBeNull()
        ->and($run->scenario_id)->toBe($scenario->id)
        ->and($run->mode)->toBe(ScenarioRunMode::Real)
        ->and($run->started_by_user_id)->toBeNull()
        ->and($run->steps()->count())->toBe(1);

    // Alarmkette greift: Push an die Geräte wird dispatcht.
    Queue::assertPushed(SendCompanyPush::class, function (SendCompanyPush $job) {
        $data = (fn () => $this->data)->call($job);

        return ($data['type'] ?? null) === 'incident';
    });

    // Nachvollziehbarkeit am Alert-Datensatz.
    $alert = MonitoringAlert::query()->withoutGlobalScope(CurrentCompanyScope::class)->first();
    expect($alert->handling)->toBe('created_incident')
        ->and($alert->note)->toContain('Automatische Alarmierung')
        ->and($alert->note)->toContain($run->id);
});

test('a follow-up alert attached to the open incident does not start a second run', function () {
    Queue::fake();
    [$company, , , $token] = autoAlarmSetup();

    postZabbixAlert($token, 'evt-1')->assertStatus(202);
    postZabbixAlert($token, 'evt-2')->assertStatus(202);

    expect(ScenarioRun::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->count())->toBe(1);

    $second = MonitoringAlert::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('idempotency_key', 'evt-2')
        ->first();

    expect($second->note ?? '')->not->toContain('Automatische Alarmierung:');
});

test('without an emergency scenario mapping a critical alert only creates the incident', function () {
    Queue::fake();
    [$company, , , $token] = autoAlarmSetup(withMapping: false);

    postZabbixAlert($token)
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'created_incident');

    expect(ScenarioRun::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->exists())->toBeFalse();
});

test('no second run is started while the scenario already has an open run', function () {
    Queue::fake();
    [$company, , $scenario, $token] = autoAlarmSetup();

    $existing = ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'scenario_id' => $scenario->id,
        'mode' => ScenarioRunMode::Real,
        'started_at' => now()->subMinutes(3),
    ]);

    postZabbixAlert($token)->assertStatus(202);

    expect(ScenarioRun::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->count())->toBe(1);

    $alert = MonitoringAlert::query()->withoutGlobalScope(CurrentCompanyScope::class)->first();
    expect($alert->note)->toContain('übersprungen')
        ->and($existing->refresh()->ended_at)->toBeNull();
});

test('the system edit form persists and clears the emergency scenario mapping', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Cyber-Angriff',
    ]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Mailserver',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('emergency_scenario_id', $scenario->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->emergency_scenario_id)->toBe($scenario->id);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system->fresh()])
        ->set('emergency_scenario_id', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->emergency_scenario_id)->toBeNull();
});

test('a scenario of another company is rejected as emergency scenario', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $stranger = User::factory()->create();
    $foreignCompany = Company::factory()->for($stranger->currentTeam)->create();
    $foreignScenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $foreignCompany->id,
        'name' => 'Fremdes Szenario',
    ]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Mailserver',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('emergency_scenario_id', $foreignScenario->id)
        ->call('save')
        ->assertHasErrors(['emergency_scenario_id']);

    expect($system->fresh()->emergency_scenario_id)->toBeNull();
});

test('a monitoring-triggered run records its source and the alerting host', function () {
    Queue::fake();
    [$company, , , $token] = autoAlarmSetup();

    postZabbixAlert($token)->assertStatus(202);

    $run = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->first();
    expect($run)->not->toBeNull()
        ->and($run->source)->toBe('monitoring')
        ->and($run->trigger_detail)->toBe('srv-prod-01');

    // Feed nennt das Monitoring als Auslöser statt niemanden.
    $notification = AppNotification::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('type', 'incident_started')
        ->first();
    expect($notification->triggered_by_name)->toBe('IT-Monitoring · srv-prod-01');
});

test('a new outage after the all-clear starts a second alarm run', function () {
    Queue::fake();
    [$company, , , $token] = autoAlarmSetup();

    $post = fn (string $status, string $startsAt) => test()->postJson('/api/v1/webhooks/prometheus', [
        'alerts' => [[
            'fingerprint' => 'fp-refire',
            'status' => $status,
            'startsAt' => $startsAt,
            'labels' => ['alertname' => 'HostOffline', 'instance' => 'srv-prod-01', 'severity' => 'critical'],
            'annotations' => ['summary' => 'Host offline'],
        ]],
    ], ['Authorization' => 'Bearer '.$token]);

    // Erster Ausfall: Alarm startet. Der Notfall wird beendet, dann Entwarnung.
    $post('firing', '2026-07-07T08:55:43Z')->assertStatus(202);
    ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->update(['ended_at' => now()]);
    $post('resolved', '2026-07-07T08:55:43Z')->assertStatus(202);

    // Zweiter Ausfall am selben Tag muss WIEDER alarmieren — inkl. Push.
    $post('firing', '2026-07-07T13:54:43Z')->assertStatus(202);

    expect(ScenarioRun::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->count())->toBe(2);

    $incidentPushes = Queue::pushed(SendCompanyPush::class, function (SendCompanyPush $job) {
        $data = (fn () => $this->data)->call($job);

        return ($data['type'] ?? null) === 'incident';
    });
    expect($incidentPushes)->toHaveCount(2);
});
