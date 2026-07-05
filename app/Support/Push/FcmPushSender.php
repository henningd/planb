<?php

namespace App\Support\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Versendet Push-Nachrichten über die FCM HTTP v1 API. Aktiv, sobald ein
 * Firebase-Service-Account konfiguriert ist. Holt sich per JWT-Bearer ein
 * OAuth2-Access-Token (gecacht) und stellt pro Gerät eine Nachricht zu.
 */
class FcmPushSender implements PushSender
{
    /**
     * @param  array{client_email: string, private_key: string}  $credentials
     */
    public function __construct(
        private readonly string $projectId,
        private readonly array $credentials,
    ) {}

    public function send(array $tokens, array $data, ?string $title = null, ?string $body = null): array
    {
        if ($tokens === []) {
            return [];
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return [];
        }

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $dead = [];

        foreach ($tokens as $token) {
            $message = ['token' => $token, 'data' => $data];
            if ($title !== null || $body !== null) {
                $message['notification'] = array_filter(
                    ['title' => $title, 'body' => $body],
                    fn ($value) => $value !== null,
                );
            }

            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($endpoint, ['message' => $message]);

                if ($response->failed()) {
                    Log::warning('FCM-Push abgelehnt', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    if ($response->json('error.status') === 'UNREGISTERED'
                        || in_array($response->status(), [400, 404], true)) {
                        $dead[] = $token;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('FCM-Push fehlgeschlagen: '.$e->getMessage());
            }
        }

        return $dead;
    }

    /**
     * OAuth2-Access-Token für die FCM-API via Service-Account (JWT-Bearer),
     * ~55 Minuten gecacht.
     */
    private function accessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function (): ?string {
            $jwt = $this->signedJwt();
            if ($jwt === null) {
                return null;
            }

            try {
                return Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ])->json('access_token');
            } catch (\Throwable $e) {
                Log::warning('FCM-Token-Abruf fehlgeschlagen: '.$e->getMessage());

                return null;
            }
        });
    }

    private function signedJwt(): ?string
    {
        $now = time();
        $header = self::base64Url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64Url((string) json_encode([
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = $header.'.'.$claims;
        $signature = '';
        if (! openssl_sign($signingInput, $signature, $this->credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return $signingInput.'.'.self::base64Url($signature);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
