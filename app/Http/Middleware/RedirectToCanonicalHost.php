<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Leitet Anfragen an Alt-Domains (z.B. planb.uniguard.cloud oder www.) per
 * 301 auf die kanonische Domain um, damit Suchmaschinen die Ranking-Signale
 * auf eine Domain bündeln.
 *
 * Aktiv nur, wenn CANONICAL_HOST gesetzt ist (config app.canonical_host) —
 * lokal bleibt die Middleware dadurch wirkungslos. Stripe-Webhooks und der
 * Health-Check sind ausgenommen, weil deren Aufrufer Redirects nicht folgen.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalHost = (string) config('app.canonical_host', '');

        if ($canonicalHost === ''
            || $request->getHost() === $canonicalHost
            || $request->is('stripe/*', 'up')) {
            return $next($request);
        }

        return redirect()->to(
            'https://'.$canonicalHost.$request->getRequestUri(),
            301,
        );
    }
}
