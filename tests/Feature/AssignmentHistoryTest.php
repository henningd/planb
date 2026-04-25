<?php

use App\Enums\RaciRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Support\AssignmentHistory;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function bootHistoryTenant(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('forSystem returns active and removed stints across all pivot kinds', function () {
    [$user, $company] = bootHistoryTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create(['name' => 'ERP']);
    $alice = Employee::factory()->for($company)->create(['first_name' => 'Alice', 'last_name' => 'A']);
    $role = Role::factory()->for($company)->create(['name' => 'Buchhaltung']);
    $provider = ServiceProvider::factory()->for($company)->create(['name' => 'IT-Service']);
    $task = SystemTask::factory()->forSystem($system)->create(['title' => 'Backup prüfen']);
    $bob = Employee::factory()->for($company)->create(['first_name' => 'Bob', 'last_name' => 'B']);

    AssignmentSync::attach($system, $system->employees(), $alice->id, ['raci_role' => RaciRole::Responsible->value]);
    AssignmentSync::attach($system, $system->roles(), $role->id, ['raci_role' => RaciRole::Accountable->value]);
    AssignmentSync::attach($system, $system->serviceProviders(), $provider->id, ['raci_role' => RaciRole::Consulted->value]);
    AssignmentSync::attach($task, $task->assignees(), $bob->id, ['raci_role' => RaciRole::Responsible->value]);
    AssignmentSync::detach($system, $system->employees(), $alice->id);

    $history = AssignmentHistory::forSystem($system->fresh());

    expect($history)->toHaveCount(4);

    $byKind = $history->groupBy('kind');
    expect($byKind->keys()->all())->toContain('employee', 'role', 'provider');

    $aliceRow = $history->firstWhere('target_label', 'Alice A');
    expect($aliceRow['removed_at'])->not->toBeNull()
        ->and($aliceRow['removed_by'])->toBe($user->name)
        ->and($aliceRow['scope'])->toBe('system');

    $roleRow = $history->firstWhere('target_label', 'Buchhaltung');
    expect($roleRow['removed_at'])->toBeNull()
        ->and($roleRow['raci_role'])->toBe(RaciRole::Accountable->value);

    $taskRow = $history->firstWhere('target_label', 'Bob B');
    expect($taskRow['scope'])->toBe('task')
        ->and($taskRow['scope_label'])->toBe('Backup prüfen');
});

test('atMoment returns only stints active at the given timestamp', function () {
    [$user, $company] = bootHistoryTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create();
    $alice = Employee::factory()->for($company)->create(['first_name' => 'Alice', 'last_name' => 'A']);
    $bob = Employee::factory()->for($company)->create(['first_name' => 'Bob', 'last_name' => 'B']);

    Carbon::setTestNow('2026-01-10 09:00');
    AssignmentSync::attach($system, $system->employees(), $alice->id, ['raci_role' => RaciRole::Responsible->value]);

    Carbon::setTestNow('2026-02-10 09:00');
    AssignmentSync::attach($system, $system->employees(), $bob->id, ['raci_role' => RaciRole::Accountable->value]);

    Carbon::setTestNow('2026-03-15 09:00');
    AssignmentSync::detach($system, $system->employees(), $alice->id);

    Carbon::setTestNow();

    $history = AssignmentHistory::forSystem($system->fresh());

    $atFebruary = AssignmentHistory::atMoment($history, Carbon::parse('2026-02-15 12:00'));
    $atFebNames = $atFebruary->pluck('target_label')->all();
    expect($atFebNames)->toContain('Alice A', 'Bob B');

    $atApril = AssignmentHistory::atMoment($history, Carbon::parse('2026-04-01 09:00'));
    $atAprilNames = $atApril->pluck('target_label')->all();
    expect($atAprilNames)->toBe(['Bob B']);

    $beforeAny = AssignmentHistory::atMoment($history, Carbon::parse('2025-12-01 00:00'));
    expect($beforeAny->all())->toBe([]);
});

test('system show page renders the history tab and reacts to date filter', function () {
    [$user, $company] = bootHistoryTenant();
    $this->actingAs($user);

    $system = System::factory()->for($company)->create(['name' => 'ERP']);
    $alice = Employee::factory()->for($company)->create(['first_name' => 'Alice', 'last_name' => 'Active']);
    $bob = Employee::factory()->for($company)->create(['first_name' => 'Bob', 'last_name' => 'Removed']);

    Carbon::setTestNow('2026-02-01 09:00');
    AssignmentSync::attach($system, $system->employees(), $alice->id, ['raci_role' => RaciRole::Responsible->value]);
    AssignmentSync::attach($system, $system->employees(), $bob->id, ['raci_role' => RaciRole::Consulted->value]);

    Carbon::setTestNow('2026-03-01 09:00');
    AssignmentSync::detach($system, $system->employees(), $bob->id);

    Carbon::setTestNow();

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->assertSeeText('Alice Active')
        ->assertSeeText('Bob Removed')
        ->set('historyDate', '2026-02-15')
        ->assertSeeText('Bob Removed')
        ->set('historyDate', '2026-04-01')
        ->assertSeeText('Alice Active')
        ->assertDontSeeText('Bob Removed');
});
