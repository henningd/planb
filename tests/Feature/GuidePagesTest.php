<?php

use App\Support\Marketing\GuideCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every guide page renders with title, canonical and structured data', function () {
    foreach (GuideCatalog::all() as $slug => $guide) {
        $response = $this->get(route('guides.show', $slug))->assertOk();
        $html = $response->getContent();

        expect($html)->toContain('<h1')
            ->and($html)->toContain(e($guide['title']))
            ->and($html)->toContain('<link rel="canonical" href="'.route('guides.show', $slug).'">')
            ->and($html)->toContain('property="og:type" content="article"')
            ->and($html)->toContain('"FAQPage"')
            ->and($html)->toContain('"Article"')
            ->and($html)->toContain('"BreadcrumbList"');
    }
});

test('the notfallhandbuch guide covers the target keyword in depth', function () {
    $html = $this->get(route('guides.show', 'notfallhandbuch'))->getContent();

    expect(substr_count($html, 'Notfallhandbuch'))->toBeGreaterThanOrEqual(10)
        ->and($html)->toContain('BSI-Standard 200-4')
        ->and($html)->toContain('NIS2');
});

test('the krisenmanagement guide covers the target keyword in depth', function () {
    $html = $this->get(route('guides.show', 'krisenmanagement'))->getContent();

    expect(substr_count($html, 'Krisenmanagement'))->toBeGreaterThanOrEqual(8)
        ->and($html)->toContain('Krisenstab')
        ->and($html)->toContain('Lessons Learned');
});

test('guides cross-link each other', function () {
    $this->get(route('guides.show', 'notfallhandbuch'))
        ->assertSee(route('guides.show', 'krisenmanagement'), false);

    $this->get(route('guides.show', 'krisenmanagement'))
        ->assertSee(route('guides.show', 'notfallhandbuch'), false);
});

test('the home page links to both guides', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('guides.show', 'notfallhandbuch'), false)
        ->assertSee(route('guides.show', 'krisenmanagement'), false);
});

test('the sitemap lists both guide pages', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    foreach (GuideCatalog::slugs() as $slug) {
        expect($xml)->toContain('<loc>'.route('guides.show', $slug).'</loc>');
    }
});

test('unknown guide slugs return 404', function () {
    $this->get('/unbekannter-ratgeber')->assertNotFound();
});
