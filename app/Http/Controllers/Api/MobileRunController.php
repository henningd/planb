<?php

namespace App\Http\Controllers\Api;

use App\Events\ScenarioRunMessagePosted;
use App\Events\ScenarioRunStepAssigned;
use App\Events\ScenarioRunStepCompleted;
use App\Events\ScenarioRunStepReopened;
use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\Employee;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use App\Models\ScenarioRunMessage;
use App\Models\ScenarioRunParticipant;
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

    /**
     * Koordinations-/Lagemeldung aus der App posten (Chat am laufenden Notfall).
     */
    public function postMessage(Request $request, string $run): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);
        abort_if($token->created_by_user_id === null, 403, 'Kein Bearbeiter hinterlegt.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $scenarioRun = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->whereKey($run)
            ->firstOrFail();

        abort_unless($scenarioRun->isActive(), 409, 'Ablauf ist bereits abgeschlossen.');

        $userName = User::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($token->created_by_user_id)?->name ?? 'App';

        $message = ScenarioRunMessage::create([
            'company_id' => $scenarioRun->company_id,
            'scenario_run_id' => $scenarioRun->id,
            'user_id' => $token->created_by_user_id,
            'author_name' => $userName,
            'body' => trim($validated['body']),
        ]);

        try {
            event(new ScenarioRunMessagePosted($message));
        } catch (Throwable) {
            // Broadcast optional – die Meldung ist bereits gespeichert.
        }

        $this->pushSync($token->company_id);

        return response()->json([
            'id' => $message->id,
            'author' => $userName,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Einen Schritt einer Person zuweisen bzw. Zuweisung entfernen (employee_id null).
     */
    public function assignStep(Request $request, string $run, string $step): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);
        abort_if($token->created_by_user_id === null, 403, 'Kein Bearbeiter hinterlegt.');

        $validated = $request->validate([
            'employee_id' => ['nullable', 'string'],
        ]);

        $scenarioRun = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->whereKey($run)
            ->firstOrFail();

        abort_unless($scenarioRun->isActive(), 409, 'Ablauf ist bereits abgeschlossen.');

        $runStep = $scenarioRun->steps()->whereKey($step)->firstOrFail();

        $employeeId = ($validated['employee_id'] ?? '') !== '' ? $validated['employee_id'] : null;
        $employee = null;
        if ($employeeId !== null) {
            $employee = Employee::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->where('company_id', $token->company_id)
                ->whereKey($employeeId)
                ->first();
            abort_unless($employee !== null, 422, 'Unbekannter Mitarbeiter.');
        }

        $runStep->forceFill(['assigned_employee_id' => $employeeId])->save();
        $runStep->refresh();

        try {
            event(new ScenarioRunStepAssigned($runStep, $employee?->fullName()));
        } catch (Throwable) {
            // Broadcast optional.
        }

        $this->pushSync($token->company_id);

        return response()->json([
            'run_id' => $scenarioRun->id,
            'step_id' => $runStep->id,
            'assigned_to' => $employee?->fullName(),
        ]);
    }

    /**
     * Präsenz-Heartbeat: markiert den Nutzer als aktiv an diesem Notfall und
     * liefert die aktuell aktiven Teilnehmer zurück. Löst bewusst KEINEN
     * Sync-Push aus (zu häufig).
     */
    public function heartbeat(Request $request, string $run): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);
        abort_if($token->created_by_user_id === null, 403, 'Kein Bearbeiter hinterlegt.');

        $scenarioRun = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->whereKey($run)
            ->firstOrFail();

        ScenarioRunParticipant::updateOrCreate(
            ['scenario_run_id' => $scenarioRun->id, 'user_id' => $token->created_by_user_id],
            ['last_seen_at' => now()],
        );

        $fresh = now()->subSeconds(ScenarioRunParticipant::FRESH_SECONDS);
        $participants = $scenarioRun->participants()
            ->with('user')
            ->where('last_seen_at', '>=', $fresh)
            ->get()
            ->map(fn (ScenarioRunParticipant $p) => [
                'user_id' => (string) $p->user_id,
                'name' => $p->user?->name,
            ])
            ->all();

        return response()->json(['participants' => $participants]);
    }

    /**
     * Übrige Geräte der Firma best-effort zum Neu-Sync anstoßen (geteilter Stand).
     */
    private function pushSync(string $companyId): void
    {
        try {
            $company = Company::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($companyId);
            if ($company !== null) {
                app(PushNotifier::class)->syncCompany($company);
            }
        } catch (Throwable) {
            // best-effort
        }
    }
}
