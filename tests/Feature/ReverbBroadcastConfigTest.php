<?php

/**
 * Setzt Umgebungswerte in $_ENV/$_SERVER (Laravels env() liest dort, putenv ist
 * standardmäßig deaktiviert), lädt die Broadcasting-Config frisch und räumt auf.
 *
 * @param  array<string, ?string>  $vars
 */
function withReverbEnv(array $vars, Closure $assert): void
{
    $original = [];
    foreach ($vars as $key => $value) {
        $original[$key] = $_ENV[$key] ?? null;
        if ($value === null) {
            unset($_ENV[$key], $_SERVER[$key]);
        } else {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    try {
        $config = require base_path('config/broadcasting.php');
        $assert($config['connections']['reverb']['options']);
    } finally {
        foreach ($original as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

it('publishes broadcasts to the internal reverb api host, not the public one', function () {
    withReverbEnv([
        'REVERB_HOST' => 'ws.example.test',
        'REVERB_PORT' => '443',
        'REVERB_SCHEME' => 'https',
        'REVERB_API_HOST' => '127.0.0.1',
        'REVERB_API_PORT' => '8081',
        'REVERB_API_SCHEME' => 'http',
    ], function (array $options) {
        expect($options['host'])->toBe('127.0.0.1')
            ->and((string) $options['port'])->toBe('8081')
            ->and($options['scheme'])->toBe('http')
            ->and($options['useTLS'])->toBeFalse();
    });
});

it('falls back to the public reverb host when no api host is configured', function () {
    withReverbEnv([
        'REVERB_HOST' => 'ws.example.test',
        'REVERB_PORT' => '443',
        'REVERB_SCHEME' => 'https',
        'REVERB_API_HOST' => null,
        'REVERB_API_PORT' => null,
        'REVERB_API_SCHEME' => null,
    ], function (array $options) {
        expect($options['host'])->toBe('ws.example.test')
            ->and($options['useTLS'])->toBeTrue();
    });
});
