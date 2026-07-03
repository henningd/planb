<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\Mobile\MobileSyncBundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Liefert das Offline-Datenpaket der Notfall-App für den Mandanten des
 * authentifizierten Tokens — mit inhaltsbasiertem Delta-Sync.
 *
 * Über einen Fingerprint (`version`) des Bundle-Inhalts wird erkannt, ob sich
 * seit dem letzten Sync des Clients etwas geändert hat. Schickt der Client
 * seine bekannte Version (Query `?version=` oder Header `If-None-Match`) und
 * stimmt sie mit der aktuellen überein, wird nur ein kompaktes
 * `unchanged`-Signal zurückgegeben — der Client behält seinen lokalen Stand.
 * Andernfalls kommt das vollständige Bundle mit neuer Version. Da jede
 * Änderung (inkl. Löschungen) den Fingerprint verändert, propagieren auch
 * gelöschte Datensätze zuverlässig.
 */
class MobileSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->attributes->get('api_token');

        $company = $token instanceof ApiToken
            ? Company::query()->withoutGlobalScope(CurrentCompanyScope::class)->find($token->company_id)
            : null;

        abort_if($company === null, 404);

        $bundle = MobileSyncBundle::for($company);
        $version = self::fingerprint($bundle);
        $syncedAt = Carbon::now()->toIso8601String();

        if (self::clientVersion($request) === $version) {
            return response()->json(['data' => [
                'synced_at' => $syncedAt,
                'version' => $version,
                'unchanged' => true,
            ]]);
        }

        return response()->json(['data' => [
            'synced_at' => $syncedAt,
            'version' => $version,
            'unchanged' => false,
            ...$bundle,
        ]]);
    }

    /**
     * Inhalts-Fingerprint des Bundles (stabil, ohne volatile Felder).
     *
     * @param  array<string, mixed>  $bundle
     */
    private static function fingerprint(array $bundle): string
    {
        return 'v1:'.substr(hash('sha256', (string) json_encode($bundle)), 0, 32);
    }

    private static function clientVersion(Request $request): ?string
    {
        $version = $request->query('version');

        if (! is_string($version) || $version === '') {
            $version = trim((string) $request->header('If-None-Match'), '"');
        }

        return $version !== '' ? $version : null;
    }
}
