<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prüft den optionalen App-Key der Notfall-App (Header `X-App-Key`) gegen
 * config('services.mobile.app_key'). Ist serverseitig kein Key konfiguriert
 * (leer), wird nicht geprüft — die App sendet dann ebenfalls einen leeren Key.
 */
class EnsureMobileAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.mobile.app_key', '');

        if ($expected === '') {
            return $next($request);
        }

        $provided = (string) $request->header('X-App-Key', '');

        if (! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'invalid_app_key'], 401);
        }

        return $next($request);
    }
}
