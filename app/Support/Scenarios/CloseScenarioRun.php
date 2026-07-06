<?php

namespace App\Support\Scenarios;

use App\Events\IncidentEnded;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Services\Chat\AlarmChatNotifier;
use App\Support\Push\PushNotifier;
use Throwable;

/**
 * Beendet („completed" → ended_at) oder bricht ab („aborted" → aborted_at) einen
 * laufenden Notfall-Ablauf und benachrichtigt darüber – App (sichtbarer Push,
 * `type=incident_ended`, veranlasst zugleich den Neu-Sync) und Web-Dashboard
 * (Broadcast {@see IncidentEnded}). Gemeinsame Logik für Mobile-API und Dashboard.
 */
class CloseScenarioRun
{
    public function __construct(
        private readonly PushNotifier $push,
        private readonly AlarmChatNotifier $chat,
    ) {}

    public function handle(ScenarioRun $run, string $outcome, ?int $byUserId = null, string $source = 'web'): void
    {
        $run->forceFill(
            $outcome === 'completed' ? ['ended_at' => now()] : ['aborted_at' => now()],
        )->save();

        $isDrill = $run->isDrill();
        $title = $run->title ?: 'Notfall';
        // Präfix nur für Logbuch und Push — der App-Feed rendert bei is_drill ein Badge.
        $feedHeading = $outcome === 'aborted' ? 'Notfall abgebrochen' : 'Notfall beendet';
        $heading = ($isDrill ? 'ÜBUNG: ' : '').$feedHeading;

        // Krisen-Logbuch: Abschluss/Abbruch revisionssicher festhalten (Quelle App/Web).
        CrisisLogEntry::create([
            'company_id' => $run->company_id,
            'scenario_run_id' => $run->id,
            'user_id' => $byUserId,
            'type' => 'system',
            'source' => $source,
            'message' => $heading,
            'occurred_at' => now(),
        ]);

        $endedBy = $byUserId !== null
            ? User::query()->withoutGlobalScope(CurrentCompanyScope::class)->find($byUserId)?->name
            : null;

        AppNotification::create([
            'company_id' => $run->company_id,
            'type' => $outcome === 'aborted' ? 'incident_aborted' : 'incident_ended',
            'title' => $feedHeading,
            'body' => $title,
            'triggered_by_name' => $endedBy,
            'severity' => 'info',
            'scenario_run_id' => $run->id,
        ]);

        try {
            $company = Company::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($run->company_id);

            if ($company !== null) {
                $this->push->incidentEnded($company, $title, $outcome, $byUserId, $isDrill);
                $this->chat->incidentEnded($company, $title, $outcome, $isDrill);
            }
        } catch (Throwable) {
            // best-effort
        }

        try {
            event(new IncidentEnded(
                companyId: $run->company_id,
                runId: $run->id,
                title: $title,
                outcome: $outcome,
                endedBy: $endedBy,
            ));
        } catch (Throwable) {
            // best-effort; Broadcast darf das Beenden nie blockieren
        }
    }
}
