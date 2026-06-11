<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blockt Routen von per config/features.php deaktivierten Funktionsbereichen
 * mit 404, z.B. `->middleware('feature:roles')`. Im Gegensatz zum Gating bei
 * der Routen-Registrierung bleiben die Routen-Namen auflösbar, sodass
 * `route()`-Aufrufe an anderen Stellen nicht brechen.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless((bool) config('features.'.$feature, false), 404);

        return $next($request);
    }
}
