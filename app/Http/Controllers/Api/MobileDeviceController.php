<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registrierung von Push-Tokens (FCM/APNs) der Notfall-App.
 *
 * Vorerst nimmt der Endpunkt die Registrierung entgegen und bestätigt sie, ohne
 * sie zu persistieren — die serverseitige Push-Zustellung (Alarmierung) folgt
 * als eigener Ausbauschritt. So schlägt der Post-Login-Aufruf der App nicht fehl.
 */
class MobileDeviceController extends Controller
{
    public function register(Request $request): Response
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'in:ios,android'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->noContent();
    }

    public function unregister(Request $request): Response
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        return response()->noContent();
    }
}
