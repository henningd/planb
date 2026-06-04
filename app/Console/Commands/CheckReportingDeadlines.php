<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\IncidentReport;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ReportingDeadlineApproaching;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

#[Signature('app:check-reporting-deadlines')]
#[Description('Alarmiert die Team-Admins, wenn eine gesetzliche Meldepflicht-Frist eines Vorfalls in höchstens 6 Stunden abläuft oder bereits abgelaufen ist. Idempotent: pro Meldepflicht wird höchstens ein Alarm verschickt.')]
class CheckReportingDeadlines extends Command
{
    /**
     * Vorlauf in Stunden: Eine offene Meldepflicht wird alarmiert, sobald ihre
     * Frist in höchstens so vielen Stunden abläuft (oder schon abgelaufen ist).
     */
    protected const WARN_HOURS = 6;

    public function handle(): int
    {
        $now = Carbon::now();
        $threshold = $now->copy()->addHours(self::WARN_HOURS);

        $reports = IncidentReport::withoutGlobalScope(CurrentCompanyScope::class)
            ->whereNotNull('occurred_at')
            ->with(['obligations', 'company.team.members'])
            ->get();

        $sent = 0;

        foreach ($reports as $report) {
            foreach ($report->obligations as $obligation) {
                if ($obligation->reported_at !== null || $obligation->deadline_alerted_at !== null) {
                    continue;
                }

                $hours = $obligation->obligation?->deadlineHours();
                if ($hours === null) {
                    continue;
                }

                $deadline = $report->occurred_at->copy()->addHours($hours);
                if ($deadline->greaterThan($threshold)) {
                    continue;
                }

                $recipients = $this->recipientsFor($report->company?->team);
                if ($recipients->isEmpty()) {
                    continue;
                }

                Notification::send($recipients, new ReportingDeadlineApproaching($report, $obligation, $deadline));
                $obligation->update(['deadline_alerted_at' => $now]);

                $sent++;
                $this->line("→ [{$report->title}] Alarm '{$obligation->obligation->label()}' an {$recipients->count()} Empfänger.");
            }
        }

        $this->info("Fertig. {$sent} Frist-Alarme verschickt.");

        return self::SUCCESS;
    }

    /**
     * Empfänger eines Frist-Alarms: alle Team-Mitglieder mit Rolle Owner oder
     * Admin. Wenn keine Admin/Owner-Mitglieder vorhanden sind, fällt es auf den
     * Team-Owner zurück.
     *
     * @return Collection<int, User>
     */
    protected function recipientsFor(?Team $team): Collection
    {
        if ($team === null) {
            return collect();
        }

        $admins = $team->members->filter(function ($member): bool {
            $role = $member->pivot->role ?? null;

            return in_array($role, [TeamRole::Owner->value, TeamRole::Admin->value], true);
        })->values();

        if ($admins->isNotEmpty()) {
            return $admins;
        }

        $owner = $team->owner();

        return $owner !== null ? collect([$owner]) : collect();
    }
}
