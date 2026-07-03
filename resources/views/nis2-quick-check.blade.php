@php
    $checkUrl = route('nis2-quick-check');
    $companyName = 'Arento AI GmbH';
    $browserTitle = 'NIS2 Quick-Check: In 5 Minuten zur Selbsteinschätzung';
    $metaDescription = 'Kostenloser NIS2 Quick-Check für kleine und mittelständische Unternehmen: 10 Fragen, sofortiger Reifegrad und individuelle Handlungsempfehlungen zu Meldepflicht, Risikomanagement, Notfallvorsorge und Lieferkette.';
@endphp

<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    @include('partials.head')

    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $checkUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $productName }}">
    <meta property="og:locale" content="de_DE">
    <meta property="og:url" content="{{ $checkUrl }}">
    <meta property="og:title" content="{{ $browserTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $browserTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 dark:text-zinc-100">
    <header class="border-b border-zinc-100 dark:border-zinc-800">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold">
                <x-app-logo-icon class="size-7 fill-current" />
                <span>{{ $productName }}</span>
            </a>
            <flux:button href="{{ route('guides.index') }}" variant="ghost" size="sm">{{ __('Ratgeber') }}</flux:button>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-12 md:py-16">
        <section class="mx-auto max-w-3xl text-center">
            <flux:badge color="sky" size="sm">{{ __('Kostenlos · ca. 5 Minuten') }}</flux:badge>
            <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('NIS2 Quick-Check') }}</h1>
            <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-300">
                {{ __('Wie gut ist Ihr Unternehmen auf die NIS2-Anforderungen vorbereitet? Beantworten Sie 10 kurze Fragen und erhalten Sie sofort Ihren Reifegrad – mit konkreten Handlungsempfehlungen.') }}
            </p>
        </section>

        <section class="mt-12">
            <livewire:nis2-quick-check />
        </section>

        <section class="mx-auto mt-16 max-w-3xl rounded-2xl border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Von der Selbsteinschätzung zur gelebten Vorsorge') }}</flux:heading>
            <flux:text class="mx-auto mt-2 max-w-xl text-zinc-600 dark:text-zinc-300">
                {{ __('Der Quick-Check zeigt Ihre Lücken. Mit') }} {{ $productName }} {{ __('schließen Sie sie strukturiert: Notfallhandbuch, Rollen, Wiederanlaufpläne, Meldeprozesse und Nachweise – an einem Ort.') }}
            </flux:text>
            <flux:button href="{{ route('home') }}" variant="primary" class="mt-6">{{ __('Mehr über') }} {{ $productName }} {{ __('erfahren') }}</flux:button>
        </section>
    </main>

    <footer class="border-t border-zinc-100 dark:border-zinc-800">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-6 text-sm text-zinc-500 dark:text-zinc-400">
            <span>&copy; {{ now()->year }} {{ $companyName }}</span>
            <nav class="flex gap-4">
                <a href="/impressum" class="hover:underline">{{ __('Impressum') }}</a>
                <a href="/datenschutz" class="hover:underline">{{ __('Datenschutz') }}</a>
            </nav>
        </div>
    </footer>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
