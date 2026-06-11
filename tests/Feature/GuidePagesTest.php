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

test('every guide cross-links all other guides', function () {
    $slugs = GuideCatalog::slugs();

    foreach ($slugs as $slug) {
        $html = $this->get(route('guides.show', $slug))->getContent();

        foreach ($slugs as $other) {
            if ($other !== $slug) {
                expect($html)->toContain(route('guides.show', $other));
            }
        }
    }
});

test('the home page links to every guide', function () {
    $response = $this->get(route('home'))->assertOk();

    foreach (GuideCatalog::slugs() as $slug) {
        $response->assertSee(route('guides.show', $slug), false);
    }
});

test('the new guides cover their target keywords in depth', function () {
    $expectations = [
        'it-notfallplan' => ['IT-Notfallplan', 'RTO', 'Wiederanlauf'],
        'bsi-200-4' => ['BSI-Standard 200-4', 'Business-Impact-Analyse', 'BCMS'],
        'nis2-checkliste' => ['NIS2', 'Meldepflichten', 'Geschäftsleitung'],
    ];

    foreach ($expectations as $slug => $keywords) {
        $html = $this->get(route('guides.show', $slug))->assertOk()->getContent();

        foreach ($keywords as $keyword) {
            expect(substr_count($html, $keyword))->toBeGreaterThanOrEqual(2, "[$slug] erwartet '$keyword' mindestens 2x");
        }
    }
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

test('the guide hub lists every guide with canonical and collection schema', function () {
    $response = $this->get(route('guides.index'))->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('<link rel="canonical" href="'.route('guides.index').'">')
        ->and($html)->toContain('"CollectionPage"');

    foreach (GuideCatalog::slugs() as $slug) {
        expect($html)->toContain(route('guides.show', $slug));
    }
});

test('guides show a table of contents, a last-updated date and dateModified schema', function () {
    $html = $this->get(route('guides.show', 'notfallhandbuch'))->getContent();

    expect($html)->toContain('Inhaltsverzeichnis')
        ->and($html)->toContain('Stand: 11.06.2026')
        ->and($html)->toContain('"dateModified":"2026-06-11"')
        ->and($html)->toContain('id="was-ist-ein-notfallhandbuch"')
        ->and($html)->toContain('href="#was-ist-ein-notfallhandbuch"');
});

test('the sitemap lists the guide hub and lastmod dates', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<loc>'.route('guides.index').'</loc>')
        ->and($xml)->toContain('<lastmod>2026-06-11</lastmod>');
});

test('the header navigation links to the guide hub on every public page', function () {
    foreach ([route('home'), route('pricing.show'), route('guides.show', 'notfallhandbuch')] as $url) {
        $this->get($url)->assertOk()->assertSee(route('guides.index'), false);
    }
});
