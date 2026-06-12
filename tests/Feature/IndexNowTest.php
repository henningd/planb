<?php

use App\Support\IndexNow;
use App\Support\Marketing\GuideCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('the ping command submits every marketing url to indexnow', function () {
    config()->set('services.indexnow.key', 'test-key-1234567890');
    Http::fake([IndexNow::ENDPOINT => Http::response('', 200)]);

    $this->artisan('indexnow:ping')->assertSuccessful();

    Http::assertSent(function ($request) {
        $urls = $request['urlList'];

        return $request->url() === IndexNow::ENDPOINT
            && $request['key'] === 'test-key-1234567890'
            && in_array(route('home'), $urls, true)
            && in_array(route('guides.show', 'nis2-checkliste'), $urls, true)
            && count($urls) === 3 + count(GuideCatalog::slugs()) + 6;
    });
});

test('single urls can be pinged explicitly', function () {
    config()->set('services.indexnow.key', 'test-key-1234567890');
    Http::fake([IndexNow::ENDPOINT => Http::response('', 200)]);

    $this->artisan('indexnow:ping', ['url' => [route('pricing.show')]])->assertSuccessful();

    Http::assertSent(fn ($request) => $request['urlList'] === [route('pricing.show')]);
});

test('the command is a no-op without a configured key', function () {
    config()->set('services.indexnow.key', '');
    Http::fake();

    $this->artisan('indexnow:ping')->assertSuccessful();

    Http::assertNothingSent();
});

test('a failed submission is reported without throwing', function () {
    config()->set('services.indexnow.key', 'test-key-1234567890');
    Http::fake([IndexNow::ENDPOINT => Http::response('', 500)]);

    $this->artisan('indexnow:ping')->assertFailed();
});
