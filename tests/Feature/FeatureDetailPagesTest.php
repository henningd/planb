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

    $this->get('/funktionen/'.$slug)
        ->assertOk()
        ->assertSeeText('Screenshot folgt');
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
