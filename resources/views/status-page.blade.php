<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }} – {{ $productName }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        .prose h2 { margin-top: 2rem; font-size: 1.4rem; font-weight: 600; color: rgb(15 23 42); }
        .prose h3 { margin-top: 1.5rem; font-size: 1.1rem; font-weight: 600; color: rgb(15 23 42); }
        .prose p { margin-top: 0.75rem; line-height: 1.7; color: rgb(51 65 85); }
        .prose ul { list-style: disc; padding-left: 1.25rem; margin-top: 0.5rem; }
        .prose li { margin-top: 0.2rem; }
        .prose strong { color: rgb(15 23 42); font-weight: 600; }
        .prose em { color: rgb(71 85 105); font-style: italic; }
        .prose table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9em; }
        .prose th, .prose td { border: 1px solid rgb(226 232 240); padding: 0.5rem 0.75rem; text-align: left; vertical-align: top; }
        .prose th { background: rgb(248 250 252); font-weight: 600; }
        .prose code { background: rgb(241 245 249); padding: 0.1em 0.4em; border-radius: 0.25rem; font-size: 0.9em; }
        .prose a { color: rgb(79 70 229); text-decoration: underline; }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

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

    <main class="max-w-3xl mx-auto px-6 lg:px-8 py-12 lg:py-16">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $heading }}</h1>

        <div class="mt-6 rounded-2xl border-2 px-6 py-5 sm:px-8 sm:py-6 shadow-sm {{ $bannerClasses }}" data-status-state="{{ $state }}">
            <div class="flex items-center gap-4">
                <span class="relative flex h-4 w-4">
                    <span class="absolute inline-flex h-full w-full rounded-full opacity-60 animate-ping {{ $dotClasses }}"></span>
                    <span class="relative inline-flex h-4 w-4 rounded-full {{ $dotClasses }}"></span>
                </span>
                <span class="text-xl sm:text-2xl font-semibold tracking-tight">{{ $stateLabel }}</span>
            </div>
            <p class="mt-2 text-sm opacity-80">
                {{ __('Stand:') }} {{ now()->format('d.m.Y H:i') }} {{ __('Uhr') }}
            </p>
        </div>

        <h2 class="mt-12 text-xl font-semibold tracking-tight text-slate-900">{{ __('Incident-Historie') }}</h2>

        @if (trim($content) !== '')
            <div class="mt-4 prose max-w-none">
                {!! $html !!}
            </div>
        @else
            <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-6 text-amber-900">
                <div class="font-semibold">{{ __('Inhalt steht aus.') }}</div>
                <p class="mt-2 text-sm leading-relaxed">{{ $emptyHint }}</p>
                <p class="mt-3 text-xs text-amber-800/80">
                    {{ __('Plattform-Betreiber: Text in den Plattform-Einstellungen unter dem Schlüssel') }} <code>{{ $settingKey }}</code> {{ __('hinterlegen.') }}
                </p>
            </div>
        @endif
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-sm text-slate-500">
            <div>&copy; {{ date('Y') }} {{ $productName }}.</div>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a>
                <a href="{{ route('legal.av_contract') }}" class="hover:text-slate-900 transition">AVV</a>
                <a href="{{ route('legal.tom') }}" class="hover:text-slate-900 transition">TOM</a>
                <a href="{{ route('legal.subprocessors') }}" class="hover:text-slate-900 transition">Subprocessors</a>
                <a href="{{ route('legal.accessibility') }}" class="hover:text-slate-900 transition">Barrierefreiheit</a>
            </div>
        </div>
    </footer>
</body>
</html>
