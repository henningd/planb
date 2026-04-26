<?php

namespace App\Http\Middleware;

use App\Support\Settings\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sperrt /register zur Laufzeit auf Basis der Plattform-Einstellung
 * `registration_enabled`. Ergänzt Fortifys Feature-Flag, das nur zur
 * Boot-Zeit ausgewertet wird, um eine UI-steuerbare Variante.
 */
class EnsureRegistrationEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('register') && ! SystemSetting::get('registration_enabled', true)) {
            abort(404);
        }

        return $next($request);
    }
}
