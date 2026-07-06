<?php

namespace App\Http\Controllers\Api;

use App\Events\ScenarioRunStepCompleted;
use App\Events\ScenarioRunStepReopened;
use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Push\PushNotifier;
use App\Support\Scenarios\CloseScenarioRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Geteilter Fortschritt eines laufenden Notfall-Ablaufs aus der App: ein Schritt
 * wird an-/abgehakt und serverseitig zurückgeschrieben, sodass Web-Dashboard und
 * alle Geräte denselben Stand sehen. Der {@see CurrentCompanyScope} ist im
 * API-Kontext inert — die Firmenzugehörigkeit wird ausschließlich über das Token
 * abgesichert.
 */
class MobileRunController extends Controller
{
    public function toggleStep(Request $request, string $run, string $step): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);
        abort_if($token->created_by_user_id === null, 403, 'Kein Bearbeiter hinterlegt.');

        $validated = $request->validate([
            'checked' => ['required', 'boolean'],
        ]);

        $scenarioRun = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->whereKey($run)
            ->firstOrFail();

        abort_unless($scenarioRun->isActive(), 409, 'Ablauf ist bereits abgeschlossen.');

        $runStep = $scenarioRun->steps()->whereKey($step)->firstOrFail();

        if ($validated['checked']) {
            $runStep->forceFill([
                'checked_at' => now(),
                'checked_by_user_id' => $token->created_by_user_id,
            ])->save();
        } else {
            $runStep->forceFill([
                'checked_at' => null,
                'checked_by_user_id' => null,
            ])->save();
        }
        $runStep->refresh();

        $userName = User::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($token->created_by_user_id)?->name ?? 'App';

        // Revisionssicher ins Krisen-Logbuch schreiben – mit Quelle „app", damit
        // im Nachhinein nachvollziehbar ist, wer wann worüber (App/Web) gehandelt hat.
        CrisisLogEntry::create([
            'company_id' => $scenarioRun->company_id,
            'scenario_run_id' => $scenarioRun->id,
            'user_id' => $token->created_by_user_id,
            'type' => 'step',
            'source' => 'app',
            'message' => $runStep->checked_at !== null
                ? 'Schritt erledigt: '.$runStep->title
                : 'Schritt zurückgesetzt: '.$runStep->title,
            'occurred_at' => now(),
        ]);

        // Live an das Web-Cockpit broadcasten (gleiche Events wie im Dashboard).
        // Best-effort: ein nicht erreichbarer Broadcast-Server (Reverb/Pusher)
        // darf das bereits gespeicherte Abhaken NICHT zum Fehler machen.
        try {
            if ($runStep->checked_at !== null) {
                event(new ScenarioRunStepCompleted($runStep, $userName, $runStep->checked_at->toIso8601String()));
            } else {
                event(new ScenarioRunStepReopened($runStep, $userName));
            }
        } catch (Throwable) {
            // Broadcast optional – der Schreibvorgang ist bereits persistiert.
        }

        // Übrige Geräte der Firma zum Neu-Sync anstoßen (geteilter Fortschritt).
        try {
            $company = Company::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($token->company_id);
            if ($company !== null) {
                app(PushNotifier::class)->syncCompany($company);
            }
        } catch (Throwable) {
            // best-effort
        }

        return response()->json([
            'run_id' => $scenarioRun->id,
            'step_id' => $runStep->id,
            'checked' => $runStep->checked_at !== null,
            'checked_at' => $runStep->checked_at?->toIso8601String(),
            'checked_by' => $userName,
        ]);
    }

    /**
     * Alarm-Quittierung (API v1.1): „gesehen" oder „übernehme" — Upsert pro
     * (User, Run). `taking_over` ersetzt `seen`; ein späteres `seen` downgraded
     * NIE ein bestehendes `taking_over`. Die Antwort spiegelt immer den
     * gespeicherten Stand wider.
     */
    public function acknowledge(Request $request, string $run): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);
        abort_if($token->created_by_user_id === null, 403, 'Kein Bearbeiter hinterlegt.');

        $validated = $request->validate([
            'status' => ['required', 'in:seen,taking_over'],
        ]);

        $scenarioRun = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->whereKey($run)
            ->firstOrFail();

        $acknowledgement = ScenarioRunAcknowledgement::query()->firstOrNew([
            'scenario_run_id' => $scenarioRun->id,
            'user_id' => $token->created_by_user_id,
        ]);

        $isDowngrade = $acknowledgement->exists
            && $acknowledgement->status === ScenarioRunAcknowledgement::STATUS_TAKING_OVER
            && $validated['status'] === ScenarioRunAcknowledgement::STATUS_SEEN;

        $changed = false;

        if (! $isDowngrade && $acknowledgement->status !== $validated['status']) {
            $acknowledgement->status = $validated['status'];
            $acknowledgement->acknowledged_at = now();
            $acknowledgement->save();
            $changed = true;
        }

        // Übrige Geräte zum Neu-Sync anstoßen, damit die Quittierungsliste
        // überall aktuell ist (best-effort, wie beim Schritt-Abhaken).
        if ($changed) {
            try {
                $company = Company::query()
                    ->withoutGlobalScope(CurrentCompanyScope::class)
                    ->find($token->company_id);
                if ($company !== null) {
                    app(PushNotifier::class)->syncCompany($company);
                }
            } catch (Throwable) {
                // best-effort
            }
        }

        return response()->json(['data' => [
            'run_id' => $scenarioRun->id,
            'status' => $acknowledgement->status,
            'acknowledged_at' => $acknowledgement->acknowledged_at?->toIso8601String(),
        ]]);
    }

    /**
     * Beendet („completed" → ended_at) oder bricht ab („aborted" → aborted_at)
     * einen laufenden Notfall-Ablauf. Danach fällt er aus dem Sync-Bundle und
     * die übrigen Geräte werden per Push zum Neu-Sync angestoßen, sodass ihre
     * „Aktiver Notfall"-Karte sofort verschwindet.
     */
    public function close(Request $request, string $run): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);
        abort_if($token->created_by_user_id === null, 403, 'Kein Bearbeiter hinterlegt.');

        $validated = $request->validate([
            'outcome' => ['required', 'in:completed,aborted'],
        ]);

        $scenarioRun = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->whereKey($run)
            ->firstOrFail();

        abort_unless($scenarioRun->isActive(), 409, 'Ablauf ist bereits abgeschlossen.');

        app(CloseScenarioRun::class)->handle($scenarioRun, $validated['outcome'], $token->created_by_user_id, 'app');

        return response()->json([
            'run_id' => $scenarioRun->id,
            'outcome' => $validated['outcome'],
            'active' => false,
        ]);
    }
}
