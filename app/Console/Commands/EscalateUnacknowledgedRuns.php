<?php

namespace App\Console\Commands;

use App\Enums\CrisisRole;
use App\Enums\ScenarioRunMode;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\ScenarioRun;
use App\Scopes\CurrentCompanyScope;
use App\Services\Chat\AlarmChatNotifier;
use App\Services\Sms\SmsGatewayContract;
use App\Support\Push\PushNotifier;
use App\Support\Settings\CompanySetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('planb:escalate-unacknowledged-runs')]
#[Description('Eskaliert echte Alarme (mode=real), die nach Ablauf der firmenspezifischen Eskalationsfrist von niemandem quittiert wurden: erneuter Push an alle Geräte, SMS an den Krisenstab (falls konfiguriert), Vermerk im Krisen-Logbuch. Idempotent: escalated_at sperrt gegen Doppel-Eskalation.')]
class EscalateUnacknowledgedRuns extends Command
{
    public function handle(PushNotifier $push, SmsGatewayContract $sms, AlarmChatNotifier $chat): int
    {
        // Übungen (drill) werden NIE eskaliert. Läuft ein Alt-Run ohne
        // Acknowledgement-Einträge (vor Einführung der Quittierungen
        // gestartet), greift dieselbe Logik.
        $candidates = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('mode', ScenarioRunMode::Real->value)
            ->whereNull('ended_at')
            ->whereNull('aborted_at')
            ->whereNull('escalated_at')
            ->whereDoesntHave('acknowledgements')
            ->get();

        $escalated = 0;

        foreach ($candidates->groupBy('company_id') as $companyId => $runs) {
            $company = Company::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($companyId);

            if ($company === null) {
                continue;
            }

            $minutes = (int) CompanySetting::for($company)->get('alarm_escalation_minutes', 10);
            if ($minutes <= 0) {
                continue;
            }

            foreach ($runs as $run) {
                if ($run->started_at->greaterThan(now()->subMinutes($minutes))) {
                    continue;
                }

                if ($this->escalate($company, $run, $minutes, $push, $sms, $chat)) {
                    $escalated++;
                    $this->line("→ [{$run->title}] eskaliert nach {$minutes} Minuten ohne Quittierung.");
                }
            }
        }

        $this->info("Fertig. {$escalated} Alarm(e) eskaliert.");

        return self::SUCCESS;
    }

    /**
     * Führt die einmalige Eskalation eines Runs aus. Das bedingte Update auf
     * `escalated_at` wirkt als Lock — bei parallelen Läufen eskaliert genau
     * einer. Push/SMS/Chat-Post sind best-effort und blockieren einander nicht.
     */
    private function escalate(Company $company, ScenarioRun $run, int $minutes, PushNotifier $push, SmsGatewayContract $sms, AlarmChatNotifier $chat): bool
    {
        $claimed = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->whereKey($run->id)
            ->whereNull('escalated_at')
            ->update(['escalated_at' => now()]);

        if ($claimed === 0) {
            return false;
        }

        try {
            $push->incidentUnacknowledged($company, $run->id, $run->scenario_id, $run->title);
        } catch (Throwable) {
            // best-effort — SMS und Logbuch-Vermerk folgen trotzdem
        }

        try {
            $chat->incidentUnacknowledged($company, $run->title);
        } catch (Throwable) {
            // best-effort — Chat-Post darf die Eskalation nie blockieren
        }

        $smsSent = 0;
        if ($sms->isConfigured()) {
            $text = sprintf(
                'PlanB ALARM: „%s" ist seit %d Minuten von niemandem quittiert. Bitte in der Notfall-App übernehmen.',
                $run->title,
                $minutes,
            );

            foreach ($this->crisisStaffNumbers($company) as $number) {
                try {
                    if ($sms->send($number, $text)->success) {
                        $smsSent++;
                    }
                } catch (Throwable) {
                    // best-effort
                }
            }
        }

        CrisisLogEntry::create([
            'company_id' => $company->id,
            'scenario_run_id' => $run->id,
            'user_id' => null,
            'type' => 'system',
            'source' => 'system',
            'message' => sprintf(
                'Eskalation: Alarm nach %d Minuten ohne Quittierung — erneuter Push an alle Geräte%s.',
                $minutes,
                $smsSent > 0 ? ", SMS an {$smsSent} Krisenstab-Nummer(n)" : '',
            ),
            'occurred_at' => now(),
        ]);

        return true;
    }

    /**
     * Mobilnummern des Krisenstabs: alle besetzten Pflichtrollen
     * (Hauptperson und Vertretung) mit gepflegter Mobilnummer, dedupliziert.
     *
     * @return list<string>
     */
    private function crisisStaffNumbers(Company $company): array
    {
        $numbers = [];

        foreach (CrisisRole::cases() as $role) {
            foreach ([false, true] as $deputy) {
                $phone = trim((string) $company->crisisRoleHolder($role, $deputy)?->mobile_phone);
                if ($phone !== '') {
                    $numbers[$phone] = true;
                }
            }
        }

        return array_keys($numbers);
    }
}
