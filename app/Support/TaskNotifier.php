<?php

namespace App\Support;

use App\Enums\RaciRole;
use App\Mail\TaskAssignmentMail;
use App\Models\Employee;
use App\Models\HandbookTest;
use App\Models\PreventiveMeasure;
use App\Models\SystemTask;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Verschickt beim Zuweisen einer Präventivmaßnahme, Prüfung oder System-Aufgabe
 * eine E-Mail an die verantwortliche(n) Person(en) – optional mit Kalender-
 * Einladung (.ics). Die Fälligkeits-Reminder bleiben davon unberührt
 * (siehe App\Console\Commands\SendDueReminders).
 */
class TaskNotifier
{
    public static function notifyMeasure(PreventiveMeasure $measure): TaskNotificationResult
    {
        $measure->loadMissing('responsible');

        return self::dispatch(
            recipients: self::single($measure->responsible),
            sourceLabel: 'Präventivmaßnahme',
            title: $measure->title,
            description: $measure->description,
            date: $measure->next_due_at ?? $measure->target_date,
            recurrenceMonths: $measure->interval?->months(),
            intervalLabel: $measure->interval?->label(),
            url: self::safeRoute('preventive-measures.index'),
            uid: 'measure-'.$measure->id.'@planb',
        );
    }

    public static function notifyTest(HandbookTest $test): TaskNotificationResult
    {
        $test->loadMissing('responsible');

        return self::dispatch(
            recipients: self::single($test->responsible),
            sourceLabel: 'Prüfung',
            title: $test->name ?: $test->type->label(),
            description: $test->description,
            date: $test->next_due_at,
            recurrenceMonths: $test->interval?->months(),
            intervalLabel: $test->interval?->label(),
            url: self::safeRoute('handbook-tests.index'),
            uid: 'test-'.$test->id.'@planb',
        );
    }

    public static function notifyTask(SystemTask $task): TaskNotificationResult
    {
        $task->loadMissing('assignees');

        // Bevorzugt die Verantwortlichen (RACI „R"); gibt es keine, alle Zugewiesenen.
        $responsible = $task->assignees->filter(
            fn (Employee $e) => ($e->pivot->raci_role ?? null) === RaciRole::Responsible->value,
        );
        $pool = $responsible->isNotEmpty() ? $responsible : $task->assignees;

        $recipients = $pool
            ->filter(fn (Employee $e) => filled($e->email))
            ->map(fn (Employee $e) => ['name' => $e->fullName(), 'email' => $e->email])
            ->values()
            ->all();

        return self::dispatch(
            recipients: $recipients,
            sourceLabel: 'Aufgabe',
            title: $task->title,
            description: $task->description,
            date: $task->due_date,
            recurrenceMonths: null,
            intervalLabel: null,
            url: self::safeRoute('systems.show', $task->system_id),
            uid: 'task-'.$task->id.'@planb',
        );
    }

    /**
     * @return array<int, array{name: string, email: string}>
     */
    protected static function single(?Employee $employee): array
    {
        if ($employee === null || ! filled($employee->email)) {
            return [];
        }

        return [['name' => $employee->fullName(), 'email' => $employee->email]];
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $recipients
     */
    protected static function dispatch(
        array $recipients,
        string $sourceLabel,
        string $title,
        ?string $description,
        ?DateTimeInterface $date,
        ?int $recurrenceMonths,
        ?string $intervalLabel,
        ?string $url,
        string $uid,
    ): TaskNotificationResult {
        if ($recipients === []) {
            return new TaskNotificationResult(0, []);
        }

        $orgName = Auth::user()?->currentCompany()?->name ?? config('app.name');
        $orgEmail = config('mail.from.address');
        $dueLabel = $date?->format('d.m.Y') ?? 'ohne festes Datum';

        $names = [];

        foreach ($recipients as $recipient) {
            $ics = $date !== null
                ? IcsBuilder::allDayEvent(
                    uid: $uid,
                    summary: $sourceLabel.': '.$title,
                    description: $description,
                    date: $date,
                    recurrenceMonths: $recurrenceMonths,
                    organizerName: $orgName,
                    organizerEmail: $orgEmail,
                    attendeeName: $recipient['name'],
                    attendeeEmail: $recipient['email'],
                )
                : null;

            Mail::to($recipient['email'])->send(new TaskAssignmentMail(
                recipientName: $recipient['name'],
                sourceLabel: $sourceLabel,
                title: $title,
                description: $description,
                dueLabel: $dueLabel,
                intervalLabel: $intervalLabel,
                actionUrl: $url,
                ics: $ics,
            ));

            $names[] = $recipient['name'];
        }

        return new TaskNotificationResult(count($names), $names);
    }

    /**
     * route() kann außerhalb eines Web-Requests (fehlender {current_team}-Default)
     * fehlschlagen; dann wird der Button in der E-Mail einfach weggelassen.
     *
     * @param  array<string, mixed>|string|null  $params
     */
    protected static function safeRoute(string $name, array|string|null $params = []): ?string
    {
        try {
            return route($name, $params ?? []);
        } catch (\Throwable) {
            return null;
        }
    }
}
