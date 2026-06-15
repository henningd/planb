<?php

use App\Enums\PreventiveMeasureInterval;
use App\Enums\PreventiveMeasureStatus;
use App\Mail\MeasureDueReminder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PreventiveMeasure;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the inbox lists due recurring preventive measures and can mark them executed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create(['company_id' => $company->id]);

    $measure = PreventiveMeasure::factory()->forSystem($system)->create([
        'title' => 'Backup-Rückspieltest',
        'interval' => PreventiveMeasureInterval::Quarterly,
        'status' => PreventiveMeasureStatus::Active,
        'next_due_at' => now()->subDay()->format('Y-m-d'),
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::tasks-inbox.index')
        ->assertSee('Backup-Rückspieltest')
        ->call('markMeasureExecuted', $measure->id);

    expect($measure->fresh()->next_due_at->isFuture())->toBeTrue();
});

test('the reminder command emails the responsible person for a due measure and is idempotent', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create(['company_id' => $company->id]);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'email' => 'praevention@example.test',
    ]);

    PreventiveMeasure::factory()->forSystem($system)->create([
        'interval' => PreventiveMeasureInterval::Monthly,
        'status' => PreventiveMeasureStatus::Active,
        'next_due_at' => now()->addDays(5)->format('Y-m-d'),
        'responsible_employee_id' => $employee->id,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);
    $this->artisan('app:send-due-reminders')->assertExitCode(0); // zweiter Lauf: kein erneuter Versand

    Mail::assertSent(MeasureDueReminder::class, 1);
    Mail::assertSent(MeasureDueReminder::class, fn (MeasureDueReminder $mail) => $mail->hasTo('praevention@example.test'));
});

test('the inbox excludes measures when the feature is disabled', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create(['company_id' => $company->id]);

    PreventiveMeasure::factory()->forSystem($system)->create([
        'title' => 'Versteckte Inbox-Maßnahme',
        'interval' => PreventiveMeasureInterval::Monthly,
        'status' => PreventiveMeasureStatus::Active,
        'next_due_at' => now()->subDay()->format('Y-m-d'),
    ]);

    config(['features.preventive_measures' => false]);

    Livewire::actingAs($user->fresh())
        ->test('pages::tasks-inbox.index')
        ->assertDontSee('Versteckte Inbox-Maßnahme');
});

test('the reminder command does not send measure reminders when the feature is disabled', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create(['company_id' => $company->id]);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'email' => 'p@example.test']);

    PreventiveMeasure::factory()->forSystem($system)->create([
        'interval' => PreventiveMeasureInterval::Monthly,
        'status' => PreventiveMeasureStatus::Active,
        'next_due_at' => now()->addDays(3)->format('Y-m-d'),
        'responsible_employee_id' => $employee->id,
    ]);

    config(['features.preventive_measures' => false]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertNothingSent();
});

test('the reminder command skips paused measures', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create(['company_id' => $company->id]);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'email' => 'p@example.test']);

    PreventiveMeasure::factory()->forSystem($system)->create([
        'interval' => PreventiveMeasureInterval::Monthly,
        'status' => PreventiveMeasureStatus::Paused,
        'next_due_at' => now()->addDays(2)->format('Y-m-d'),
        'responsible_employee_id' => $employee->id,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertNothingSent();
});
