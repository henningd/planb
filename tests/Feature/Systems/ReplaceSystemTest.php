<?php

use App\Actions\Systems\ReplaceSystem;
use App\Enums\TeamRole;
use App\Models\BusinessProcess;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PreventiveMeasure;
use App\Models\Risk;
use App\Models\Role;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\Team;
use App\Models\User;
use App\Support\AssignmentSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

/**
 * Tenant (team + company) + acting owner so company-scoped models resolve.
 */
function systemTenant(): Company
{
    $team = Team::factory()->create();
    $company = Company::factory()->for($team)->create();
    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->forceFill(['current_team_id' => $team->id])->save();

    test()->actingAs($owner->fresh());
    URL::defaults(['current_team' => $team->slug]);

    return $company;
}

test('replacing a system moves every relationship (incl. both dependency directions) to the new system', function () {
    $company = systemTenant();
    $cid = ['company_id' => $company->id];

    $a = System::factory()->create($cid);
    $b = System::factory()->create($cid);
    $dependsOn = System::factory()->create($cid);
    $dependent = System::factory()->create($cid);

    $provider = ServiceProvider::factory()->create($cid);
    $employee = Employee::factory()->create($cid);
    $role = Role::factory()->create($cid);
    $process = BusinessProcess::factory()->create($cid);
    $risk = Risk::factory()->create($cid);
    $task = SystemTask::factory()->create($cid + ['system_id' => $a->id]);
    $measure = PreventiveMeasure::factory()->create($cid + ['system_id' => $a->id]);

    AssignmentSync::attach($a, $a->serviceProviders(), $provider->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);
    AssignmentSync::attach($a, $a->employees(), $employee->id, ['raci_role' => 'A', 'ownership_kind' => 'owner', 'is_deputy' => false]);
    AssignmentSync::attach($a, $a->roles(), $role->id, ['raci_role' => 'C', 'ownership_kind' => 'contact', 'is_deputy' => false]);

    DB::table('system_dependencies')->insert(['system_id' => $a->id, 'depends_on_system_id' => $dependsOn->id, 'sort' => 0]);
    DB::table('system_dependencies')->insert(['system_id' => $dependent->id, 'depends_on_system_id' => $a->id, 'sort' => 0]);
    DB::table('business_process_system')->insert(['business_process_id' => $process->id, 'system_id' => $a->id]);
    DB::table('risk_system')->insert(['risk_id' => $risk->id, 'system_id' => $a->id]);

    $summary = app(ReplaceSystem::class)->handle($a, $b);

    expect($summary)->toMatchArray([
        'providers' => 1,
        'employees' => 1,
        'roles' => 1,
        'dependencies' => 2,
        'processes' => 1,
        'risks' => 1,
        'tasks' => 1,
        'measures' => 1,
    ]);

    // Responsible parties moved.
    expect($a->serviceProviders()->count())->toBe(0)
        ->and($a->employees()->count())->toBe(0)
        ->and($a->roles()->count())->toBe(0)
        ->and($b->serviceProviders()->whereKey($provider->id)->exists())->toBeTrue()
        ->and($b->employees()->whereKey($employee->id)->exists())->toBeTrue()
        ->and($b->roles()->whereKey($role->id)->exists())->toBeTrue();

    // Dependencies moved in BOTH directions, none left on A.
    expect(DB::table('system_dependencies')->where('system_id', $b->id)->where('depends_on_system_id', $dependsOn->id)->exists())->toBeTrue()
        ->and(DB::table('system_dependencies')->where('system_id', $dependent->id)->where('depends_on_system_id', $b->id)->exists())->toBeTrue()
        ->and(DB::table('system_dependencies')->where('system_id', $a->id)->exists())->toBeFalse()
        ->and(DB::table('system_dependencies')->where('depends_on_system_id', $a->id)->exists())->toBeFalse();

    // Process/risk links + children moved.
    expect(DB::table('business_process_system')->where('business_process_id', $process->id)->where('system_id', $b->id)->exists())->toBeTrue()
        ->and(DB::table('risk_system')->where('risk_id', $risk->id)->where('system_id', $b->id)->exists())->toBeTrue()
        ->and($task->fresh()->system_id)->toBe($b->id)
        ->and($measure->fresh()->system_id)->toBe($b->id);
});

test('replacing avoids self-dependencies and does not overwrite what B already has', function () {
    $company = systemTenant();
    $cid = ['company_id' => $company->id];

    $a = System::factory()->create($cid);
    $b = System::factory()->create($cid);
    $provider = ServiceProvider::factory()->create($cid);

    // A already depends on B -> must NOT become "B depends on B".
    DB::table('system_dependencies')->insert(['system_id' => $a->id, 'depends_on_system_id' => $b->id, 'sort' => 0]);

    // Both A and B linked to the same provider; B with stronger role.
    AssignmentSync::attach($a, $a->serviceProviders(), $provider->id, ['raci_role' => 'C', 'ownership_kind' => 'contact', 'is_deputy' => false]);
    AssignmentSync::attach($b, $b->serviceProviders(), $provider->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);

    app(ReplaceSystem::class)->handle($a, $b);

    expect(DB::table('system_dependencies')->where('system_id', $b->id)->where('depends_on_system_id', $b->id)->exists())->toBeFalse()
        ->and($b->serviceProviders()->count())->toBe(1)
        ->and($b->serviceProviders()->first()->pivot->raci_role)->toBe('R');
});

test('replace refuses different companies and the same system', function () {
    $company = systemTenant();
    $a = System::factory()->create(['company_id' => $company->id]);

    expect(fn () => app(ReplaceSystem::class)->handle($a, $a))->toThrow(InvalidArgumentException::class);

    $other = System::factory()->create(['company_id' => Company::factory()->create()->id]);
    expect(fn () => app(ReplaceSystem::class)->handle($a, $other))->toThrow(InvalidArgumentException::class);
});

test('the system page replace action transfers and redirects to the new system', function () {
    $company = systemTenant();
    $cid = ['company_id' => $company->id];

    $a = System::factory()->create($cid);
    $b = System::factory()->create($cid);
    $provider = ServiceProvider::factory()->create($cid);
    AssignmentSync::attach($a, $a->serviceProviders(), $provider->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);

    Livewire::test('pages::systems.show', ['system' => $a])
        ->set('replaceTargetId', $b->id)
        ->call('replaceSystem')
        ->assertHasNoErrors()
        ->assertRedirect(route('systems.show', $b));

    expect($a->serviceProviders()->count())->toBe(0)
        ->and($b->serviceProviders()->whereKey($provider->id)->exists())->toBeTrue();
});
