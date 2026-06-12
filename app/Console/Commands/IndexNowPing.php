<?php

namespace App\Console\Commands;

use App\Support\IndexNow;
use App\Support\Marketing\MarketingUrls;
use Illuminate\Console\Command;

/**
 * Meldet Marketing-URLs per IndexNow an Bing & Co. — gedacht als Schritt im
 * Deploy-Skript oder manuell nach Inhalts-Änderungen.
 */
class IndexNowPing extends Command
{
    protected $signature = 'indexnow:ping {url?* : Einzelne URLs; ohne Angabe werden alle Marketing-Seiten gemeldet}';

    protected $description = 'Meldet URLs per IndexNow an die teilnehmenden Suchmaschinen (Bing, Yandex …)';

    public function handle(): int
    {
        if ((string) config('services.indexnow.key') === '') {
            $this->warn('INDEXNOW_KEY ist nicht gesetzt — nichts zu tun.');

            return self::SUCCESS;
        }

        /** @var array<int, string> $urls */
        $urls = $this->argument('url') ?: MarketingUrls::locations();

        if (IndexNow::ping($urls)) {
            $this->info(count($urls).' URL(s) gemeldet.');

            return self::SUCCESS;
        }

        $this->error('IndexNow-Ping fehlgeschlagen (Details im Log).');

        return self::FAILURE;
    }
}
