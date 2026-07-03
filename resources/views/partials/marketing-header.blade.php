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
    $navPortalUrl = rtrim((string) config('services.portal.url'), '/');
    $navFeatures = \App\Support\Marketing\FeatureCatalog::all();
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

        <nav class="hidden lg:flex items-center gap-5 xl:gap-7 text-sm text-slate-600">
            {{-- Funktionen mit Dropdown zu den Funktionsseiten (öffnet per Hover und Tastatur-Fokus) --}}
            <div class="relative group">
                <a href="{{ route('home') }}#features" class="inline-flex items-center gap-1 hover:text-slate-900 transition">
                    Funktionen
                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform group-hover:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </a>
                <div class="invisible opacity-0 translate-y-1 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 group-focus-within:visible group-focus-within:opacity-100 group-focus-within:translate-y-0 transition duration-150 absolute left-0 top-full z-50 pt-3 w-80">
                    <div class="rounded-xl bg-white ring-1 ring-slate-200 shadow-lg p-2">
                        @foreach ($navFeatures as $navFeature)
                            <a href="{{ route('feature.show', $navFeature['slug']) }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                                {{ $navFeature['title'] }}
                            </a>
                        @endforeach
                        <a href="{{ route('home') }}#features" class="mt-1 block rounded-lg px-3 py-2 text-sm font-medium text-indigo-600 hover:bg-slate-50 transition">
                            Alle Funktionen ansehen →
                        </a>
                    </div>
                </div>
            </div>

            <a href="{{ route('home') }}#compliance" class="hover:text-slate-900 transition">Compliance</a>
            <a href="{{ route('pricing.show') }}" @class(['text-slate-900 font-medium' => request()->routeIs('pricing.*'), 'hover:text-slate-900 transition' => ! request()->routeIs('pricing.*')])>Preise</a>
            <a href="{{ route('guides.index') }}" @class(['text-slate-900 font-medium' => request()->routeIs('guides.*'), 'hover:text-slate-900 transition' => ! request()->routeIs('guides.*')])>Ratgeber</a>
            <a href="{{ $navPortalUrl }}" class="hover:text-slate-900 transition">Anbieter-Portal</a>
            <a href="{{ route('home') }}#faq" class="hover:text-slate-900 transition">FAQ</a>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
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

            {{-- Hamburger für < lg --}}
            <button
                type="button"
                id="mobile-nav-toggle"
                class="lg:hidden inline-flex items-center justify-center w-10 h-10 -mr-2 rounded-lg text-slate-700 hover:bg-slate-100 transition"
                aria-label="Menü öffnen"
                aria-expanded="false"
                aria-controls="mobile-nav"
            >
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobil-/Tablet-Navigation (< lg), per Hamburger umgeschaltet --}}
    <div id="mobile-nav" class="lg:hidden hidden border-t border-slate-200 bg-white">
        <nav class="max-w-7xl mx-auto px-6 py-4 flex flex-col text-slate-700">
            <a href="{{ route('home') }}#features" class="py-2 font-medium text-slate-900">Funktionen</a>
            <div class="mb-1 flex flex-col border-l border-slate-200 pl-3 ml-1">
                @foreach ($navFeatures as $navFeature)
                    <a href="{{ route('feature.show', $navFeature['slug']) }}" class="py-1.5 text-sm text-slate-600 hover:text-slate-900 transition">{{ $navFeature['title'] }}</a>
                @endforeach
            </div>
            <a href="{{ route('home') }}#compliance" class="py-2 hover:text-slate-900 transition">Compliance</a>
            <a href="{{ route('pricing.show') }}" class="py-2 hover:text-slate-900 transition">Preise</a>
            <a href="{{ route('guides.index') }}" class="py-2 hover:text-slate-900 transition">Ratgeber</a>
            <a href="{{ $navPortalUrl }}" class="py-2 hover:text-slate-900 transition">Anbieter-Portal</a>
            <a href="{{ route('home') }}#faq" class="py-2 hover:text-slate-900 transition">FAQ</a>
            @guest
                <a href="{{ route('login') }}" class="py-2 sm:hidden hover:text-slate-900 transition">Anmelden</a>
            @endguest
        </nav>
    </div>
</header>

<script>
    (function () {
        var toggle = document.getElementById('mobile-nav-toggle');
        var panel = document.getElementById('mobile-nav');
        if (!toggle || !panel) {
            return;
        }
        toggle.addEventListener('click', function () {
            var isOpen = panel.classList.toggle('hidden') === false;
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? 'Menü schließen' : 'Menü öffnen');
        });
    })();
</script>
