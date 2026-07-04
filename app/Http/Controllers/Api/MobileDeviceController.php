<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\MobileDevice;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registrierung von Push-Tokens (FCM/APNs) der Notfall-App. Grundlage für die
 * Alarmierung und das „bitte jetzt synchronisieren"-Signal (Push type=sync).
 */
class MobileDeviceController extends Controller
{
    public function register(Request $request): Response
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'in:ios,android'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);

        if ($token->created_by_user_id === null) {
            return response()->noContent();
        }

        MobileDevice::updateOrCreate(
            ['fcm_token' => $validated['fcm_token']],
            [
                'user_id' => $token->created_by_user_id,
                'company_id' => $token->company_id,
                'api_token_id' => $token->id,
                'platform' => $validated['platform'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->noContent();
    }

    public function unregister(Request $request): Response
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        MobileDevice::where('fcm_token', $validated['fcm_token'])->delete();

        return response()->noContent();
    }
}
