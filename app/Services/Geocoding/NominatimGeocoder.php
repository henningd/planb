<?php

namespace App\Services\Geocoding;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Geokodiert Freitext-Adressen über die öffentliche OpenStreetMap-Nominatim-API.
 *
 * Bewusst fehler-tolerant: Kein Treffer, HTTP-Fehler oder Timeout liefern
 * `null` — es wird nie eine Exception in den aufrufenden Flow geworfen.
 * Nominatim-Policy: aussagekräftiger User-Agent ist Pflicht, max. 1 Request
 * pro Sekunde (die Drosselung verantwortet der Aufrufer, siehe
 * `planb:geocode-locations`).
 */
class NominatimGeocoder
{
    public const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    public const USER_AGENT = 'PlanB-Notfallhandbuch (kontakt@arento.ai)';

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array
    {
        if (trim($address) === '') {
            return null;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::ENDPOINT, [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $hit = $response->json()[0] ?? null;

            if (! is_array($hit) || ! isset($hit['lat'], $hit['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $hit['lat'],
                'lng' => (float) $hit['lon'],
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
