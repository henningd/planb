<?php

use App\Support\Manual\ManualCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('renders the manual index with all categories', function () {
    $this->get('/handbuch')
        ->assertOk()
        ->assertSeeText('Benutzerhandbuch')
        ->assertSeeText('Erste Schritte')
        ->assertSeeText('Stammdaten')
        ->assertSeeText('Ernstfall')
        ->assertSeeText('Compliance');
});

it('renders every chapter with its markdown content', function (string $slug) {
    $entry = ManualCatalog::find($slug);

    $this->get('/handbuch/'.$slug)
        ->assertOk()
        ->assertSeeText($entry['title']);
})->with(array_column(ManualCatalog::all(), 'slug'));

it('returns 404 for an unknown slug', function () {
    $this->get('/handbuch/does-not-exist')->assertNotFound();
});

it('exposes the manual.index and manual.show routes', function () {
    expect(Route::has('manual.index'))->toBeTrue();
    expect(Route::has('manual.show'))->toBeTrue();
});

it('links the manual from the welcome page footer', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('manual.index'), false)
        ->assertSeeText('Benutzerhandbuch');
});

it('every catalog entry has a markdown file', function (string $slug) {
    expect(ManualCatalog::content($slug))
        ->not->toBeNull()
        ->and(ManualCatalog::content($slug))
        ->not->toBe('');
})->with(array_column(ManualCatalog::all(), 'slug'));
