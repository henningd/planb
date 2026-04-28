@php
    $contactEmail = (string) \App\Support\Settings\SystemSetting::get('platform_contact_email');
    $companyName = 'Arento AI GmbH i. G.';
    $colorMap = [
        'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'ring' => 'ring-indigo-100'],
        'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-600', 'ring' => 'ring-rose-100'],
        'violet' => ['bg' => 'bg-violet-50', 'text' => 'text-violet-600', 'ring' => 'ring-violet-100'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'ring' => 'ring-amber-100'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'ring' => 'ring-emerald-100'],
        'sky' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-600', 'ring' => 'ring-sky-100'],
    ];
    $color = $colorMap[$feature['icon_color']] ?? $colorMap['indigo'];
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $feature['title'] }} – {{ $feature['tagline'] }}">
    <title>{{ $feature['title'] }} – {{ $productName }}</title>
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
            <a href="{{ route('home') }}#compliance" class="text-sm text-slate-600 hover:text-slate-900 transition">
                ← {{ __('Alle Funktionen') }}
            </a>
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-slate-50 via-white to-white"></div>
        <div class="max-w-4xl mx-auto px-6 lg:px-8 pt-16 lg:pt-20 pb-10">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl {{ $color['bg'] }} {{ $color['text'] }} ring-1 {{ $color['ring'] }}">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/>
                </svg>
            </div>
            <h1 class="mt-6 text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900 leading-tight">
                {{ $feature['title'] }}
            </h1>
            <p class="mt-4 text-xl text-slate-600 leading-relaxed">{{ $feature['tagline'] }}</p>
            <p class="mt-6 text-lg text-slate-700 leading-relaxed">{{ $feature['lead'] }}</p>
        </div>
    </section>

    {{-- ============ INHALTE / SECTIONS ============ --}}
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 grid gap-8 lg:gap-10">
            @foreach ($feature['sections'] as $section)
                <div class="border-l-4 {{ str_replace('text-', 'border-', $color['text']) }} pl-5">
                    <h2 class="text-xl font-semibold text-slate-900">{{ $section['heading'] }}</h2>
                    <p class="mt-2 text-slate-700 leading-relaxed">{{ $section['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ SCREENSHOTS ============ --}}
    @if (! empty($feature['screenshots']))
        <section class="py-12 lg:py-16 bg-slate-50 border-y border-slate-100">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <div class="max-w-2xl">
                    <span class="text-sm font-semibold uppercase tracking-wide {{ $color['text'] }}">Aus dem Demo-Mandanten</span>
                    <h2 class="mt-3 text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                        Echte Daten, echte Ansicht.
                    </h2>
                    @if (! empty($feature['demo_hint']))
                        <p class="mt-3 text-slate-600 leading-relaxed">{{ $feature['demo_hint'] }}</p>
                    @endif
                </div>

                <div class="mt-8 grid gap-6 @if(count($feature['screenshots']) > 1) md:grid-cols-2 @endif">
                    @foreach ($feature['screenshots'] as $screenshot)
                        @php
                            $path = '/screenshots/'.$screenshot['file'];
                            $exists = file_exists(public_path('screenshots/'.$screenshot['file']));
                        @endphp
                        <figure class="rounded-xl overflow-hidden ring-1 ring-slate-200 bg-white shadow-sm">
                            @if ($exists)
                                <img src="{{ $path }}" alt="{{ $screenshot['caption'] }}" class="block w-full h-auto" loading="lazy">
                            @else
                                <div class="aspect-video bg-gradient-to-br from-slate-100 to-slate-200 flex flex-col items-center justify-center text-slate-400 p-8 text-center">
                                    <svg class="w-12 h-12 mb-3 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="9" cy="9" r="2"/>
                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                    </svg>
                                    <div class="text-sm font-medium">{{ __('Screenshot folgt') }}</div>
                                    <div class="text-xs mt-1 opacity-70 font-mono">public/screenshots/{{ $screenshot['file'] }}</div>
                                </div>
                            @endif
                            <figcaption class="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                                {{ $screenshot['caption'] }}
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ CTA ============ --}}
    <section class="py-16 lg:py-20">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 px-8 py-10 lg:px-12 lg:py-14 text-white">
                <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                    @auth
                        Direkt im Dashboard ansehen.
                    @else
                        Im Demo-Account selbst ausprobieren.
                    @endauth
                </h2>
                <p class="mt-3 text-slate-300 leading-relaxed">
                    @auth
                        Sie sind angemeldet — der Bereich ist nur einen Klick entfernt.
                    @else
                        Konto in Minuten anlegen, mit dem Onboarding-Wizard das Notfallhandbuch starten und alle Funktionen mit eigenen Daten ausprobieren.
                    @endauth
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route($feature['cta_route'], ['current_team' => auth()->user()->currentTeam->slug]) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition">
                            {{ $feature['cta_label'] }}
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </a>
                    @else
                        @if ($canRegister)
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition">
                                Kostenlos starten
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                                Anmelden
                            </a>
                        @endif
                        @if ($contactEmail !== '')
                            <a href="mailto:{{ $contactEmail }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                                {{ $contactEmail }}
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-sm text-slate-500">
            <div>&copy; {{ date('Y') }} {{ $companyName }}.</div>
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}#compliance" class="hover:text-slate-900 transition">Alle Funktionen</a>
                <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a>
            </div>
        </div>
    </footer>
</body>
</html>
