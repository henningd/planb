<?php

namespace App\Http\Controllers\Api;

use App\Events\ScenarioRunStepCompleted;
use App\Events\ScenarioRunStepReopened;
use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Push\PushNotifier;
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

        // Live an das Web-Cockpit broadcasten (gleiche Events wie im Dashboard).
        if ($runStep->checked_at !== null) {
            event(new ScenarioRunStepCompleted($runStep, $userName, $runStep->checked_at->toIso8601String()));
        } else {
            event(new ScenarioRunStepReopened($runStep, $userName));
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
}
