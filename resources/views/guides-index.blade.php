@php
    $hubUrl = route('guides.index');
    $hubTitle = 'Ratgeber: Notfallhandbuch, Krisenmanagement & Compliance';
    $hubDescription = 'Praxis-Ratgeber für den Mittelstand: Notfallhandbuch erstellen, Krisenmanagement aufbauen, IT-Notfallplan, BSI 200-4 und NIS2 umsetzen – verständlich und umsetzbar.';
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $hubDescription }}">
    <title>{{ $hubTitle }} | {{ $productName }}</title>
    @include('partials.seo-meta', [
        'seoTitle' => $hubTitle,
        'seoDescription' => $hubDescription,
        'seoUrl' => $hubUrl,
        'seoBreadcrumbs' => [
            ['name' => $productName, 'item' => route('home')],
            ['name' => 'Ratgeber', 'item' => $hubUrl],
        ],
    ])

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $hubTitle,
            'description' => $hubDescription,
            'url' => $hubUrl,
            'inLanguage' => 'de',
            'hasPart' => collect(\App\Support\Marketing\GuideCatalog::all())->values()->map(fn ($guide) => [
                '@type' => 'Article',
                'headline' => $guide['title'],
                'url' => route('guides.show', $guide['slug']),
            ])->all(),
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
        <div class="max-w-4xl mx-auto px-6 lg:px-8 pt-16 lg:pt-20 pb-10">
            <h1 class="text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900 leading-tight">
                Ratgeber für Notfallvorsorge &amp; Krisenmanagement
            </h1>
            <p class="mt-6 text-lg text-slate-700 leading-relaxed max-w-2xl">
                Praxisnah, ohne Beraterdeutsch: Schritt-für-Schritt-Anleitungen für Notfallhandbuch,
                Krisenmanagement und Compliance im Mittelstand — von Menschen geschrieben, die wissen,
                dass im Ernstfall niemand Zeit für 80-seitige Konzepte hat.
            </p>
        </div>
    </section>

    {{-- ============ RATGEBER-LISTE ============ --}}
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 grid gap-6">
            @foreach (\App\Support\Marketing\GuideCatalog::all() as $slug => $guide)
                <a href="{{ route('guides.show', $slug) }}" class="group rounded-2xl ring-1 ring-slate-200 p-6 lg:p-8 hover:ring-indigo-300 hover:bg-indigo-50/40 transition">
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900 group-hover:text-indigo-700 transition">
                                {{ $guide['title'] }}
                            </h2>
                            <p class="mt-3 text-slate-600 leading-relaxed">{{ $guide['lead'] }}</p>
                            <div class="mt-4 flex items-center gap-3 text-xs text-slate-500">
                                <span>Stand: {{ \Illuminate\Support\Carbon::parse($guide['updated'])->format('d.m.Y') }}</span>
                                <span>·</span>
                                <span>{{ count($guide['sections']) }} Kapitel</span>
                                <span>·</span>
                                <span>{{ count($guide['faqs']) }} FAQ</span>
                            </div>
                        </div>
                        <svg class="w-6 h-6 shrink-0 mt-1 text-slate-300 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="py-16 lg:py-20">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 px-8 py-10 lg:px-12 lg:py-14 text-white">
                <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                    Lieber direkt loslegen statt nur lesen?
                </h2>
                <p class="mt-3 text-slate-300 leading-relaxed">
                    {{ $productName }} führt Schritt für Schritt durch alle Bausteine des Notfallhandbuchs —
                    inklusive Compliance-Nachweisen für NIS2 und BSI 200-4.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition">
                            Kostenlos starten
                        </a>
                    @endif
                    <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                        Funktionen ansehen
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('partials.marketing-footer')
</body>
</html>
