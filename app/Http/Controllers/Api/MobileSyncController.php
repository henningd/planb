<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\Mobile\MobileSyncBundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Liefert das Offline-Datenpaket der Notfall-App für den Mandanten des
 * authentifizierten Tokens. Der `since`-Parameter ist vorgesehen (Delta), v1
 * liefert stets das vollständige Bundle; die App ersetzt ihren lokalen Stand.
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

        return response()->json(['data' => MobileSyncBundle::for($company)]);
    }
}
