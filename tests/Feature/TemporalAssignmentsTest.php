<?php

use App\Enums\RaciRole;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function bootTemporalTenant(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('detach via AssignmentSync sets removed_at instead of deleting the row', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $role = Role::factory()->for($company)->create();
    $emp = Employee::factory()->for($company)->create();

    AssignmentSync::attach($role, $role->employees(), $emp->id);
    AssignmentSync::detach($role, $role->employees(), $emp->id);

    expect($role->employees()->count())->toBe(0);
    expect($role->employeesHistory()->count())->toBe(1);

    $row = DB::table('employee_role')->first();
    expect($row->removed_at)->not->toBeNull();
    expect($row->removed_by_user_id)->toBe($user->id);
    expect($row->assigned_by_user_id)->toBe($user->id);
});

test('point-in-time query returns the assignees that were active at that moment', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $role = Role::factory()->for($company)->create();
    $alice = Employee::factory()->for($company)->create(['first_name' => 'Alice']);
    $bob = Employee::factory()->for($company)->create(['first_name' => 'Bob']);

    Carbon::setTestNow('2026-01-01 09:00');
    AssignmentSync::attach($role, $role->employees(), $alice->id);

    Carbon::setTestNow('2026-02-01 09:00');
    AssignmentSync::attach($role, $role->employees(), $bob->id);

    Carbon::setTestNow('2026-03-01 09:00');
    AssignmentSync::detach($role, $role->employees(), $alice->id);

    Carbon::setTestNow();

    $atFebruary = $role->employeesHistory()
        ->wherePivot('assigned_at', '<=', '2026-02-15 00:00')
        ->where(function ($q) {
            $q->whereNull('employee_role.removed_at')
                ->orWhere('employee_role.removed_at', '>', '2026-02-15 00:00');
        })
        ->pluck('employees.id')
        ->all();

    expect($atFebruary)->toHaveCount(2)
        ->and($atFebruary)->toContain($alice->id, $bob->id);

    $atApril = $role->employeesHistory()
        ->wherePivot('assigned_at', '<=', '2026-04-01 00:00')
        ->where(function ($q) {
            $q->whereNull('employee_role.removed_at')
                ->orWhere('employee_role.removed_at', '>', '2026-04-01 00:00');
        })
        ->pluck('employees.id')
        ->all();

    expect($atApril)->toBe([$bob->id]);
});

test('changing raci_role rotates the row and preserves the previous stint in history', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create();
    $emp = Employee::factory()->for($company)->create();

    AssignmentSync::sync($system, $system->employees(), [
        $emp->id => ['raci_role' => RaciRole::Consulted->value, 'sort' => 0],
    ]);

    AssignmentSync::sync($system, $system->employees(), [
        $emp->id => ['raci_role' => RaciRole::Responsible->value, 'sort' => 0],
    ]);

    $rows = DB::table('employee_system')->where('employee_id', $emp->id)->get();
    expect($rows)->toHaveCount(2);

    $ended = $rows->firstWhere('raci_role', RaciRole::Consulted->value);
    $current = $rows->firstWhere('raci_role', RaciRole::Responsible->value);

    expect($ended->removed_at)->not->toBeNull()
        ->and($current->removed_at)->toBeNull();

    expect($system->employees()->count())->toBe(1)
        ->and($system->employees->first()->pivot->raci_role)->toBe(RaciRole::Responsible->value);
});

test('changing sort or note does not rotate the row (no history bloat)', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create();
    $emp = Employee::factory()->for($company)->create();

    AssignmentSync::sync($system, $system->employees(), [
        $emp->id => ['raci_role' => RaciRole::Responsible->value, 'sort' => 0, 'note' => 'erste Notiz'],
    ]);

    AssignmentSync::sync($system, $system->employees(), [
        $emp->id => ['raci_role' => RaciRole::Responsible->value, 'sort' => 5, 'note' => 'angepasste Notiz'],
    ]);

    expect(DB::table('employee_system')->where('employee_id', $emp->id)->count())->toBe(1);

    $row = DB::table('employee_system')->where('employee_id', $emp->id)->first();
    expect($row->sort)->toBe(5)
        ->and($row->note)->toBe('angepasste Notiz');
});

test('audit log entries are created for assignments and removals', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $role = Role::factory()->for($company)->create(['name' => 'Buchhaltung']);
    $emp = Employee::factory()->for($company)->create([
        'first_name' => 'Erika',
        'last_name' => 'Beispiel',
    ]);

    AssignmentSync::attach($role, $role->employees(), $emp->id);
    AssignmentSync::detach($role, $role->employees(), $emp->id);

    $entries = AuditLogEntry::withoutGlobalScopes()
        ->where('entity_type', 'Role')
        ->where('entity_id', $role->id)
        ->where('action', 'like', 'employees.%')
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();

    expect($entries)->toHaveCount(2);
    expect($entries[0]->action)->toBe('employees.assigned');
    expect($entries[1]->action)->toBe('employees.unassigned');
    expect($entries[0]->user_id)->toBe($user->id);
    expect($entries[0]->changes['related_id'])->toBe($emp->id);
    expect($entries[0]->changes['related_label'])->toBe('Erika Beispiel');
});

test('partial unique index enforces at most one active row per pair', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $role = Role::factory()->for($company)->create();
    $emp = Employee::factory()->for($company)->create();

    AssignmentSync::attach($role, $role->employees(), $emp->id);
    AssignmentSync::detach($role, $role->employees(), $emp->id);
    AssignmentSync::attach($role, $role->employees(), $emp->id);
    AssignmentSync::detach($role, $role->employees(), $emp->id);
    AssignmentSync::attach($role, $role->employees(), $emp->id);

    expect($role->employees()->count())->toBe(1);
    expect($role->employeesHistory()->count())->toBe(3);
});

test('service provider on system is soft-removed and stays in history', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create();
    $provider = ServiceProvider::factory()->for($company)->create(['name' => 'IT-Service GmbH']);

    AssignmentSync::attach($system, $system->serviceProviders(), $provider->id, [
        'raci_role' => RaciRole::Responsible->value,
        'note' => 'Hauptansprechpartner',
    ]);
    AssignmentSync::detach($system, $system->serviceProviders(), $provider->id);

    expect($system->serviceProviders()->count())->toBe(0);
    expect($system->serviceProvidersHistory()->count())->toBe(1);

    $row = DB::table('service_provider_system')->first();
    expect($row->removed_at)->not->toBeNull()
        ->and($row->note)->toBe('Hauptansprechpartner');

    $audit = AuditLogEntry::withoutGlobalScopes()
        ->where('entity_type', 'System')
        ->where('entity_id', $system->id)
        ->where('action', 'like', 'serviceProviders.%')
        ->pluck('action')
        ->all();
    expect($audit)->toContain('serviceProviders.assigned', 'serviceProviders.unassigned');
});

test('SystemTask provider assignees rotate on raci change and stay in history', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create();
    $task = SystemTask::factory()->forSystem($system)->create();
    $provider = ServiceProvider::factory()->for($company)->create();

    AssignmentSync::sync($task, $task->providerAssignees(), [
        $provider->id => ['raci_role' => RaciRole::Consulted->value],
    ]);
    AssignmentSync::sync($task, $task->providerAssignees(), [
        $provider->id => ['raci_role' => RaciRole::Responsible->value],
    ]);

    expect(DB::table('service_provider_system_task')->where('service_provider_id', $provider->id)->count())->toBe(2);
    expect($task->providerAssignees()->count())->toBe(1);
    expect($task->providerAssignees->first()->pivot->raci_role)->toBe(RaciRole::Responsible->value);
    expect($task->providerAssigneesHistory()->count())->toBe(2);
});

test('SystemTask role assignees keep history when rotated', function () {
    [$user, $company] = bootTemporalTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create();
    $task = SystemTask::factory()->forSystem($system)->create();
    $role = Role::factory()->for($company)->create();

    AssignmentSync::sync($task, $task->roleAssignees(), [
        $role->id => ['raci_role' => RaciRole::Accountable->value, 'sort' => 0],
    ]);
    AssignmentSync::sync($task, $task->roleAssignees(), []);

    expect($task->roleAssignees()->count())->toBe(0);
    expect($task->roleAssigneesHistory()->count())->toBe(1);

    $audit = AuditLogEntry::withoutGlobalScopes()
        ->where('entity_type', 'SystemTask')
        ->where('entity_id', $task->id)
        ->pluck('action')
        ->all();
    expect($audit)->toContain('roleAssignees.assigned', 'roleAssignees.unassigned');
});
