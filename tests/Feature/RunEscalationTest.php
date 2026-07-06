<?php

use App\Enums\CrisisRole;
use App\Enums\ScenarioRunMode;
use App\Jobs\SendCompanyPush;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\Employee;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Services\Sms\SmsGatewayContract;
use App\Services\Sms\SmsResult;
use App\Support\Settings\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function escalationCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

function unacknowledgedRealRun(Company $company, int $ageMinutes = 15): ScenarioRun
{
    return ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Real,
        'started_at' => now()->subMinutes($ageMinutes),
    ]);
}

/**
 * Konfiguriertes Fake-SMS-Gateway, das jeden Versand mitschreibt.
 */
function fakeSmsGateway(bool $configured = true): object
{
    $gateway = new class implements SmsGatewayContract
    {
        public bool $configured = true;

        /** @var list<array{to: string, text: string}> */
        public array $sent = [];

        public function isConfigured(): bool
        {
            return $this->configured;
        }

        public function send(string $to, string $text, ?string $from = null): SmsResult
        {
            $this->sent[] = ['to' => $to, 'text' => $text];

            return SmsResult::ok($to);
        }
    };

    $gateway->configured = $configured;
    app()->instance(SmsGatewayContract::class, $gateway);

    return $gateway;
}

test('an unacknowledged real run past the deadline is escalated with push, sms and log entry', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    fakeSmsGateway();

    Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::EmergencyOfficer)
        ->create(['mobile_phone' => '+491701234567']);

    $run = unacknowledgedRealRun($company);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    $run->refresh();
    expect($run->escalated_at)->not->toBeNull();

    Queue::assertPushed(SendCompanyPush::class, function (SendCompanyPush $job) use ($run) {
        $data = (fn () => $this->data)->call($job);
        $body = (fn () => $this->body)->call($job);

        return ($data['type'] ?? null) === 'incident_escalation'
            && ($data['run_id'] ?? null) === $run->id
            && str_contains((string) $body, 'Noch niemand hat den Notfall übernommen');
    });

    $sms = app(SmsGatewayContract::class);
    expect($sms->sent)->toHaveCount(1)
        ->and($sms->sent[0]['to'])->toBe('+491701234567')
        ->and($sms->sent[0]['text'])->toContain($run->title);

    $log = CrisisLogEntry::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('scenario_run_id', $run->id)
        ->where('type', 'system')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->message)->toContain('Eskalation')
        ->and($log->message)->toContain('SMS an 1 Krisenstab-Nummer(n)');
});

test('escalation works without a configured sms gateway (push and log only)', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    $sms = fakeSmsGateway(configured: false);

    Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::Management)
        ->create(['mobile_phone' => '+491700000001']);

    $run = unacknowledgedRealRun($company);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    expect($run->refresh()->escalated_at)->not->toBeNull()
        ->and($sms->sent)->toBe([]);

    Queue::assertPushed(SendCompanyPush::class, 1);
});

test('an acknowledged run is never escalated', function () {
    Queue::fake();
    [$user, $company] = escalationCompany();
    fakeSmsGateway();

    $run = unacknowledgedRealRun($company);
    $run->acknowledgements()->create([
        'user_id' => $user->id,
        'status' => 'seen',
        'acknowledged_at' => now(),
    ]);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    expect($run->refresh()->escalated_at)->toBeNull();
    Queue::assertNothingPushed();
});

test('a drill run is never escalated, no matter how old', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    fakeSmsGateway();

    $run = ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Drill,
        'started_at' => now()->subHours(5),
    ]);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    expect($run->refresh()->escalated_at)->toBeNull();
    Queue::assertNothingPushed();
});

test('a run younger than the deadline is not escalated yet', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    fakeSmsGateway();

    $run = unacknowledgedRealRun($company, ageMinutes: 5);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    expect($run->refresh()->escalated_at)->toBeNull();
    Queue::assertNothingPushed();
});

test('an ended or aborted run is not escalated', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    fakeSmsGateway();

    $ended = ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Real,
        'started_at' => now()->subMinutes(30),
        'ended_at' => now()->subMinutes(2),
    ]);
    $aborted = ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Real,
        'started_at' => now()->subMinutes(30),
        'aborted_at' => now()->subMinutes(2),
    ]);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    expect($ended->refresh()->escalated_at)->toBeNull()
        ->and($aborted->refresh()->escalated_at)->toBeNull();
    Queue::assertNothingPushed();
});

test('a run is escalated at most once', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    fakeSmsGateway();

    $run = unacknowledgedRealRun($company);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();
    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    Queue::assertPushed(SendCompanyPush::class, 1);

    expect(CrisisLogEntry::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('scenario_run_id', $run->id)
        ->count())->toBe(1);
});

test('setting the escalation time to zero disables escalation for the company', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    fakeSmsGateway();

    CompanySetting::for($company)->set('alarm_escalation_minutes', 0);

    $run = unacknowledgedRealRun($company, ageMinutes: 120);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    expect($run->refresh()->escalated_at)->toBeNull();
    Queue::assertNothingPushed();
});

test('a shorter per-company deadline escalates earlier than the default', function () {
    Queue::fake();
    [, $company] = escalationCompany();
    fakeSmsGateway();

    CompanySetting::for($company)->set('alarm_escalation_minutes', 5);

    $run = unacknowledgedRealRun($company, ageMinutes: 6);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    expect($run->refresh()->escalated_at)->not->toBeNull();
});
