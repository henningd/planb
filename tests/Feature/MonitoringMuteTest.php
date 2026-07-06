<?php

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\MonitoringAlert;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * @return array{0: Company, 1: System, 2: Scenario, 3: string}
 */
function muteSetup(?CarbonInterface $mutedUntil = null): array
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
        'emergency_scenario_id' => $scenario->id,
        'monitoring_muted_until' => $mutedUntil,
    ]);

    $issued = ApiToken::issue($company->id, 'Zabbix', ['monitoring.write'], $user->id);

    return [$company, $system, $scenario, $issued['token']];
}

function postMuteZabbixAlert(string $token, string $eventId = 'evt-1', string $status = 'PROBLEM'): TestResponse
{
    return test()->postJson('/api/v1/webhooks/zabbix', [
        'host' => 'srv-prod-01',
        'event_id' => $eventId,
        'severity' => 'disaster',
        'status' => $status,
        'subject' => 'Server down',
    ], ['Authorization' => 'Bearer '.$token]);
}

test('a critical alert during an active maintenance window is only logged as muted', function () {
    Queue::fake();
    [$company, , , $token] = muteSetup(mutedUntil: now()->addHours(2));

    postMuteZabbixAlert($token)
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'muted');

    $alert = MonitoringAlert::query()->withoutGlobalScope(CurrentCompanyScope::class)->first();
    expect($alert->handling)->toBe('muted')
        ->and($alert->incident_report_id)->toBeNull()
        ->and($alert->note)->toContain('Wartungsfenster');

    // Kein Incident, kein Auto-Alarm trotz emergency_scenario-Mapping.
    expect(IncidentReport::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->exists())->toBeFalse()
        ->and(ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->exists())->toBeFalse();
});

test('an expired maintenance window processes alerts normally again', function () {
    Queue::fake();
    [$company, , , $token] = muteSetup(mutedUntil: now()->subMinutes(5));

    postMuteZabbixAlert($token)
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'created_incident');

    expect(IncidentReport::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->count())->toBe(1)
        ->and(ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->count())->toBe(1);
});

test('a resolved alert still annotates the open incident while the system is muted', function () {
    Queue::fake();
    [$company, $system, , $token] = muteSetup();

    // Erst ein normaler kritischer Alert (Fenster noch nicht aktiv) → Incident.
    postMuteZabbixAlert($token, 'evt-1')->assertStatus(202);

    $system->forceFill(['monitoring_muted_until' => now()->addHours(4)])->save();

    // Entwarnung während des Wartungsfensters verhält sich wie bisher.
    postMuteZabbixAlert($token, 'evt-2', status: 'OK')
        ->assertStatus(202)
        ->assertJsonPath('alerts.0.handling', 'matched_existing');

    $incident = IncidentReport::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->first();

    expect($incident->notes)->toContain('RESOLVED');
});

test('the system edit form persists and clears the maintenance window', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Mailserver',
        'category' => 'geschaeftsbetrieb',
    ]);

    $until = now()->addDay()->startOfHour();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('monitoring_muted_until', $until->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->monitoring_muted_until?->format('Y-m-d H:i'))
        ->toBe($until->format('Y-m-d H:i'));

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system->fresh()])
        ->set('monitoring_muted_until', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->monitoring_muted_until)->toBeNull();
});
