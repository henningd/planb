<?php

use App\Support\Manual\ManualCatalog;
use App\Support\Manual\ManualRenderer;
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

it('renders GFM tables as html tables', function () {
    $html = ManualRenderer::toHtml(<<<'MD'
        | Spalte A | Spalte B |
        |---|---|
        | Wert 1 | Wert 2 |
        MD);

    expect($html)
        ->toContain('<table>')
        ->toContain('<th>Spalte A</th>')
        ->toContain('<td>Wert 1</td>');
});

it('renders the rolle-chapter table as actual html table', function () {
    $this->get('/handbuch/rollen')
        ->assertOk()
        ->assertSee('<table>', false)
        ->assertSee('<th>Rolle</th>', false);
});

it('every catalog entry has a markdown file', function (string $slug) {
    expect(ManualCatalog::content($slug))
        ->not->toBeNull()
        ->and(ManualCatalog::content($slug))
        ->not->toBe('');
})->with(array_column(ManualCatalog::all(), 'slug'));

it('finds chapters by title via search', function () {
    $hits = ManualCatalog::search('Risiko');

    expect($hits)->not->toBeEmpty();
    expect(collect($hits)->pluck('entry.slug'))->toContain('risiken');
});

it('ranks title matches above content matches', function () {
    $hits = ManualCatalog::search('Glossar');
    $first = $hits[0] ?? null;

    expect($first)->not->toBeNull();
    expect($first['entry']['slug'])->toBe('glossar');
});

it('returns empty array for queries shorter than two characters', function () {
    expect(ManualCatalog::search(''))->toBe([]);
    expect(ManualCatalog::search('a'))->toBe([]);
});

it('renders the search form on the manual index', function () {
    $this->get('/handbuch')
        ->assertOk()
        ->assertSee('name="q"', false)
        ->assertSee('Im Handbuch suchen', false);
});

it('shows search results with snippets when q is provided', function () {
    $this->get('/handbuch?q=Risiko')
        ->assertOk()
        ->assertSeeText('Treffer für „Risiko"')
        ->assertSee('/handbuch/risiken', false);
});

it('shows a friendly message when search has no hits', function () {
    $this->get('/handbuch?q=zzzunwahrscheinlich')
        ->assertOk()
        ->assertSeeText('Keine passenden Kapitel gefunden');
});
