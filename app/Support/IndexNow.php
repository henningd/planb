<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Meldet neue oder geänderte URLs per IndexNow-Protokoll an die teilnehmenden
 * Suchmaschinen (Bing, Yandex, Seznam, Naver — und damit auch DuckDuckGo und
 * Copilot, die auf dem Bing-Index aufbauen). Google nimmt nicht teil.
 *
 * Deaktiviert, solange kein INDEXNOW_KEY konfiguriert ist; Fehler werden
 * gemeldet und verschluckt, damit ein Ping nie den auslösenden Vorgang bricht.
 */
class IndexNow
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    /**
     * @param  array<int, string>|string  $urls
     */
    public static function ping(array|string $urls): bool
    {
        $key = (string) config('services.indexnow.key');

        if ($key === '') {
            return false;
        }

        $urls = array_values((array) $urls);
        $host = parse_url($urls[0], PHP_URL_HOST);

        try {
            return Http::timeout(5)
                ->asJson()
                ->post(self::ENDPOINT, [
                    'host' => $host,
                    'key' => $key,
                    'keyLocation' => 'https://'.$host.'/'.$key.'.txt',
                    'urlList' => $urls,
                ])
                ->successful();
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
