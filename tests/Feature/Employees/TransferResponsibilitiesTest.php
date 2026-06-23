<?php

use App\Actions\Employees\TransferResponsibilities;
use App\Enums\TeamRole;
use App\Models\BusinessProcess;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\Team;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Support\AssignmentSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

/**
 * Sets up a tenant (team + company), acts as its owner so the company-scoped
 * models resolve, and returns the company every test record must belong to.
 */
function handoverTenant(): Company
{
    $team = Team::factory()->create();
    $company = Company::factory()->for($team)->create();
    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->forceFill(['current_team_id' => $team->id])->save();

    test()->actingAs($owner->fresh());

    // Mirror SetTeamUrlDefaults so team-scoped route() generation works in
    // component redirects during Livewire tests.
    URL::defaults(['current_team' => $team->slug]);

    return $company;
}

test('handover moves every active responsibility from A to B and frees A', function () {
    $company = handoverTenant();
    $cid = ['company_id' => $company->id];

    $a = Employee::factory()->create($cid);
    $b = Employee::factory()->create($cid);
    $report = Employee::factory()->create($cid);

    $system = System::factory()->create($cid);
    $task = SystemTask::factory()->create(['company_id' => $company->id, 'system_id' => $system->id]);
    $role = Role::factory()->create($cid);

    AssignmentSync::attach($a, $a->systems(), $system->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);
    AssignmentSync::attach($a, $a->tasks(), $task->id, ['raci_role' => 'R', 'is_deputy' => false]);
    AssignmentSync::attach($a, $a->roles(), $role->id, ['is_deputy' => false]);
    DB::table('employee_manager')->insert(['employee_id' => $report->id, 'manager_id' => $a->id]);
    $process = BusinessProcess::factory()->create($cid + ['responsible_employee_id' => $a->id]);

    $summary = app(TransferResponsibilities::class)->handle($a, $b);

    expect($summary)->toMatchArray([
        'systems' => 1,
        'tasks' => 1,
        'roles' => 1,
        'reports' => 1,
        'responsibilities' => 1,
    ]);

    expect($a->systems()->count())->toBe(0)
        ->and($a->tasks()->count())->toBe(0)
        ->and($a->roles()->count())->toBe(0)
        ->and($b->systems()->whereKey($system->id)->exists())->toBeTrue()
        ->and($b->tasks()->whereKey($task->id)->exists())->toBeTrue()
        ->and($b->roles()->whereKey($role->id)->exists())->toBeTrue();

    expect(DB::table('employee_manager')->where('manager_id', $b->id)->where('employee_id', $report->id)->exists())->toBeTrue()
        ->and(DB::table('employee_manager')->where('manager_id', $a->id)->exists())->toBeFalse()
        ->and($process->fresh()->responsible_employee_id)->toBe($b->id);
});

test('handover does not duplicate or overwrite what B already has', function () {
    $company = handoverTenant();
    $cid = ['company_id' => $company->id];

    $a = Employee::factory()->create($cid);
    $b = Employee::factory()->create($cid);
    $system = System::factory()->create($cid);

    AssignmentSync::attach($a, $a->systems(), $system->id, ['raci_role' => 'C', 'ownership_kind' => 'contact', 'is_deputy' => false]);
    AssignmentSync::attach($b, $b->systems(), $system->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);

    app(TransferResponsibilities::class)->handle($a, $b);

    expect($a->systems()->count())->toBe(0)
        ->and($b->systems()->count())->toBe(1)
        ->and($b->systems()->first()->pivot->raci_role)->toBe('R');
});

test('handover leaves personal history (training records) with A', function () {
    $company = handoverTenant();
    $cid = ['company_id' => $company->id];

    $a = Employee::factory()->create($cid);
    $b = Employee::factory()->create($cid);
    TrainingRecord::factory()->create($cid + ['employee_id' => $a->id]);

    app(TransferResponsibilities::class)->handle($a, $b);

    expect($a->trainingRecords()->count())->toBe(1)
        ->and($b->trainingRecords()->count())->toBe(0);
});

test('handover refuses two employees from different companies', function () {
    $company = handoverTenant();

    $a = Employee::factory()->create(['company_id' => $company->id]);
    $otherCompany = Company::factory()->create();
    $b = Employee::factory()->create(['company_id' => $otherCompany->id]);

    app(TransferResponsibilities::class)->handle($a, $b);
})->throws(InvalidArgumentException::class);

test('handover refuses transferring to the same employee', function () {
    $company = handoverTenant();

    $a = Employee::factory()->create(['company_id' => $company->id]);

    app(TransferResponsibilities::class)->handle($a, $a);
})->throws(InvalidArgumentException::class);

test('the employee page handover action transfers and frees A', function () {
    $company = handoverTenant();
    $cid = ['company_id' => $company->id];

    $a = Employee::factory()->create($cid);
    $b = Employee::factory()->create($cid);
    $system = System::factory()->create($cid);
    AssignmentSync::attach($a, $a->systems(), $system->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);

    Livewire::test('pages::employees.show', ['employee' => $a])
        ->set('handoverTargetId', $b->id)
        ->call('handover')
        ->assertHasNoErrors()
        ->assertRedirect(route('employees.show', $a));

    expect($a->systems()->count())->toBe(0)
        ->and($b->systems()->whereKey($system->id)->exists())->toBeTrue();
});

test('the handover action requires a target employee', function () {
    $company = handoverTenant();
    $a = Employee::factory()->create(['company_id' => $company->id]);

    Livewire::test('pages::employees.show', ['employee' => $a])
        ->call('handover')
        ->assertHasErrors('handoverTargetId');
});
