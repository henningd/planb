<?php

namespace App\Console\Commands;

use App\Enums\CrisisRole;
use App\Mail\ResourceDueReminder;
use App\Mail\TestDueReminder;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\Employee;
use App\Models\HandbookTest;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-due-reminders')]
#[Description('Versendet pro Mandant Mail-Reminder für anstehende Handbuch-Tests und Notfall-Ressourcen-Prüfungen, die innerhalb der nächsten 14 Tage fällig sind. Idempotent: pro Eintrag wird höchstens alle 14 Tage ein Reminder verschickt.')]
class SendDueReminders extends Command
{
    /**
     * Reminder-Vorlauf in Tagen: Eintrag wird angezeigt, sobald Fälligkeit
     * weniger als so viele Tage entfernt ist.
     */
    protected const LEAD_DAYS = 14;

    public function handle(): int
    {
        $now = Carbon::now();
        $horizon = $now->copy()->addDays(self::LEAD_DAYS)->endOfDay();

        $companies = Company::withoutGlobalScope(CurrentCompanyScope::class)->get();

        $sent = 0;

        foreach ($companies as $company) {
            $sent += $this->sendTestReminders($company, $horizon);
            $sent += $this->sendResourceReminders($company, $horizon, $now);
        }

        $this->info("Fertig. {$sent} Reminder verschickt.");

        return self::SUCCESS;
    }

    protected function sendTestReminders(Company $company, Carbon $horizon): int
    {
        $tests = HandbookTest::withoutGlobalScope(CurrentCompanyScope::class)
            ->with('responsible')
            ->where('company_id', $company->id)
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', $horizon)
            ->get();

        $count = 0;

        foreach ($tests as $test) {
            if (! $this->shouldSendForTest($test)) {
                continue;
            }

            $email = $test->responsible?->email;
            if (! $email) {
                continue;
            }

            Mail::to($email)->send(new TestDueReminder($test));

            $test->forceFill(['last_reminder_sent_at' => Carbon::now()])->save();

            $this->line("→ [{$company->name}] Test-Reminder an {$email}");
            $count++;
        }

        return $count;
    }

    protected function sendResourceReminders(Company $company, Carbon $horizon, Carbon $now): int
    {
        $resources = EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('next_check_at')
            ->where('next_check_at', '<=', $horizon)
            ->get();

        if ($resources->isEmpty()) {
            return 0;
        }

        $officer = Employee::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->where('crisis_role', CrisisRole::EmergencyOfficer->value)
            ->where('is_crisis_deputy', false)
            ->first();

        if (! $officer || ! $officer->email) {
            return 0;
        }

        $count = 0;

        foreach ($resources as $resource) {
            if (! $this->shouldSendForResource($resource)) {
                continue;
            }

            Mail::to($officer->email)->send(new ResourceDueReminder($resource));

            $resource->forceFill(['last_reminder_sent_at' => $now])->save();

            $this->line("→ [{$company->name}] Ressourcen-Reminder an {$officer->email}");
            $count++;
        }

        return $count;
    }

    protected function shouldSendForTest(HandbookTest $test): bool
    {
        if ($test->last_reminder_sent_at === null) {
            return true;
        }

        $threshold = $test->next_due_at?->copy()->subDays(self::LEAD_DAYS);
        if ($threshold === null) {
            return true;
        }

        return $test->last_reminder_sent_at->lessThan($threshold);
    }

    protected function shouldSendForResource(EmergencyResource $resource): bool
    {
        if ($resource->last_reminder_sent_at === null) {
            return true;
        }

        $threshold = $resource->next_check_at?->copy()->subDays(self::LEAD_DAYS);
        if ($threshold === null) {
            return true;
        }

        return $resource->last_reminder_sent_at->lessThan($threshold);
    }
}
