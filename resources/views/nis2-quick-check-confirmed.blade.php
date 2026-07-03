<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('E-Mail bestätigt') }} | {{ $productName }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

    @include('partials.marketing-header')

    <main class="mx-auto flex max-w-2xl flex-col items-center justify-center px-6 py-24 text-center">
        <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 ring-1 ring-emerald-100">
            <svg class="h-8 w-8 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
        <h1 class="mt-6 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ __('E-Mail-Adresse bestätigt') }}</h1>
        <p class="mt-4 text-lg text-slate-700 leading-relaxed">
            {{ __('Vielen Dank! Ihre ausführliche NIS2-Auswertung ist unterwegs zu Ihnen und sollte in wenigen Minuten in Ihrem Postfach liegen.') }}
        </p>
        <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
            {{ __('Mehr über') }} {{ $productName }} {{ __('erfahren') }}
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
        </a>
    </main>

    @include('partials.marketing-footer')
</body>
</html>
