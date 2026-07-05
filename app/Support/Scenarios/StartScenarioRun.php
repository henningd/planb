<?php

namespace App\Support\Scenarios;

use App\Enums\ScenarioRunMode;
use App\Events\IncidentStarted;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Push\PushNotifier;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Startet einen Szenario-Ablauf (Notfall) und kopiert die Szenario-Schritte in
 * eine frische Bearbeitungsliste. Gemeinsame Logik für das Web-Frontend
 * (Incident-Launcher) und die Notfall-App (Mobile-API), damit ein per App
 * ausgelöster Notfall exakt so aussieht wie ein im Dashboard gestarteter.
 *
 * Bei einem echten Notfall (Modus {@see ScenarioRunMode::Real}) werden zusätzlich
 * alle registrierten Geräte der Firma per Push alarmiert.
 */
class StartScenarioRun
{
    public function __construct(private readonly PushNotifier $push) {}

    /**
     * @param  ScenarioRunMode|string  $mode  Enum oder dessen Wert ('real'/'drill')
     */
    public function handle(
        Scenario $scenario,
        int $startedByUserId,
        ScenarioRunMode|string $mode = ScenarioRunMode::Real,
        ?string $title = null,
    ): ScenarioRun {
        $mode = $mode instanceof ScenarioRunMode ? $mode : ScenarioRunMode::from($mode);
        $scenario->loadMissing('steps');

        $title = filled($title)
            ? $title
            : $scenario->name.' · '.now()->format('d.m.Y H:i');

        $run = DB::transaction(function () use ($scenario, $startedByUserId, $mode, $title) {
            $run = ScenarioRun::create([
                'company_id' => $scenario->company_id,
                'scenario_id' => $scenario->id,
                'started_by_user_id' => $startedByUserId,
                'title' => $title,
                'mode' => $mode->value,
                'started_at' => now(),
            ]);

            foreach ($scenario->steps as $step) {
                $run->steps()->create([
                    'sort' => $step->sort,
                    'title' => $step->title,
                    'description' => $step->description,
                    'responsible' => $step->responsible,
                ]);
            }

            return $run;
        });

        if ($mode === ScenarioRunMode::Real) {
            $this->alarm($scenario, $run, $startedByUserId);
        }

        return $run;
    }

    /**
     * Alarmierung darf das Auslösen nie blockieren – Fehler werden geschluckt.
     * Zwei Kanäle: Push an die Geräte (Apps) und ein firmenweiter Broadcast fürs
     * Web-Dashboard ({@see IncidentStarted}).
     */
    private function alarm(Scenario $scenario, ScenarioRun $run, int $startedByUserId): void
    {
        AppNotification::create([
            'company_id' => $scenario->company_id,
            'type' => 'incident_started',
            'title' => 'Notfall gemeldet',
            'body' => $scenario->name,
            'scenario_run_id' => $run->id,
        ]);

        try {
            $company = Company::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($scenario->company_id);

            if ($company !== null) {
                $this->push->incident($company, $scenario->id, $scenario->name);
            }
        } catch (Throwable) {
            // best-effort
        }

        try {
            $startedBy = User::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($startedByUserId)?->name;

            event(new IncidentStarted(
                companyId: $scenario->company_id,
                runId: $run->id,
                scenarioId: $scenario->id,
                scenarioTitle: $scenario->name,
                startedBy: $startedBy,
            ));
        } catch (Throwable) {
            // best-effort; Broadcast darf das Auslösen nie blockieren
        }
    }
}
