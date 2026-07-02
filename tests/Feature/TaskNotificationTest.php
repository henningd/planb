<?php

use App\Enums\HandbookTestInterval;
use App\Enums\HandbookTestType;
use App\Enums\PreventiveMeasureInterval;
use App\Enums\SystemType;
use App\Mail\TaskAssignmentMail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\System;
use App\Models\User;
use App\Support\IcsBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: System}
 */
function notifyTenant(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create([
        'company_id' => $company->id,
        'system_type' => SystemType::Server,
    ]);

    return [$user->fresh(), $company, $system];
}

function employeeWithEmail(Company $company, string $email = 'anna@example.test'): Employee
{
    return Employee::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'email' => $email,
    ]);
}

it('builds a recurring all-day ICS event with attendee and RRULE', function () {
    $ics = IcsBuilder::allDayEvent(
        uid: 'measure-1@planb',
        summary: 'Präventivmaßnahme: Backup-Test',
        description: 'Kurz; mit, Sonderzeichen',
        date: new DateTimeImmutable('2026-08-01'),
        recurrenceMonths: 3,
        organizerName: 'Muster GmbH',
        organizerEmail: 'org@example.test',
        attendeeName: 'Anna Beispiel',
        attendeeEmail: 'anna@example.test',
    );

    expect($ics)->toContain('BEGIN:VCALENDAR')
        ->and($ics)->toContain('BEGIN:VEVENT')
        ->and($ics)->toContain('UID:measure-1@planb')
        ->and($ics)->toContain('DTSTART;VALUE=DATE:20260801')
        ->and($ics)->toContain('DTEND;VALUE=DATE:20260802')
        ->and($ics)->toContain('RRULE:FREQ=MONTHLY;INTERVAL=3')
        ->and($ics)->toContain('ATTENDEE')
        ->and($ics)->toContain('mailto:anna@example.test')
        ->and($ics)->toContain('DESCRIPTION:Kurz\\; mit\\, Sonderzeichen');
});

it('builds a single ICS event without RRULE', function () {
    $ics = IcsBuilder::allDayEvent(
        uid: 'task-1@planb',
        summary: 'Aufgabe: Einmalig',
        description: null,
        date: new DateTimeImmutable('2026-08-01'),
    );

    expect($ics)->not->toContain('RRULE');
});

it('emails the responsible person with a recurring calendar invite when a measure is created', function () {
    Mail::fake();
    [$user, $company, $system] = notifyTenant();
    $anna = employeeWithEmail($company);

    Livewire::actingAs($user)
        ->test('pages::preventive-measures.index')
        ->set('system_id', $system->id)
        ->set('title', 'Backup-Rückspieltest')
        ->set('interval', PreventiveMeasureInterval::Monthly->value)
        ->set('responsible_employee_id', $anna->id)
        ->set('notifyResponsible', true)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertSent(TaskAssignmentMail::class, function (TaskAssignmentMail $mail) use ($anna) {
        return $mail->hasTo($anna->email)
            && $mail->sourceLabel === 'Präventivmaßnahme'
            && $mail->ics !== null
            && str_contains($mail->ics, 'RRULE:FREQ=MONTHLY;INTERVAL=1');
    });
});

it('does not email when the responsible person has no email address', function () {
    Mail::fake();
    [$user, $company, $system] = notifyTenant();
    $noEmail = Employee::factory()->create(['company_id' => $company->id, 'email' => null]);

    Livewire::actingAs($user)
        ->test('pages::preventive-measures.index')
        ->set('system_id', $system->id)
        ->set('title', 'Ohne E-Mail')
        ->set('responsible_employee_id', $noEmail->id)
        ->set('notifyResponsible', true)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertNothingSent();
});

it('does not email when the notify checkbox is unchecked', function () {
    Mail::fake();
    [$user, $company, $system] = notifyTenant();
    $anna = employeeWithEmail($company);

    Livewire::actingAs($user)
        ->test('pages::preventive-measures.index')
        ->set('system_id', $system->id)
        ->set('title', 'Nicht benachrichtigen')
        ->set('interval', PreventiveMeasureInterval::Monthly->value)
        ->set('responsible_employee_id', $anna->id)
        ->set('notifyResponsible', false)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertNothingSent();
});

it('emails the responsible person when a handbook test is created', function () {
    Mail::fake();
    [$user, $company] = notifyTenant();
    $anna = employeeWithEmail($company);

    Livewire::actingAs($user)
        ->test('pages::handbook-tests.index')
        ->set('type', HandbookTestType::Tabletop->value)
        ->set('interval', HandbookTestInterval::Yearly->value)
        ->set('next_due_at', '2027-01-15')
        ->set('responsible_employee_id', $anna->id)
        ->set('notifyResponsible', true)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertSent(TaskAssignmentMail::class, function (TaskAssignmentMail $mail) use ($anna) {
        return $mail->hasTo($anna->email)
            && $mail->sourceLabel === 'Prüfung'
            && $mail->ics !== null;
    });
});

it('emails the responsible assignee when a system task is created', function () {
    Mail::fake();
    [$user, $company, $system] = notifyTenant();
    $anna = employeeWithEmail($company);

    Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', 'Quartals-Prüfung durchführen')
        ->set('newTaskDueDate', '2026-09-30')
        ->set('newTaskAssignees', [
            ['employee_id' => $anna->id, 'raci_role' => 'R', 'is_deputy' => false],
        ])
        ->set('notifyTaskResponsible', true)
        ->call('addTask')
        ->assertHasNoErrors();

    Mail::assertSent(TaskAssignmentMail::class, function (TaskAssignmentMail $mail) use ($anna) {
        return $mail->hasTo($anna->email)
            && $mail->sourceLabel === 'Aufgabe'
            && $mail->ics !== null;
    });
});

it('omits the calendar invite when a system task has no due date', function () {
    Mail::fake();
    [$user, $company, $system] = notifyTenant();
    $anna = employeeWithEmail($company);

    Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->set('newTaskTitle', 'Ohne Fälligkeit')
        ->set('newTaskAssignees', [
            ['employee_id' => $anna->id, 'raci_role' => 'R', 'is_deputy' => false],
        ])
        ->call('addTask')
        ->assertHasNoErrors();

    Mail::assertSent(TaskAssignmentMail::class, fn (TaskAssignmentMail $mail) => $mail->ics === null);
});
