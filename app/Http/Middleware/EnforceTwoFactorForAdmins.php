<?php

namespace App\Http\Middleware;

use App\Support\Settings\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Leitet Team-Admins eines Mandanten, der 2FA-Pflicht aktiviert hat,
 * auf die Security-Seite um, solange sie kein bestätigtes 2FA haben.
 * Bypass für Auth-/Settings-/2FA-Setup-Routen, damit die Aktivierung
 * möglich bleibt.
 */
class EnforceTwoFactorForAdmins
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->isCurrentTeamAdmin()) {
            return $next($request);
        }

        $company = $user->currentCompany();
        if ($company === null) {
            return $next($request);
        }

        if (! CompanySetting::for($company)->get('enforce_2fa_admins', false)) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        // Setup-/Logout-Pfade müssen erreichbar bleiben.
        if (
            $request->is('settings/*')
            || $request->is('logout')
            || $request->is('user/two-factor-*')
            || $request->is('two-factor-challenge')
            || $request->routeIs('preferences.sidebar-group')
        ) {
            return $next($request);
        }

        return redirect()
            ->route('security.edit')
            ->with('warning', __('Ihr Mandant verlangt 2FA für Admins. Bitte aktivieren Sie 2FA, bevor Sie weiterarbeiten.'));
    }
}
