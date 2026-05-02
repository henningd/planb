<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;

#[Signature('stripe:bootstrap {--path= : Verzeichnis mit den Bootstrap-JSONs (Default: storage/stripe-bootstrap)} {--dry-run : Nichts anlegen, nur ausgeben, was passieren würde}')]
#[Description('Legt Products und Prices aus den Bootstrap-JSONs in Stripe an und gibt die ENV-Werte für die .env aus.')]
class StripeBootstrap extends Command
{
    /**
     * Slug aus dem Dateinamen → ENV-Variable. Slugs ohne Eintrag werden zwar
     * angelegt, aber nicht in die .env-Ausgabe übernommen.
     *
     * @var array<string, string>
     */
    public const ENV_MAP = [
        'starter_monthly' => 'STRIPE_PRICE_STARTER_MONTHLY',
        'starter_yearly' => 'STRIPE_PRICE_STARTER_YEARLY',
        'advanced_monthly' => 'STRIPE_PRICE_ADVANCED_MONTHLY',
        'advanced_yearly' => 'STRIPE_PRICE_ADVANCED_YEARLY',
        'workshop' => 'STRIPE_PRICE_ADDON_WORKSHOP',
        'coaching_hour' => 'STRIPE_PRICE_ADDON_COACHING_HOUR',
        'coaching_retainer' => 'STRIPE_PRICE_ADDON_COACHING_RETAINER',
        'extra_user' => 'STRIPE_PRICE_ADDON_EXTRA_USER',
    ];

    public function handle(): int
    {
        if (! config('cashier.secret')) {
            $this->error('STRIPE_SECRET ist nicht gesetzt. Bitte erst sk_test_… aus dem Stripe-Dashboard in die .env eintragen.');

            return self::FAILURE;
        }

        $directory = $this->option('path') ?: storage_path('stripe-bootstrap');

        if (! is_dir($directory)) {
            $this->error("Verzeichnis nicht gefunden: {$directory}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $stripe = Cashier::stripe();

        $productFiles = glob($directory.'/product-*.json') ?: [];
        $priceFiles = glob($directory.'/price-*.json') ?: [];

        if ($productFiles === [] || $priceFiles === []) {
            $this->error("Keine Bootstrap-JSONs in {$directory} gefunden.");

            return self::FAILURE;
        }

        try {
            $productIdMap = $this->syncProducts($stripe, $productFiles, $dryRun);
            $envOutput = $this->syncPrices($stripe, $priceFiles, $productIdMap, $dryRun);
        } catch (ApiErrorException $exception) {
            $this->error('Stripe-API-Fehler: '.$exception->getMessage());

            return self::FAILURE;
        } catch (JsonException $exception) {
            $this->error('Bootstrap-JSON konnte nicht gelesen werden: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry-Run abgeschlossen. Ohne --dry-run werden die folgenden Werte gesetzt:'
            : 'Folgende Zeilen in die .env übernehmen:');

        foreach (self::ENV_MAP as $slug => $envKey) {
            $value = $envOutput[$slug] ?? 'price_…';
            $this->line("{$envKey}={$value}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $files
     * @return array<string, string> Bootstrap-Product-ID → Stripe-Product-ID
     */
    protected function syncProducts(StripeClient $stripe, array $files, bool $dryRun): array
    {
        $this->info('1/2 Products synchronisieren …');

        $map = [];

        foreach ($files as $file) {
            $slug = $this->slugFromFilename($file, 'product');
            $data = $this->readJson($file);

            $existing = $this->findProductBySlug($stripe, $slug);

            if ($existing !== null) {
                $map[$data['id']] = $existing->id;
                $this->line("  ↺ {$slug} → {$existing->id} (vorhanden)");

                continue;
            }

            if ($dryRun) {
                $map[$data['id']] = 'prod_dryrun_'.$slug;
                $this->line("  + {$slug} (würde angelegt: {$data['name']})");

                continue;
            }

            $product = $stripe->products->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'service',
                'metadata' => ['planb_key' => $slug],
            ]);

            $map[$data['id']] = $product->id;
            $this->line("  + {$slug} → {$product->id}");
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $files
     * @param  array<string, string>  $productIdMap
     * @return array<string, string> Slug → Stripe-Price-ID
     */
    protected function syncPrices(StripeClient $stripe, array $files, array $productIdMap, bool $dryRun): array
    {
        $this->info('2/2 Prices synchronisieren …');

        $envOutput = [];

        foreach ($files as $file) {
            $slug = $this->slugFromFilename($file, 'price');
            $data = $this->readJson($file);
            $bootstrapProductId = $data['product'] ?? null;

            if ($bootstrapProductId === null || ! isset($productIdMap[$bootstrapProductId])) {
                $this->warn("  ! {$slug}: Bootstrap-Product {$bootstrapProductId} nicht gemappt – übersprungen");

                continue;
            }

            $existing = $this->findPriceByLookupKey($stripe, $slug);

            if ($existing !== null) {
                $envOutput[$slug] = $existing->id;
                $this->line("  ↺ {$slug} → {$existing->id} (vorhanden)");

                continue;
            }

            if ($dryRun) {
                $envOutput[$slug] = 'price_dryrun_'.$slug;
                $this->line("  + {$slug} (würde angelegt: {$data['unit_amount']} {$data['currency']})");

                continue;
            }

            $params = [
                'product' => $productIdMap[$bootstrapProductId],
                'currency' => $data['currency'],
                'unit_amount' => $data['unit_amount'],
                'lookup_key' => $slug,
                'metadata' => ['planb_key' => $slug],
            ];

            if (! empty($data['recurring'])) {
                $params['recurring'] = [
                    'interval' => $data['recurring']['interval'],
                    'interval_count' => $data['recurring']['interval_count'] ?? 1,
                ];
            }

            $price = $stripe->prices->create($params);
            $envOutput[$slug] = $price->id;
            $this->line("  + {$slug} → {$price->id}");
        }

        return $envOutput;
    }

    protected function findProductBySlug(StripeClient $stripe, string $slug): ?Product
    {
        $result = $stripe->products->search([
            'query' => "active:'true' AND metadata['planb_key']:'{$slug}'",
            'limit' => 1,
        ]);

        return $result->data[0] ?? null;
    }

    protected function findPriceByLookupKey(StripeClient $stripe, string $slug): ?Price
    {
        $result = $stripe->prices->all([
            'lookup_keys' => [$slug],
            'active' => true,
            'limit' => 1,
        ]);

        return $result->data[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    protected function readJson(string $path): array
    {
        return json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
    }

    protected function slugFromFilename(string $path, string $prefix): string
    {
        return substr(basename($path, '.json'), strlen($prefix) + 1);
    }
}
