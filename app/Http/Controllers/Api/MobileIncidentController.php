<?php

namespace App\Http\Controllers\Api;

use App\Enums\ScenarioRunMode;
use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Scenario;
use App\Scopes\CurrentCompanyScope;
use App\Support\Scenarios\StartScenarioRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * „Notfall auslösen" aus der Notfall-App: startet einen Szenario-Ablauf für die
 * Firma des Tokens und alarmiert (bei echtem Notfall) die übrigen Geräte per
 * Push. Der {@see CurrentCompanyScope} ist im API-Kontext inert – die
 * Firmenzugehörigkeit wird deshalb ausschließlich über das Token abgesichert.
 */
class MobileIncidentController extends Controller
{
    public function store(Request $request, StartScenarioRun $starter): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);
        abort_if($token->created_by_user_id === null, 403, 'Kein Auslöser hinterlegt.');

        $validated = $request->validate([
            'scenario_id' => ['required', 'string'],
            'mode' => ['nullable', 'in:real,drill'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        // Szenario streng auf die Firma des Tokens einschränken – Scope ist inert.
        $scenario = Scenario::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->where('id', $validated['scenario_id'])
            ->firstOrFail();

        $run = $starter->handle(
            scenario: $scenario,
            startedByUserId: $token->created_by_user_id,
            mode: $validated['mode'] ?? ScenarioRunMode::Real->value,
            title: $validated['title'] ?? null,
            source: 'app',
        );

        return response()->json([
            'run_id' => $run->id,
            'title' => $run->title,
            'mode' => $run->mode->value,
            'started_at' => $run->started_at?->toIso8601String(),
        ], 201);
    }
}
