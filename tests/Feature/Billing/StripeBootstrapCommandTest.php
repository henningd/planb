<?php

use App\Console\Commands\StripeBootstrap;

test('bricht ab, wenn STRIPE_SECRET fehlt', function () {
    config(['cashier.secret' => null]);

    $this->artisan('stripe:bootstrap')
        ->expectsOutputToContain('STRIPE_SECRET ist nicht gesetzt')
        ->assertExitCode(1);
});

test('bricht ab, wenn das Bootstrap-Verzeichnis nicht existiert', function () {
    config(['cashier.secret' => 'sk_test_dummy']);

    $this->artisan('stripe:bootstrap', ['--path' => '/tmp/does-not-exist-'.uniqid()])
        ->expectsOutputToContain('Verzeichnis nicht gefunden')
        ->assertExitCode(1);
});

test('bricht ab, wenn keine JSONs im Verzeichnis liegen', function () {
    config(['cashier.secret' => 'sk_test_dummy']);

    $empty = sys_get_temp_dir().'/stripe-bootstrap-empty-'.uniqid();
    mkdir($empty);

    try {
        $this->artisan('stripe:bootstrap', ['--path' => $empty])
            ->expectsOutputToContain('Keine Bootstrap-JSONs')
            ->assertExitCode(1);
    } finally {
        rmdir($empty);
    }
});

test('das ENV_MAP deckt alle in config/billing.php erwarteten Plan- und Add-on-Keys ab', function () {
    $expectedEnvVars = [
        'STRIPE_PRICE_STARTER_MONTHLY',
        'STRIPE_PRICE_STARTER_YEARLY',
        'STRIPE_PRICE_ADVANCED_MONTHLY',
        'STRIPE_PRICE_ADVANCED_YEARLY',
        'STRIPE_PRICE_ADDON_WORKSHOP',
        'STRIPE_PRICE_ADDON_COACHING_HOUR',
        'STRIPE_PRICE_ADDON_COACHING_RETAINER',
        'STRIPE_PRICE_ADDON_EXTRA_USER',
    ];

    expect(array_values(StripeBootstrap::ENV_MAP))
        ->toEqualCanonicalizing($expectedEnvVars);
});

test('für jeden Slug im ENV_MAP existiert eine passende Bootstrap-JSON', function () {
    foreach (array_keys(StripeBootstrap::ENV_MAP) as $slug) {
        expect(storage_path("stripe-bootstrap/price-{$slug}.json"))->toBeFile();
    }
});
