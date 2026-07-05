<?php

use App\Enums\CrisisRole;
use App\Events\ScenarioRunStepCompleted;
use App\Events\ScenarioRunStepReopened;
use App\Jobs\SendCompanyPush;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\Employee;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use App\Models\User;
use App\Services\Sms\SmsGatewayContract;
use App\Services\Sms\SmsResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

/**
 * Legt einen User mit Firma an, meldet ihn an und gibt [User, Company] zurück.
 *
 * @return array{0: User, 1: Company}
 */
function crisisCockpitActor(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $user = $user->fresh();

    test()->actingAs($user);

    return [$user, $company];
}

function crisisCockpitActiveRun(Company $company): ScenarioRun
{
    return ScenarioRun::factory()->for($company)->create([
        'started_at' => now(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);
}

test('addLogEntry creates an entry with type, message and user', function () {
    [$user, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    Livewire::test('pages::incident-mode.index')
        ->set('newLogType', 'decision')
        ->set('newLogMessage', 'Krisenstab einberufen')
        ->call('addLogEntry');

    $entry = CrisisLogEntry::where('scenario_run_id', $run->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->type)->toBe('decision')
        ->and($entry->message)->toBe('Krisenstab einberufen')
        ->and($entry->user_id)->toBe($user->id)
        ->and($entry->occurred_at)->not->toBeNull();
});

test('addLogEntry rejects empty message', function () {
    [, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    Livewire::test('pages::incident-mode.index')
        ->set('newLogMessage', '   ')
        ->call('addLogEntry');

    expect(CrisisLogEntry::where('scenario_run_id', $run->id)->count())->toBe(0);
});

test('toggleStep writes a step log entry', function () {
    [$user, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    $step = ScenarioRunStep::create([
        'scenario_run_id' => $run->id,
        'sort' => 0,
        'title' => 'Server isolieren',
    ]);

    Livewire::test('pages::incident-mode.index')
        ->call('toggleStep', $step->id);

    $entry = CrisisLogEntry::where('scenario_run_id', $run->id)
        ->where('type', 'step')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->message)->toContain('Server isolieren')
        ->and($entry->user_id)->toBe($user->id);
});

test('toggleStep broadcasts the step change and nudges the apps to re-sync', function () {
    Event::fake([ScenarioRunStepCompleted::class, ScenarioRunStepReopened::class]);
    Queue::fake();

    [, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    $step = ScenarioRunStep::create([
        'scenario_run_id' => $run->id,
        'sort' => 0,
        'title' => 'Server isolieren',
    ]);

    // Abhaken → Live-Broadcast an andere Browser + Sync-Push an die Apps.
    Livewire::test('pages::incident-mode.index')
        ->call('toggleStep', $step->id);

    Event::assertDispatched(ScenarioRunStepCompleted::class, fn ($e) => $e->step->id === $step->id);
    Queue::assertPushed(SendCompanyPush::class);

    // Wieder öffnen → reopened-Broadcast.
    Livewire::test('pages::incident-mode.index')
        ->call('toggleStep', $step->id);

    Event::assertDispatched(ScenarioRunStepReopened::class, fn ($e) => $e->step->id === $step->id);
});

test('endRun writes a system log entry', function () {
    [, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    Livewire::test('pages::incident-mode.index')
        ->call('endRun');

    $entry = CrisisLogEntry::where('scenario_run_id', $run->id)
        ->where('type', 'system')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($run->fresh()->ended_at)->not->toBeNull();
});

test('alertCrisisStaff sends one SMS per unique mobile number and logs an alert', function () {
    [, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    $gateway = new class implements SmsGatewayContract
    {
        /** @var list<string> */
        public array $sent = [];

        public function send(string $to, string $text, ?string $from = null): SmsResult
        {
            $this->sent[] = $to;

            return SmsResult::ok($to);
        }

        public function isConfigured(): bool
        {
            return true;
        }
    };
    $this->app->instance(SmsGatewayContract::class, $gateway);

    Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::EmergencyOfficer, deputy: false)
        ->create(['mobile_phone' => '+49 170 1111111']);

    Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::EmergencyOfficer, deputy: true)
        ->create(['mobile_phone' => '+49 170 2222222']);

    Livewire::test('pages::incident-mode.index')
        ->call('alertCrisisStaff');

    expect($gateway->sent)->toHaveCount(2);

    $alert = CrisisLogEntry::where('scenario_run_id', $run->id)
        ->where('type', 'alert')
        ->first();

    expect($alert)->not->toBeNull()
        ->and($alert->message)->toContain('2/2');
});

test('alertCrisisStaff with no numbers does not crash and sends nothing', function () {
    [, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    $gateway = new class implements SmsGatewayContract
    {
        public int $calls = 0;

        public function send(string $to, string $text, ?string $from = null): SmsResult
        {
            $this->calls++;

            return SmsResult::ok($to);
        }

        public function isConfigured(): bool
        {
            return true;
        }
    };
    $this->app->instance(SmsGatewayContract::class, $gateway);

    Livewire::test('pages::incident-mode.index')
        ->call('alertCrisisStaff');

    expect($gateway->calls)->toBe(0)
        ->and(CrisisLogEntry::where('scenario_run_id', $run->id)->where('type', 'alert')->count())->toBe(0);
});

test('protocol PDF export returns a PDF for the current tenant', function () {
    [$user, $company] = crisisCockpitActor();
    $run = crisisCockpitActiveRun($company);

    CrisisLogEntry::create([
        'company_id' => $company->id,
        'scenario_run_id' => $run->id,
        'user_id' => $user->id,
        'type' => 'note',
        'message' => 'Testeintrag',
        'occurred_at' => now(),
    ]);

    $response = $this->get(route('scenario-runs.protocol.pdf', ['run' => $run]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('protocol PDF export is 404 for a run of another tenant', function () {
    [$user] = crisisCockpitActor();

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $foreignRun = crisisCockpitActiveRun($otherCompany);

    $this->actingAs($user->fresh());

    $this->get(route('scenario-runs.protocol.pdf', [
        'current_team' => $user->currentTeam->slug,
        'run' => $foreignRun,
    ]))->assertNotFound();
});
