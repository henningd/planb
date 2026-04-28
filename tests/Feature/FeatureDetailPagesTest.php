<?php

use App\Support\Marketing\FeatureCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('exposes a detail page for every catalog entry', function (string $slug) {
    $this->get('/funktionen/'.$slug)
        ->assertOk()
        ->assertSeeText(FeatureCatalog::find($slug)['title'])
        ->assertSeeText(FeatureCatalog::find($slug)['tagline']);
})->with(FeatureCatalog::slugs());

it('returns 404 for an unknown feature slug', function () {
    $this->get('/funktionen/does-not-exist')->assertNotFound();
});

it('shows a placeholder when a screenshot file is missing', function () {
    $slug = FeatureCatalog::slugs()[0];
    $feature = FeatureCatalog::find($slug);
    $screenshotFile = $feature['screenshots'][0]['file'];
    $path = public_path('screenshots/'.$screenshotFile);
    $backup = $path.'.bak';

    $hadFile = file_exists($path);
    if ($hadFile) {
        rename($path, $backup);
    }

    try {
        $this->get('/funktionen/'.$slug)
            ->assertOk()
            ->assertSeeText('Screenshot folgt');
    } finally {
        if ($hadFile && file_exists($backup)) {
            rename($backup, $path);
        }
    }
});

it('links every feature card on the home page to a detail route', function () {
    $response = $this->get('/');
    $response->assertOk();

    foreach (FeatureCatalog::slugs() as $slug) {
        $response->assertSee(route('feature.show', $slug), false);
    }
});

it('exposes the named feature.show route', function () {
    expect(Route::has('feature.show'))->toBeTrue();
});
