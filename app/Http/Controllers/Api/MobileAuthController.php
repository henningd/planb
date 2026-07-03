<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentifizierung der PlanB-Notfall-App.
 *
 * `login` löst einen einmaligen Kopplungs-Code ein (E-Mail + Code) und stellt
 * einen langlebigen Bearer-Token (Scope `mobile`) für die zugehörige Firma aus.
 * Über die im Code hinterlegte Firma spricht das Gerät automatisch den
 * richtigen Mandanten an.
 */
class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
        ]);

        $accessCode = MobileAccessCode::findRedeemable($validated['email'], $validated['code']);

        if ($accessCode === null) {
            return response()->json(['message' => 'Ungültiger oder abgelaufener Zugangscode.'], 401);
        }

        $company = Company::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($accessCode->company_id);
        $user = User::find($accessCode->user_id);

        if ($company === null || $user === null) {
            return response()->json(['message' => 'Dieser Zugang ist nicht mehr gültig.'], 401);
        }

        $issued = ApiToken::issue(
            companyId: $company->id,
            name: 'Notfall-App · '.$user->email,
            scopes: ['mobile'],
            userId: $user->id,
        );

        $accessCode->consume($issued['model']);

        return response()->json([
            'token' => $issued['token'],
            'expires_at' => null,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        $token = $request->attributes->get('api_token');

        if ($token instanceof ApiToken) {
            $token->forceFill(['revoked_at' => now()])->save();
        }

        return response()->noContent();
    }
}
