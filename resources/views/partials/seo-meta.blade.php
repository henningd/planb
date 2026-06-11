{{--
    SEO-Meta-Block für Marketing-Unterseiten: Canonical, Open-Graph/Twitter
    und BreadcrumbList-JSON-LD.

    Erwartete Variablen:
      $seoTitle        – Seitentitel für OG/Twitter
      $seoDescription  – Meta-Description
      $seoUrl          – kanonische URL der Seite
      $seoBreadcrumbs  – optional: array<int, array{name: string, item: string}>
--}}
@php
    $seoSiteName = \App\Support\Settings\SystemSetting::get('platform_name') ?: config('app.name', 'PlanB');
@endphp

<link rel="canonical" href="{{ $seoUrl }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $seoSiteName }}">
<meta property="og:locale" content="de_DE">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:image" content="{{ url('/og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ url('/og-image.png') }}">

@if (! empty($seoBreadcrumbs))
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($seoBreadcrumbs)->values()->map(fn ($crumb, $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['item'],
            ])->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endif
