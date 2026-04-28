<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Benutzerhandbuch zu '.$productName)">
    <title>@yield('title', 'Benutzerhandbuch') – {{ $productName }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        .prose h2 { margin-top: 2.5rem; padding-top: 0.5rem; scroll-margin-top: 5rem; }
        .prose h3 { margin-top: 1.75rem; scroll-margin-top: 5rem; }
        .prose ul { list-style: disc; padding-left: 1.25rem; }
        .prose ol { list-style: decimal; padding-left: 1.25rem; }
        .prose li { margin-top: 0.25rem; }
        .prose p { margin-top: 0.85rem; line-height: 1.7; color: rgb(51 65 85); }
        .prose strong { color: rgb(15 23 42); font-weight: 600; }
        .prose code { background: rgb(241 245 249); padding: 0.1em 0.4em; border-radius: 0.25rem; font-size: 0.9em; }
        .prose pre code { background: transparent; padding: 0; }
        .prose pre { background: rgb(15 23 42); color: rgb(241 245 249); padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-top: 1rem; font-size: 0.85em; }
        .prose blockquote { border-left: 4px solid rgb(99 102 241); padding-left: 1rem; color: rgb(71 85 105); margin: 1rem 0; font-style: italic; }
        .prose table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.95em; }
        .prose th, .prose td { border: 1px solid rgb(226 232 240); padding: 0.5rem 0.75rem; text-align: left; }
        .prose th { background: rgb(248 250 252); font-weight: 600; }
        .prose a { color: rgb(79 70 229); text-decoration: underline; }
        .prose a:hover { color: rgb(67 56 202); }
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
                <span class="hidden sm:inline text-slate-400 text-sm">· Benutzerhandbuch</span>
            </a>
            <a href="{{ route('home') }}" class="text-sm text-slate-600 hover:text-slate-900 transition">
                ← {{ __('Zur Startseite') }}
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 lg:py-12">
        <div class="grid lg:grid-cols-[280px_1fr] gap-8 lg:gap-12">

            {{-- ============ SIDEBAR ============ --}}
            <aside class="lg:sticky lg:top-20 lg:self-start lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto">
                <a href="{{ route('manual.index') }}" class="block text-sm font-semibold text-slate-900 hover:text-indigo-600 transition mb-4">
                    Inhaltsverzeichnis
                </a>
                <nav class="space-y-5 text-sm">
                    @foreach ($grouped as $category => $entries)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ $category }}</div>
                            <ul class="space-y-1">
                                @foreach ($entries as $entry)
                                    @php
                                        $isActive = isset($currentSlug) && $currentSlug === $entry['slug'];
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ route('manual.show', $entry['slug']) }}"
                                            @class([
                                                'block px-2 py-1 rounded transition',
                                                'bg-indigo-50 text-indigo-700 font-medium' => $isActive,
                                                'text-slate-700 hover:text-slate-900 hover:bg-slate-50' => ! $isActive,
                                            ])
                                        >
                                            {{ $entry['title'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </aside>

            {{-- ============ INHALT ============ --}}
            <main class="min-w-0">
                @yield('content')
            </main>
        </div>
    </div>

    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-sm text-slate-500">
            <div>&copy; {{ date('Y') }} Arento AI GmbH i. G.</div>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('home') }}" class="hover:text-slate-900 transition">Startseite</a>
                <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a>
                <a href="{{ route('legal.av_contract') }}" class="hover:text-slate-900 transition">AVV</a>
                <a href="{{ route('legal.tom') }}" class="hover:text-slate-900 transition">TOM</a>
                <a href="{{ route('legal.subprocessors') }}" class="hover:text-slate-900 transition">Subprocessors</a>
            </div>
        </div>
    </footer>
</body>
</html>
