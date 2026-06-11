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
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $guide['browser_title'] }}">
    <meta name="twitter:description" content="{{ $guide['meta_description'] }}">

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

    {{-- ============ NAVIGATION ============ --}}
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 text-white shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </span>
                <span class="font-semibold text-slate-900 tracking-tight">{{ $productName }}</span>
            </a>
            <a href="{{ route('home') }}" class="text-sm text-slate-600 hover:text-slate-900 transition">
                ← {{ __('Zur Startseite') }}
            </a>
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-slate-50 via-white to-white"></div>
        <div class="max-w-3xl mx-auto px-6 lg:px-8 pt-16 lg:pt-20 pb-10">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium ring-1 ring-indigo-100">
                Ratgeber
            </span>
            <h1 class="mt-6 text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900 leading-tight">
                {{ $guide['title'] }}
            </h1>
            <p class="mt-4 text-xl text-slate-600 leading-relaxed">{{ $guide['tagline'] }}</p>
            <p class="mt-6 text-lg text-slate-700 leading-relaxed">{{ $guide['lead'] }}</p>
        </div>
    </section>

    {{-- ============ INHALT ============ --}}
    <section class="py-12 lg:py-16">
        <div class="max-w-3xl mx-auto px-6 lg:px-8 grid gap-12">
            @foreach ($guide['sections'] as $section)
                <div>
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
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section class="py-12 lg:py-16 bg-slate-50 border-y border-slate-100">
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

    {{-- ============ WEITERLESEN ============ --}}
    <section class="py-12 lg:py-16">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <a href="{{ route('guides.show', $guide['related_slug']) }}" class="group flex items-center justify-between gap-4 rounded-xl ring-1 ring-slate-200 p-6 hover:ring-indigo-300 hover:bg-indigo-50/40 transition">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Weiterlesen</div>
                    <div class="mt-1 font-medium text-slate-900">{{ $guide['related_label'] }}</div>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </a>
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

    {{-- ============ FOOTER ============ --}}
    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-sm text-slate-500">
            <div>&copy; {{ date('Y') }} {{ $companyName }}.</div>
            <div class="flex items-center gap-6">
                <a href="{{ route('guides.show', $guide['related_slug']) }}" class="hover:text-slate-900 transition">{{ ucfirst($guide['related_slug']) }}</a>
                <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a>
            </div>
        </div>
    </footer>
</body>
</html>
