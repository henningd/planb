<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Notifications\ReviewDueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

#[Signature('reviews:send-due')]
#[Description('Send a reminder email to every team whose emergency handbook review is due.')]
class SendDueReviewReminders extends Command
{
    /**
     * Minimum days between two reminders for the same company, so users are
     * not spammed if they do not confirm immediately.
     */
    protected const COOLDOWN_DAYS = 14;

    public function handle(): int
    {
        $now = Carbon::now();
        $cooldownCutoff = $now->copy()->subDays(self::COOLDOWN_DAYS);

        $companies = Company::with('team.members')->get();

        $sent = 0;
        foreach ($companies as $company) {
            if (! $company->isReviewDue()) {
                continue;
            }

            if ($company->last_reminder_sent_at && $company->last_reminder_sent_at->greaterThan($cooldownCutoff)) {
                continue;
            }

            $recipients = $company->team?->members ?? collect();
            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new ReviewDueNotification($company));
            $company->forceFill(['last_reminder_sent_at' => $now])->save();

            $sent++;
            $this->line("→ reminder sent to {$company->name} ({$recipients->count()} users)");
        }

        $this->info("Finished. {$sent} reminders sent.");

        return self::SUCCESS;
    }
}
