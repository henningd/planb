<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the app section links to the real App Store entry', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('https://apps.apple.com/de/app/planb-notfallhandbuch/id6787493918', escape: false)
        ->assertSee('App Store');
});

test('the app section shows the iPhone and iPad screenshot carousels', function () {
    $response = $this->get('/')->assertOk();

    $response->assertSee('app-carousel-iphone')
        ->assertSee('app-carousel-ipad')
        ->assertSee('Auf dem iPhone')
        ->assertSee('Auf dem iPad');
});

test('the app section embeds the real app screenshots', function () {
    $response = $this->get('/')->assertOk();

    foreach ([
        'iphone-01-home.webp',
        'iphone-02-szenarien.webp',
        'iphone-03-krisenstab.webp',
        'iphone-04-wiederanlauf.webp',
        'ipad-02-home-landscape.webp',
        'ipad-03-krisenstab.webp',
        'ipad-04-wiederanlauf-zeitplan.webp',
        'ipad-05-szenarien.webp',
        'app-icon.webp',
    ] as $file) {
        $response->assertSee('images/app/'.$file, escape: false);
        expect(public_path('images/app/'.$file))->toBeFile();
    }
});
