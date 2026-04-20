<?php

use App\Enums\RaciRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: System}
 */
function bootSystemTaskTenant(): array
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

test('add task creates a system task with title and description', function () {
    [$user, $company, $system] = bootSystemTaskTenant();

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', 'Backup monatlich prüfen')
        ->set('newTaskDescription', 'Samstag nachts, Datensicherung NAS gegen Cloud vergleichen.')
        ->set('newTaskDueDate', '2026-06-01')
        ->call('addTask');

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('system_id', $system->id)
        ->first();

    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Backup monatlich prüfen')
        ->and($task->description)->toBe('Samstag nachts, Datensicherung NAS gegen Cloud vergleichen.')
        ->and($task->due_date->toDateString())->toBe('2026-06-01')
        ->and($task->completed_at)->toBeNull()
        ->and($task->company_id)->toBe($company->id);
});

test('add task stores multiple assignees with RACI roles', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $a = Employee::factory()->for($system->company)->create(['first_name' => 'Alice', 'last_name' => 'A']);
    $b = Employee::factory()->for($system->company)->create(['first_name' => 'Bob', 'last_name' => 'B']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', 'Backup prüfen')
        ->set('newTaskAssignees', [
            ['employee_id' => $a->id, 'raci_role' => RaciRole::Accountable->value],
            ['employee_id' => $b->id, 'raci_role' => RaciRole::Responsible->value],
        ])
        ->call('addTask');

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('system_id', $system->id)
        ->with('assignees')
        ->first();

    expect($task->assignees)->toHaveCount(2);

    $byId = $task->assignees->keyBy('id');

    expect($byId[$a->id]->pivot->raci_role)->toBe('A')
        ->and($byId[$b->id]->pivot->raci_role)->toBe('R');
});

test('add task stores multiple provider assignees with RACI roles', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $acme = ServiceProvider::factory()->for($system->company)->create(['name' => 'ACME-IT']);
    $beta = ServiceProvider::factory()->for($system->company)->create(['name' => 'Beta-Hoster']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', 'Recovery-Test')
        ->set('newTaskProviders', [
            ['provider_id' => $acme->id, 'raci_role' => RaciRole::Responsible->value],
            ['provider_id' => $beta->id, 'raci_role' => RaciRole::Consulted->value],
        ])
        ->call('addTask');

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('system_id', $system->id)
        ->with('providerAssignees')
        ->first();

    expect($task->providerAssignees)->toHaveCount(2);

    $byId = $task->providerAssignees->keyBy('id');

    expect($byId[$acme->id]->pivot->raci_role)->toBe('R')
        ->and($byId[$beta->id]->pivot->raci_role)->toBe('C');
});

test('addNewTaskProvider appends and remove removes by index', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $component = Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('addNewTaskProvider')
        ->call('addNewTaskProvider');

    expect($component->get('newTaskProviders'))->toHaveCount(2);

    $component->call('removeNewTaskProvider', 0);

    expect($component->get('newTaskProviders'))->toHaveCount(1);
});

test('addNewTaskAssignee appends an empty row and remove removes by index', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $component = Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('addNewTaskAssignee')
        ->call('addNewTaskAssignee');

    expect($component->get('newTaskAssignees'))->toHaveCount(2);

    $component->call('removeNewTaskAssignee', 0);

    expect($component->get('newTaskAssignees'))->toHaveCount(1);
});

test('add task requires a title', function () {
    [$user, , $system] = bootSystemTaskTenant();

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', '')
        ->call('addTask')
        ->assertHasErrors('newTaskTitle');
});

test('toggle task marks done and re-opens', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $task = SystemTask::factory()->forSystem($system)->create();

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('toggleTask', $task->id);

    expect($task->fresh()->completed_at)->not->toBeNull();

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('toggleTask', $task->id);

    expect($task->fresh()->completed_at)->toBeNull();
});

test('tasks are sorted open first by due date then completed at the bottom', function () {
    [$user, , $system] = bootSystemTaskTenant();

    SystemTask::factory()->forSystem($system)->completed()->create(['title' => 'Z-Done', 'due_date' => '2026-05-01']);
    SystemTask::factory()->forSystem($system)->create(['title' => 'Late', 'due_date' => '2026-08-01']);
    SystemTask::factory()->forSystem($system)->create(['title' => 'Early', 'due_date' => '2026-05-15']);
    SystemTask::factory()->forSystem($system)->create(['title' => 'NoDue', 'due_date' => null]);

    $component = Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system]);

    $titles = $component->instance()->tasks->pluck('title')->all();

    expect($titles)->toBe(['Early', 'Late', 'NoDue', 'Z-Done']);
});

test('delete removes the task', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $task = SystemTask::factory()->forSystem($system)->create();

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('deleteTask', $task->id);

    expect(SystemTask::withoutGlobalScope(CurrentCompanyScope::class)->find($task->id))->toBeNull();
});

test('edit modal updates task fields and re-syncs assignees and providers', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $task = SystemTask::factory()->forSystem($system)->create(['title' => 'Old']);
    $old = Employee::factory()->for($system->company)->create();
    $newEmployee = Employee::factory()->for($system->company)->create();
    $newProvider = ServiceProvider::factory()->for($system->company)->create();

    $task->assignees()->attach($old->id, ['raci_role' => RaciRole::Responsible->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('openEditTask', $task->id)
        ->set('editTitle', 'New title')
        ->set('editDueDate', '2026-07-01')
        ->set('editAssignees', [
            ['employee_id' => $newEmployee->id, 'raci_role' => RaciRole::Accountable->value],
        ])
        ->set('editProviders', [
            ['provider_id' => $newProvider->id, 'raci_role' => RaciRole::Informed->value],
        ])
        ->call('saveEditTask');

    $task->refresh()->load(['assignees', 'providerAssignees']);

    expect($task->title)->toBe('New title')
        ->and($task->due_date->toDateString())->toBe('2026-07-01')
        ->and($task->assignees->pluck('id')->all())->toBe([$newEmployee->id])
        ->and($task->assignees->first()->pivot->raci_role)->toBe('A')
        ->and($task->providerAssignees->pluck('id')->all())->toBe([$newProvider->id])
        ->and($task->providerAssignees->first()->pivot->raci_role)->toBe('I');
});

test('show page renders tasks section with existing tasks and RACI labels', function () {
    [$user, , $system] = bootSystemTaskTenant();

    $person = Employee::factory()->for($system->company)->create(['first_name' => 'Sabine', 'last_name' => 'Ruf']);
    $task = SystemTask::factory()->forSystem($system)->create(['title' => 'Backup prüfen', 'due_date' => '2026-09-01']);
    $task->assignees()->attach($person->id, ['raci_role' => RaciRole::Accountable->value]);

    $this->actingAs($user)
        ->get(route('systems.show', ['system' => $system]))
        ->assertOk()
        ->assertSee('Aufgaben')
        ->assertSee('Backup prüfen')
        ->assertSee('Sabine Ruf');
});
