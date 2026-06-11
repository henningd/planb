<?php

use App\Support\Marketing\FeatureCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the home page exposes title, description and canonical with target keywords', function () {
    $response = $this->get(route('home'))->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('Digitales Notfallhandbuch &amp; Krisenmanagement')
        ->and($html)->toContain('<link rel="canonical" href="'.route('home').'">')
        ->and($html)->toContain('name="description"')
        ->and($html)->toContain('Krisenmanagement für kleine und mittelständische Unternehmen');
});

test('the home page exposes open graph and twitter tags', function () {
    $html = $this->get(route('home'))->getContent();

    expect($html)->toContain('property="og:title"')
        ->and($html)->toContain('property="og:description"')
        ->and($html)->toContain('property="og:url" content="'.route('home').'"')
        ->and($html)->toContain('property="og:locale" content="de_DE"')
        ->and($html)->toContain('name="twitter:card"');
});

test('the home page contains valid json-ld for software application and faq', function () {
    $html = $this->get(route('home'))->getContent();

    preg_match_all('/<script type="application\/ld\+json">\s*(.+?)\s*<\/script>/s', $html, $matches);

    expect($matches[1])->toHaveCount(2);

    $graph = json_decode($matches[1][0], true);
    $faq = json_decode($matches[1][1], true);

    expect($graph)->not->toBeNull()
        ->and(collect($graph['@graph'])->pluck('@type')->all())
        ->toContain('Organization', 'WebSite', 'SoftwareApplication')
        ->and($faq)->not->toBeNull()
        ->and($faq['@type'])->toBe('FAQPage')
        ->and(count($faq['mainEntity']))->toBeGreaterThanOrEqual(4);
});

test('the home page shows a visible faq section matching the structured data', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Häufige Fragen zu Notfallhandbuch')
        ->assertSee('Was ist ein Notfallhandbuch?')
        ->assertSee('das Krisenmanagement?');
});

test('the sitemap lists home, pricing and all feature pages', function () {
    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = $response->getContent();

    expect($xml)->toContain('<loc>'.route('home').'</loc>')
        ->and($xml)->toContain('<loc>'.route('pricing.show').'</loc>');

    foreach (FeatureCatalog::slugs() as $slug) {
        expect($xml)->toContain('<loc>'.route('feature.show', $slug).'</loc>');
    }
});

test('pricing and feature pages expose canonical, open graph and breadcrumbs', function () {
    $urls = [
        route('pricing.show'),
        route('feature.show', 'compliance-dashboard'),
    ];

    foreach ($urls as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('<link rel="canonical" href="'.$url.'">')
            ->and($html)->toContain('property="og:title"')
            ->and($html)->toContain('property="og:url" content="'.$url.'"')
            ->and($html)->toContain('"BreadcrumbList"');
    }
});

test('every public page exposes the social share image', function () {
    expect(file_exists(public_path('og-image.png')))->toBeTrue();

    $urls = [
        route('home'),
        route('pricing.show'),
        route('guides.index'),
        route('guides.show', 'notfallhandbuch'),
        route('feature.show', 'compliance-dashboard'),
    ];

    foreach ($urls as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('property="og:image" content="'.url('/og-image.png').'"')
            ->and($html)->toContain('name="twitter:card" content="summary_large_image"');
    }
});

test('robots.txt references the sitemap', function () {
    expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap:');
});
