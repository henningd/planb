<?php

use App\Enums\CrisisRole;
use App\Mail\ResourceDueReminder;
use App\Mail\TestDueReminder;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\Employee;
use App\Models\HandbookTest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends test reminder when next_due_at is within 14 days and updates last_reminder_sent_at', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $responsible = Employee::factory()->for($company)->create(['email' => 'tester@example.test']);

    $test = HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->addDays(7)->toDateString(),
        'responsible_employee_id' => $responsible->id,
        'last_reminder_sent_at' => null,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertSent(TestDueReminder::class, function (TestDueReminder $mail) use ($test, $responsible) {
        return $mail->test->is($test) && $mail->hasTo($responsible->email);
    });

    expect($test->fresh()->last_reminder_sent_at)->not->toBeNull();
});

test('sends resource reminder to emergency officer when next_check_at is within 14 days', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $officer = Employee::factory()->for($company)->withCrisisRole(CrisisRole::EmergencyOfficer)->create([
        'email' => 'officer@example.test',
    ]);

    $resource = EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addDays(5)->toDateString(),
        'last_reminder_sent_at' => null,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertSent(ResourceDueReminder::class, function (ResourceDueReminder $mail) use ($resource, $officer) {
        return $mail->resource->is($resource) && $mail->hasTo($officer->email);
    });

    expect($resource->fresh()->last_reminder_sent_at)->not->toBeNull();
});

test('does not resend reminder if one was sent within the last 14 days', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $responsible = Employee::factory()->for($company)->create(['email' => 'tester@example.test']);

    HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->addDays(7)->toDateString(),
        'responsible_employee_id' => $responsible->id,
        'last_reminder_sent_at' => now()->subDays(3),
    ]);

    $officer = Employee::factory()->for($company)->withCrisisRole(CrisisRole::EmergencyOfficer)->create([
        'email' => 'officer@example.test',
    ]);
    EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addDays(5)->toDateString(),
        'last_reminder_sent_at' => now()->subDays(2),
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertNothingSent();
});

test('skips test without responsible employee but command exits cleanly', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->addDays(3)->toDateString(),
        'responsible_employee_id' => null,
        'last_reminder_sent_at' => null,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertNothingSent();
});

test('skips resource reminders when no emergency officer exists', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addDays(3)->toDateString(),
        'last_reminder_sent_at' => null,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertNothingSent();
});

test('does not send reminder when next_due_at is more than 14 days in the future', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $responsible = Employee::factory()->for($company)->create(['email' => 'tester@example.test']);

    HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->addDays(30)->toDateString(),
        'responsible_employee_id' => $responsible->id,
        'last_reminder_sent_at' => null,
    ]);

    $officer = Employee::factory()->for($company)->withCrisisRole(CrisisRole::EmergencyOfficer)->create([
        'email' => 'officer@example.test',
    ]);
    EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addDays(40)->toDateString(),
        'last_reminder_sent_at' => null,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertNothingSent();
});

test('respects multi-tenant isolation: other companies do not receive reminders', function () {
    Mail::fake();

    $user = User::factory()->create();
    $companyA = Company::factory()->for($user->currentTeam)->create();
    $responsibleA = Employee::factory()->for($companyA)->create(['email' => 'a@example.test']);
    $testA = HandbookTest::factory()->for($companyA)->create([
        'next_due_at' => now()->addDays(7)->toDateString(),
        'responsible_employee_id' => $responsibleA->id,
        'last_reminder_sent_at' => null,
    ]);

    $companyB = Company::factory()->for(Team::factory())->create();
    $responsibleB = Employee::factory()->for($companyB)->create(['email' => 'b@example.test']);
    HandbookTest::factory()->for($companyB)->create([
        'next_due_at' => now()->addDays(60)->toDateString(),
        'responsible_employee_id' => $responsibleB->id,
        'last_reminder_sent_at' => null,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Mail::assertSent(TestDueReminder::class, 1);
    Mail::assertSent(TestDueReminder::class, function (TestDueReminder $mail) use ($testA, $responsibleA) {
        return $mail->test->is($testA) && $mail->hasTo($responsibleA->email);
    });
});

test('second run on the same day is idempotent and does not resend', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $responsible = Employee::factory()->for($company)->create(['email' => 'tester@example.test']);

    HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->addDays(7)->toDateString(),
        'responsible_employee_id' => $responsible->id,
        'last_reminder_sent_at' => null,
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);
    Mail::assertSent(TestDueReminder::class, 1);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);
    Mail::assertSent(TestDueReminder::class, 1);
});
