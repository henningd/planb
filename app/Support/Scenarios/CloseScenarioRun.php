<?php

namespace App\Support\Scenarios;

use App\Events\IncidentEnded;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
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
    public function __construct(private readonly PushNotifier $push) {}

    public function handle(ScenarioRun $run, string $outcome, ?int $byUserId = null): void
    {
        $run->forceFill(
            $outcome === 'completed' ? ['ended_at' => now()] : ['aborted_at' => now()],
        )->save();

        $title = $run->title ?: 'Notfall';
        $heading = $outcome === 'aborted' ? 'Notfall abgebrochen' : 'Notfall beendet';

        AppNotification::create([
            'company_id' => $run->company_id,
            'type' => $outcome === 'aborted' ? 'incident_aborted' : 'incident_ended',
            'title' => $heading,
            'body' => $title,
            'scenario_run_id' => $run->id,
        ]);

        try {
            $company = Company::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($run->company_id);

            if ($company !== null) {
                $this->push->incidentEnded($company, $title, $outcome);
            }
        } catch (Throwable) {
            // best-effort
        }

        try {
            $endedBy = $byUserId !== null
                ? User::query()->withoutGlobalScope(CurrentCompanyScope::class)->find($byUserId)?->name
                : null;

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
