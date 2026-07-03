@php
    $checkUrl = route('nis2-quick-check');
    $browserTitle = 'NIS2 Quick-Check: In 5 Minuten zur Selbsteinschätzung';
    $metaDescription = 'Kostenloser NIS2 Quick-Check für kleine und mittelständische Unternehmen: 10 Fragen, sofortiger Reifegrad und individuelle Handlungsempfehlungen zu Meldepflicht, Risikomanagement, Notfallvorsorge und Lieferkette.';
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $metaDescription }}">
    <title>{{ $browserTitle }} | {{ $productName }}</title>

    @include('partials.seo-meta', [
        'seoTitle' => $browserTitle,
        'seoDescription' => $metaDescription,
        'seoUrl' => $checkUrl,
        'seoBreadcrumbs' => [
            ['name' => $productName, 'item' => route('home')],
            ['name' => 'NIS2 Quick-Check', 'item' => $checkUrl],
        ],
    ])

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

    @include('partials.marketing-header')

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-slate-50 via-white to-white"></div>
        <div class="max-w-4xl mx-auto px-6 lg:px-8 pt-16 lg:pt-20 pb-6">
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                Kostenlos · ca. 5 Minuten · keine Anmeldung nötig
            </div>
            <h1 class="mt-4 text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900 leading-tight">
                NIS2 Quick-Check
            </h1>
            <p class="mt-6 text-lg text-slate-700 leading-relaxed max-w-2xl">
                Wie gut ist Ihr Unternehmen auf die NIS2-Anforderungen vorbereitet? Beantworten Sie
                10 kurze Fragen und erhalten Sie sofort Ihren Reifegrad — mit konkreten
                Handlungsempfehlungen als PDF.
            </p>
        </div>
    </section>

    {{-- ============ QUICK-CHECK ============ --}}
    <section class="pb-16 lg:pb-20">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <livewire:nis2-quick-check />
        </div>
    </section>

    {{-- ============ PRODUKT-CTA ============ --}}
    <section class="pb-16 lg:pb-20">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 px-8 py-10 lg:px-12 lg:py-14 text-white">
                <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                    Von der Selbsteinschätzung zur gelebten Vorsorge
                </h2>
                <p class="mt-3 text-slate-300 leading-relaxed">
                    Der Quick-Check zeigt Ihre Lücken. {{ $productName }} schließt sie strukturiert:
                    Notfallhandbuch, Rollen, Wiederanlaufpläne, Meldeprozesse und Compliance-Nachweise —
                    an einem Ort.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition">
                        Funktionen ansehen
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="{{ route('guides.show', 'nis2-checkliste') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                        NIS2-Checkliste lesen
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('partials.marketing-footer')

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
