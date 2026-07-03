@php
    $contactEmail = (string) \App\Support\Settings\SystemSetting::get('platform_contact_email');
    $companyName = 'Arento AI GmbH';
    $guideUrl = route('guides.show', $guide['slug']);
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $guide['meta_description'] }}">
    <title>{{ $guide['browser_title'] }} | {{ $productName }}</title>

    <link rel="canonical" href="{{ $guideUrl }}">

    <meta property="og:type" content="article">
    <meta property="og:site_name" content="{{ $productName }}">
    <meta property="og:locale" content="de_DE">
    <meta property="og:url" content="{{ $guideUrl }}">
    <meta property="og:title" content="{{ $guide['browser_title'] }}">
    <meta property="og:description" content="{{ $guide['meta_description'] }}">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="article:published_time" content="{{ \Illuminate\Support\Carbon::parse($guide['updated'], 'Europe/Berlin')->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ \Illuminate\Support\Carbon::parse($guide['updated'], 'Europe/Berlin')->toIso8601String() }}">
    <meta property="article:author" content="{{ $productName }}">
    <meta name="author" content="{{ $productName }} – Arento AI GmbH">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $guide['browser_title'] }}">
    <meta name="twitter:description" content="{{ $guide['meta_description'] }}">
    <meta name="twitter:image" content="{{ url('/og-image.png') }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Article',
                    'headline' => $guide['title'],
                    'description' => $guide['meta_description'],
                    'inLanguage' => 'de',
                    'mainEntityOfPage' => $guideUrl,
                    'datePublished' => $guide['updated'],
                    'dateModified' => $guide['updated'],
                    'author' => ['@type' => 'Organization', 'name' => $productName],
                    'publisher' => ['@type' => 'Organization', 'name' => $productName, 'url' => route('home')],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => $productName, 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $guide['title'], 'item' => $guideUrl],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => collect($guide['faqs'])->map(fn ($faq) => [
                        '@type' => 'Question',
                        'name' => $faq['q'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
                    ])->all(),
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

    @include('partials.marketing-header')

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-slate-50 via-white to-white"></div>
        <div class="max-w-3xl mx-auto px-6 lg:px-8 pt-16 lg:pt-20 pb-10">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium ring-1 ring-indigo-100">
                    Ratgeber
                </span>
                <span class="text-xs text-slate-500">
                    Stand: {{ \Illuminate\Support\Carbon::parse($guide['updated'])->format('d.m.Y') }}
                </span>
            </div>
            <h1 class="mt-6 text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900 leading-tight">
                {{ $guide['title'] }}
            </h1>
            <p class="mt-4 text-xl text-slate-600 leading-relaxed">{{ $guide['tagline'] }}</p>
            <p class="mt-6 text-lg text-slate-700 leading-relaxed">{{ $guide['lead'] }}</p>

            @if (! empty($guide['image']) && file_exists(public_path($guide['image']['src'])))
                <figure class="mt-8">
                    <img
                        src="{{ asset($guide['image']['src']) }}"
                        alt="{{ $guide['image']['alt'] }}"
                        class="w-full rounded-2xl ring-1 ring-slate-200 shadow-sm"
                        decoding="async"
                    >
                </figure>
            @endif

            <nav class="mt-8 rounded-xl bg-slate-50 ring-1 ring-slate-200 p-5" aria-label="Inhaltsverzeichnis">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Inhalt</div>
                <ol class="mt-3 space-y-2 text-sm">
                    @foreach ($guide['sections'] as $section)
                        <li>
                            <a href="#{{ \Illuminate\Support\Str::slug($section['heading']) }}" class="text-indigo-600 hover:text-indigo-700 transition">
                                {{ $section['heading'] }}
                            </a>
                        </li>
                    @endforeach
                    <li><a href="#haeufige-fragen" class="text-indigo-600 hover:text-indigo-700 transition">Häufige Fragen</a></li>
                </ol>
            </nav>
        </div>
    </section>

    {{-- ============ INHALT ============ --}}
    <section class="py-12 lg:py-16">
        <div class="max-w-3xl mx-auto px-6 lg:px-8 grid gap-12">
            @foreach ($guide['sections'] as $section)
                <div id="{{ \Illuminate\Support\Str::slug($section['heading']) }}" class="scroll-mt-24">
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $section['heading'] }}</h2>
                    @foreach ($section['paragraphs'] as $paragraph)
                        <p class="mt-4 text-slate-700 leading-relaxed">{{ $paragraph }}</p>
                    @endforeach
                    @if (! empty($section['list']))
                        <ul class="mt-5 space-y-3">
                            @foreach ($section['list'] as $item)
                                <li class="flex gap-3">
                                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-slate-700 leading-relaxed">
                                        <span class="font-medium text-slate-900">{{ $item['title'] }}:</span>
                                        {{ $item['text'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($section['feature']))
                        <a href="{{ route('feature.show', $section['feature']['slug']) }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100 transition">
                            Passende Funktion: {{ $section['feature']['label'] }}
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section id="haeufige-fragen" class="py-12 lg:py-16 bg-slate-50 border-y border-slate-100 scroll-mt-24">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Häufige Fragen</h2>
            <div class="mt-8 space-y-4">
                @foreach ($guide['faqs'] as $faq)
                    <details class="group rounded-xl bg-white ring-1 ring-slate-200 p-6">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 font-medium text-slate-900 list-none">
                            {{ $faq['q'] }}
                            <svg class="w-5 h-5 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600 leading-relaxed">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ NIS2 QUICK-CHECK ============ --}}
    <section class="pb-4">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <a href="{{ route('nis2-quick-check') }}" class="group flex flex-wrap items-center justify-between gap-4 rounded-2xl ring-1 ring-indigo-200 bg-indigo-50/60 px-6 py-5 hover:ring-indigo-300 hover:bg-indigo-50 transition">
                <div>
                    <div class="font-semibold text-slate-900">Wie NIS2-fest ist Ihr Unternehmen?</div>
                    <p class="mt-1 text-sm text-slate-600 leading-relaxed">Machen Sie den kostenlosen NIS2 Quick-Check – 10 Fragen, sofortiger Reifegrad, PDF-Auswertung.</p>
                </div>
                <span class="inline-flex items-center gap-2 shrink-0 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white group-hover:bg-indigo-700 transition">
                    Quick-Check starten
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </span>
            </a>
        </div>
    </section>

    {{-- ============ WEITERLESEN ============ --}}
    <section class="py-12 lg:py-16">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Weiterlesen</div>
            <div class="mt-4 grid gap-3">
                @foreach (\App\Support\Marketing\GuideCatalog::all() as $otherSlug => $other)
                    @continue($otherSlug === $guide['slug'])
                    <a href="{{ route('guides.show', $otherSlug) }}" class="group flex items-center justify-between gap-4 rounded-xl ring-1 ring-slate-200 p-5 hover:ring-indigo-300 hover:bg-indigo-50/40 transition">
                        <div class="font-medium text-slate-900">{{ $other['title'] }}</div>
                        <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="py-16 lg:py-20">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 px-8 py-10 lg:px-12 lg:py-14 text-white">
                <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                    Vom Ratgeber zum eigenen Notfallhandbuch.
                </h2>
                <p class="mt-3 text-slate-300 leading-relaxed">
                    {{ $productName }} führt Schritt für Schritt durch alle Bausteine – von Rollen und Wiederanlaufplänen bis zu Szenario-Checklisten und Compliance-Nachweisen.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition">
                            Kostenlos starten
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif
                    <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                        Funktionen ansehen
                    </a>
                    @if ($contactEmail !== '')
                        <a href="mailto:{{ $contactEmail }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                            {{ $contactEmail }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @include('partials.marketing-footer')
</body>
</html>
