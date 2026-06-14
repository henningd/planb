<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Erzwingt die Einrichtung der Zwei-Faktor-Authentifizierung für jeden
 * verifizierten Nutzer, der noch kein bestätigtes 2FA hat. Solange das nicht
 * geschehen ist, wird der Nutzer auf die Security-Seite umgeleitet.
 *
 * Die E-Mail-Verifizierung läuft bewusst zuerst: unverifizierte Nutzer werden
 * hier durchgelassen und durch die `verified`-Middleware zur Bestätigung
 * geleitet. Erst danach greift die 2FA-Pflicht.
 *
 * Per `features.enforce_two_factor` deaktivierbar (z. B. in der Test-Suite),
 * damit Tests ohne 2FA-Setup weiterhin auf geschützte Seiten kommen.
 */
class EnforceTwoFactorSetup
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('features.enforce_two_factor', true)) {
            return $next($request);
        }

        $user = $request->user();

        // Nicht angemeldet oder E-Mail noch nicht bestätigt → erst greift die
        // Verifizierung, nicht die 2FA-Pflicht.
        if ($user === null || ! $user->hasVerifiedEmail()) {
            return $next($request);
        }

        // Bestandsnutzer sind von der 2FA-Pflicht ausgenommen (Grandfathering);
        // nur für neue Registrierungen ist das Flag gesetzt.
        if (! $user->two_factor_required) {
            return $next($request);
        }

        // 2FA bereits eingerichtet und bestätigt → nichts zu tun.
        if ($user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        // Setup-, Auth- und 2FA-Pfade müssen erreichbar bleiben, damit die
        // Aktivierung (inkl. Passwortbestätigung) überhaupt möglich ist.
        if (
            $request->is('settings/*')
            || $request->is('user/*')
            || $request->is('logout')
            || $request->is('two-factor-challenge')
            || $request->is('email/*')
            || $request->routeIs('preferences.sidebar-group')
        ) {
            return $next($request);
        }

        return redirect()
            ->route('security.edit')
            ->with('warning', __('Bitte richten Sie die Zwei-Faktor-Authentifizierung ein, um Ihr Konto zu schützen, bevor Sie fortfahren.'));
    }
}
