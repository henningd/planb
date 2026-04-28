<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validiert den Bearer-Token aus dem Authorization-Header gegen api_tokens.
 *
 * Bei Erfolg:
 *  - hängt das Token-Model an den Request (`$request->attributes`)
 *  - aktualisiert last_used_at
 *  - prüft den geforderten Scope (per Middleware-Parameter)
 *
 * Bei Misserfolg: 401 / 403.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, ?string $requiredScope = null): Response
    {
        $header = $request->bearerToken();
        if ($header === null || $header === '') {
            return response()->json(['error' => 'missing_token'], 401);
        }

        $token = ApiToken::findActiveByPlainToken($header);
        if (! $token) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        if ($requiredScope !== null && ! $token->hasScope($requiredScope)) {
            return response()->json(['error' => 'insufficient_scope', 'required' => $requiredScope], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
