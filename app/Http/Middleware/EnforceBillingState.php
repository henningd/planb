<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bei eingefrorenen Mandanten (Trial abgelaufen, kein gültiges Abo) werden
 * schreibende Requests auf die Billing-Seite umgeleitet. GET-Requests
 * bleiben durchlässig — Daten lesen und exportieren bleibt möglich.
 */
class EnforceBillingState
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('features.billing')) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $team = $this->team($request);

        if (! $team instanceof Team || ! $team->isFrozen()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => __('Ihr Test-Zeitraum ist abgelaufen. Bitte wählen Sie einen Tarif, um wieder Änderungen vornehmen zu können.'),
            ], 402);
        }

        return redirect()
            ->route('billing.edit')
            ->with('billing.frozen', true);
    }

    protected function team(Request $request): ?Team
    {
        $slug = $request->route('current_team');

        if (is_string($slug)) {
            return Team::where('slug', $slug)->first();
        }

        return $request->user()?->currentTeam;
    }
}
