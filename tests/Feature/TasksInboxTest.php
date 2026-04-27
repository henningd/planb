<?php

use App\Enums\RaciRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: System}
 */
function bootInboxTenant(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    return [$user->fresh(), $company, $system];
}

test('page lists open tasks scoped to company', function () {
    [$user, $company, $system] = bootInboxTenant();

    SystemTask::factory()->forSystem($system)->create(['title' => 'Eigene Aufgabe']);

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $otherSystem = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $otherCompany->id,
        'name' => 'Fremd',
        'category' => 'geschaeftsbetrieb',
    ]);
    SystemTask::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $otherCompany->id,
        'system_id' => $otherSystem->id,
        'title' => 'Fremde Aufgabe',
    ]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->assertSeeText('Eigene Aufgabe')
        ->assertDontSeeText('Fremde Aufgabe');
});

test('filter by overdue shows only overdue tasks', function () {
    [$user, , $system] = bootInboxTenant();

    SystemTask::factory()->forSystem($system)->create([
        'title' => 'Bereits überfällig',
        'due_date' => now()->subDays(3)->toDateString(),
    ]);
    SystemTask::factory()->forSystem($system)->create([
        'title' => 'Erst nächsten Monat',
        'due_date' => now()->addDays(20)->toDateString(),
    ]);
    SystemTask::factory()->forSystem($system)->create([
        'title' => 'Schon erledigt',
        'due_date' => now()->subDays(5)->toDateString(),
        'completed_at' => now(),
    ]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->set('statusFilter', 'overdue')
        ->assertSeeText('Bereits überfällig')
        ->assertDontSeeText('Erst nächsten Monat')
        ->assertDontSeeText('Schon erledigt');
});

test('filter mine shows tasks assigned to current user via employee email', function () {
    [$user, $company, $system] = bootInboxTenant();

    $me = Employee::factory()->for($company)->create([
        'first_name' => 'Daniel',
        'last_name' => 'Henninger',
        'email' => $user->email,
    ]);
    $other = Employee::factory()->for($company)->create([
        'first_name' => 'Anna',
        'last_name' => 'Andere',
        'email' => 'anna@example.com',
    ]);

    $mine = SystemTask::factory()->forSystem($system)->create(['title' => 'Aufgabe für mich']);
    $theirs = SystemTask::factory()->forSystem($system)->create(['title' => 'Aufgabe für andere']);

    AssignmentSync::attach($mine, $mine->assignees(), $me->id, ['raci_role' => RaciRole::Responsible->value]);
    AssignmentSync::attach($theirs, $theirs->assignees(), $other->id, ['raci_role' => RaciRole::Responsible->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->set('assigneeFilter', 'mine')
        ->assertSeeText('Aufgabe für mich')
        ->assertDontSeeText('Aufgabe für andere');
});

test('toggleTask completes an open task', function () {
    [$user, , $system] = bootInboxTenant();

    $task = SystemTask::factory()->forSystem($system)->create(['title' => 'Backup prüfen']);

    expect($task->completed_at)->toBeNull();

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->call('toggleTask', $task->id);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('page shows counter with open and overdue numbers', function () {
    [$user, , $system] = bootInboxTenant();

    SystemTask::factory()->forSystem($system)->create([
        'title' => 'Offen 1',
        'due_date' => now()->addDays(5)->toDateString(),
    ]);
    SystemTask::factory()->forSystem($system)->create([
        'title' => 'Überfällig 1',
        'due_date' => now()->subDays(2)->toDateString(),
    ]);
    SystemTask::factory()->forSystem($system)->create([
        'title' => 'Erledigt heute',
        'completed_at' => now(),
    ]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->assertSet('openCount', 2)
        ->assertSet('overdueCount', 1)
        ->assertSet('doneTodayCount', 1);
});
