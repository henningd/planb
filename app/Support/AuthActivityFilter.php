<?php

namespace App\Support;

use App\Models\AuthActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Baut die gefilterte AuthActivity-Query basierend auf den Query-Parametern.
 * Wird sowohl von der Login-Activity-Page (über die übergebenen Properties) als
 * auch vom Export-Controller (über den Request) genutzt, damit Filter konsistent
 * sind und doppelte Logik vermieden wird.
 */
class AuthActivityFilter
{
    /**
     * Erzeugt eine bereits sortierte und gefilterte Query für AuthActivity.
     *
     * Erwartete Parameter (alle optional):
     *  - event: exakter Match auf event ('login'|'logout'|'failed')
     *  - search: Volltext über email, ip_address und user.name
     *  - from: ISO-Datum (Y-m-d) — created_at >= 00:00:00
     *  - to:   ISO-Datum (Y-m-d) — created_at <= 23:59:59
     *
     * @param  array<string, string|null>  $filters
     * @return Builder<AuthActivity>
     */
    public static function build(array $filters): Builder
    {
        $query = AuthActivity::query()->with('user')->orderByDesc('created_at');

        $event = trim((string) ($filters['event'] ?? ''));
        if ($event !== '') {
            $query->where('event', $event);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->where('email', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhereHas('user', function (Builder $u) use ($like): void {
                        $u->where('name', 'like', $like);
                    });
            });
        }

        $from = trim((string) ($filters['from'] ?? ''));
        if ($from !== '') {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }

        $to = trim((string) ($filters['to'] ?? ''));
        if ($to !== '') {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        return $query;
    }

    /**
     * Extrahiert die unterstützten Filter-Werte aus einem Request.
     *
     * @return array<string, string>
     */
    public static function fromRequest(Request $request): array
    {
        return [
            'event' => (string) $request->query('event', ''),
            'search' => (string) $request->query('search', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];
    }
}
