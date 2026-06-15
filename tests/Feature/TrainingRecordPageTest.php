<?php

use App\Enums\PreventiveMeasureInterval;
use App\Enums\TrainingType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function actingUserWithEmployee(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
    ]);

    return [$user->fresh(), $employee];
}

test('the training page lists records of the current company', function () {
    [$user, $employee] = actingUserWithEmployee();

    TrainingRecord::factory()->forEmployee($employee)->create(['topic' => 'Phishing-Awareness']);

    $this->actingAs($user)
        ->get(route('training-records.index'))
        ->assertOk()
        ->assertSee('Phishing-Awareness');
});

test('a training record can be created through the Livewire component', function () {
    [$user, $employee] = actingUserWithEmployee();

    Livewire::actingAs($user)
        ->test('pages::training-records.index')
        ->set('employee_id', $employee->id)
        ->set('topic', 'Leitungsschulung NIS2')
        ->set('type', TrainingType::Leadership->value)
        ->set('completed_at', now()->toDateString())
        ->set('interval', PreventiveMeasureInterval::Yearly->value)
        ->call('save')
        ->assertHasNoErrors();

    $record = TrainingRecord::firstWhere('topic', 'Leitungsschulung NIS2');

    expect($record)->not->toBeNull()
        ->and($record->employee_id)->toBe($employee->id)
        ->and($record->company_id)->toBe($employee->company_id)
        ->and($record->type)->toBe(TrainingType::Leadership)
        ->and($record->next_due_at)->not->toBeNull(); // aus Intervall abgeleitet
});

test('marking a recurring training as completed advances the next due date', function () {
    [$user, $employee] = actingUserWithEmployee();

    $record = TrainingRecord::factory()->forEmployee($employee)->overdue()->create();

    Livewire::actingAs($user)
        ->test('pages::training-records.index')
        ->call('markCompleted', $record->id);

    $record->refresh();

    expect($record->completed_at)->not->toBeNull()
        ->and($record->next_due_at->isFuture())->toBeTrue();
});
