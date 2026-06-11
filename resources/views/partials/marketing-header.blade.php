{{--
    Gemeinsame obere Navigation aller öffentlichen Marketing-Seiten.
    Selbstständig: benötigt keine Variablen von der einbindenden Seite.
    Anker-Links zeigen absolut auf die Startseite, damit sie auch von
    Unterseiten funktionieren.
--}}
@php
    $navProductName = \App\Support\Settings\SystemSetting::get('platform_name') ?: config('app.name', 'PlanB');
    $navCanRegister = \Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::registration())
        && \App\Support\Settings\SystemSetting::get('registration_enabled', true);
@endphp

<header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 text-white shadow-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </span>
            <span class="font-semibold text-slate-900 tracking-tight">{{ $navProductName }}</span>
        </a>

        <nav class="hidden md:flex items-center gap-8 text-sm text-slate-600">
            <a href="{{ route('home') }}#problem" class="hover:text-slate-900 transition">Problem</a>
            <a href="{{ route('home') }}#loesung" class="hover:text-slate-900 transition">Lösung</a>
            <a href="{{ route('home') }}#features" class="hover:text-slate-900 transition">Funktionen</a>
            <a href="{{ route('home') }}#compliance" class="hover:text-slate-900 transition">Compliance</a>
            <a href="{{ route('pricing.show') }}" @class(['text-slate-900 font-medium' => request()->routeIs('pricing.*'), 'hover:text-slate-900 transition' => ! request()->routeIs('pricing.*')])>Preise</a>
            <a href="{{ route('home') }}#zielgruppen" class="hover:text-slate-900 transition">Zielgruppen</a>
            <a href="{{ route('guides.index') }}" @class(['text-slate-900 font-medium' => request()->routeIs('guides.*'), 'hover:text-slate-900 transition' => ! request()->routeIs('guides.*')])>Ratgeber</a>
            <a href="{{ route('home') }}#faq" class="hover:text-slate-900 transition">FAQ</a>
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                    Zum Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 transition">
                    Anmelden
                </a>
                @if ($navCanRegister)
                    <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                        Kostenlos starten
                    </a>
                @else
                    <a href="{{ route('home') }}#kontakt" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                        Demo anfragen
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>
