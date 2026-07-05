<?php

namespace App\Support\Push;

use Illuminate\Support\Facades\Log;

/**
 * Default-Sender ohne Firebase-Konfiguration: versendet nichts, protokolliert
 * die Push-Absicht nur. So funktioniert die gesamte Alarmierungs-/Sync-Logik
 * bereits, ohne dass echte Zustellung nötig ist – diese aktiviert sich, sobald
 * Firebase-Zugangsdaten hinterlegt sind ({@see FcmPushSender}).
 */
class LogPushSender implements PushSender
{
    public function send(array $tokens, array $data, ?string $title = null, ?string $body = null): array
    {
        if ($tokens === []) {
            return [];
        }

        Log::info('Push (nicht zugestellt – kein Firebase konfiguriert)', [
            'devices' => count($tokens),
            'data' => $data,
            'title' => $title,
        ]);

        return [];
    }
}
