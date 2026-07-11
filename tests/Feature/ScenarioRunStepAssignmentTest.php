<?php

use App\Events\ScenarioRunStepAssigned;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Mobile\MobileSyncBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: ScenarioRun, 3: ScenarioRunStep, 4: Employee}
 */
function runWithStepAndEmployee(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'title' => 'Wasserschaden', 'mode' => 'real', 'started_at' => now(),
    ]);
    $step = ScenarioRunStep::create(['scenario_run_id' => $run->id, 'sort' => 1, 'title' => 'Serverraum abriegeln']);
    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Ben', 'last_name' => 'Schulz',
    ]);

    return [$user->fresh(), $company, $run, $step, $employee];
}

test('a step can be assigned to a person and it broadcasts', function () {
    [$user, , $run, $step, $employee] = runWithStepAndEmployee();
    Event::fake([ScenarioRunStepAssigned::class]);

    Livewire::actingAs($user)
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->call('assignStep', $step->id, $employee->id)
        ->assertHasNoErrors();

    expect($step->refresh()->assigned_employee_id)->toBe($employee->id);
    Event::assertDispatched(ScenarioRunStepAssigned::class);
});

test('a step assignment can be cleared', function () {
    [$user, , $run, $step, $employee] = runWithStepAndEmployee();
    $step->update(['assigned_employee_id' => $employee->id]);

    Livewire::actingAs($user)
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->call('assignStep', $step->id, '');

    expect($step->refresh()->assigned_employee_id)->toBeNull();
});

test('the mobile sync bundle carries the step assignee', function () {
    [, $company, , $step, $employee] = runWithStepAndEmployee();
    $step->update(['assigned_employee_id' => $employee->id]);

    $bundle = MobileSyncBundle::for($company);

    expect($bundle['active_runs'][0]['steps'][0]['assigned_to'])->toBe('Ben Schulz');
});
