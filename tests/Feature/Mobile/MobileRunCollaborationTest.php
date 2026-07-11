<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\MobileAccessCode;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunMessage;
use App\Models\ScenarioRunStep;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Mobile\MobileSyncBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: string, 3: ScenarioRun}
 */
function collabSession(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $user = $user->fresh();

    $issued = MobileAccessCode::issue($user, $company);
    $token = test()->postJson('/api/mobile/login', [
        'email' => $user->email,
        'code' => $issued['code'],
    ])->json('token');

    $run = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'started_by_user_id' => $user->id,
        'title' => 'Ausfall · Ernstfall',
        'mode' => 'real',
        'started_at' => now(),
    ]);

    return [$user, $company, $token, $run];
}

test('the app can post a coordination message', function () {
    [, $company, $token, $run] = collabSession();

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/messages", ['body' => 'Feuerwehr eingetroffen'])
        ->assertOk()
        ->assertJsonPath('body', 'Feuerwehr eingetroffen');

    expect(ScenarioRunMessage::where('scenario_run_id', $run->id)->value('body'))->toBe('Feuerwehr eingetroffen');
});

test('the app can assign a step to an employee and clear it', function () {
    [, $company, $token, $run] = collabSession();
    $step = ScenarioRunStep::create(['scenario_run_id' => $run->id, 'sort' => 1, 'title' => 'Serverraum abriegeln']);
    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Ben', 'last_name' => 'Schulz',
    ]);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/steps/{$step->id}/assign", ['employee_id' => $employee->id])
        ->assertOk()
        ->assertJsonPath('assigned_to', 'Ben Schulz');
    expect($step->refresh()->assigned_employee_id)->toBe($employee->id);

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/steps/{$step->id}/assign", ['employee_id' => null])
        ->assertOk();
    expect($step->refresh()->assigned_employee_id)->toBeNull();
});

test('a heartbeat marks the user present and lists active participants', function () {
    [$user, $company, $token, $run] = collabSession();

    test()->withToken($token)
        ->postJson("/api/mobile/runs/{$run->id}/heartbeat")
        ->assertOk()
        ->assertJsonPath('participants.0.user_id', (string) $user->id);

    // …und der Sync-Payload trägt die aktiven Teilnehmer.
    $bundle = MobileSyncBundle::for($company->fresh());
    expect($bundle['active_runs'][0]['participants'])->toHaveCount(1)
        ->and($bundle['active_runs'][0]['participants'][0]['user_id'])->toBe((string) $user->id);
});
