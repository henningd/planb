{{--
    Gemeinsamer Footer aller öffentlichen Marketing-Seiten (Startseite,
    Preise, Funktions-Detail, Ratgeber, Rechtstexte). Selbstständig:
    benötigt keine Variablen von der einbindenden Seite. Anker-Links zeigen
    absolut auf die Startseite, damit sie auch von Unterseiten funktionieren.
--}}
@php
    $footerProductName = \App\Support\Settings\SystemSetting::get('platform_name') ?: config('app.name', 'PlanB');
    $footerCompanyName = 'Arento AI GmbH';
    $footerPortalUrl = rtrim((string) config('services.portal.url'), '/');
    $footerLinkedInUrl = 'https://www.linkedin.com/company/arento-ai-gmbh';
    $footerRedditUrl = 'https://www.reddit.com/user/ArentoAI';
@endphp

<footer class="border-t border-slate-200 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-5">
            <div class="md:col-span-2 lg:col-span-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 text-white">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </span>
                    <span class="font-semibold text-slate-900">{{ $footerProductName }}</span>
                </div>
                <p class="mt-4 text-sm text-slate-600 leading-relaxed max-w-md">
                    Das digitale Notfallhandbuch für kleine und mittelständische Unternehmen. Strukturiert vorbereitet auf Cyberangriff, Ausfall und Krise.
                </p>
                <p class="mt-3 text-xs text-slate-500">
                    Ein Produkt der <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">{{ $footerCompanyName }}</a>.
                </p>

                <div class="mt-5">
                    <div class="text-sm font-semibold text-slate-900">Folgen</div>
                    <div class="mt-3 flex items-center gap-3">
                        <a href="{{ $footerLinkedInUrl }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $footerCompanyName }} auf LinkedIn"
                           class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 ring-1 ring-slate-200 hover:text-indigo-600 hover:ring-indigo-300 hover:bg-slate-50 transition">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14zm1.78 13.02H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z"/></svg>
                        </a>
                        <a href="{{ $footerRedditUrl }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $footerCompanyName }} auf Reddit"
                           class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 ring-1 ring-slate-200 hover:text-orange-600 hover:ring-orange-300 hover:bg-slate-50 transition">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 11.78a2.34 2.34 0 0 0-3.96-1.66 11.5 11.5 0 0 0-6.24-1.97l1.06-4.98 3.46.74a1.67 1.67 0 1 0 .18-.86l-3.86-.82a.42.42 0 0 0-.5.32l-1.18 5.56a11.55 11.55 0 0 0-6.32 1.97A2.34 2.34 0 1 0 3.4 14.9c-.04.24-.06.48-.06.72 0 3.64 4.24 6.6 9.47 6.6s9.47-2.96 9.47-6.6c0-.24-.02-.48-.06-.72A2.34 2.34 0 0 0 24 11.78zM6.6 13.44a1.67 1.67 0 1 1 3.34 0 1.67 1.67 0 0 1-3.34 0zm9.34 4.42c-1.14 1.14-3.32 1.23-3.94 1.23s-2.8-.09-3.94-1.23a.43.43 0 0 1 .6-.6c.72.72 2.25.98 3.34.98s2.62-.26 3.34-.98a.43.43 0 0 1 .6.6zm-.28-2.75a1.67 1.67 0 1 1 0-3.34 1.67 1.67 0 0 1 0 3.34z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div>
                <div class="text-sm font-semibold text-slate-900">Produkt</div>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li><a href="{{ route('home') }}#features" class="hover:text-slate-900 transition">Funktionen</a></li>
                    <li><a href="{{ route('pricing.show') }}" class="hover:text-slate-900 transition">Preise</a></li>
                    <li><a href="{{ route('kommunen.show') }}" class="hover:text-slate-900 transition">Für Kommunen</a></li>
                    <li><a href="{{ route('home') }}#zielgruppen" class="hover:text-slate-900 transition">Zielgruppen</a></li>
                    <li><a href="{{ route('home') }}#ablauf" class="hover:text-slate-900 transition">So funktioniert es</a></li>
                    <li><a href="{{ route('home') }}#kontakt" class="hover:text-slate-900 transition">Demo anfragen</a></li>
                </ul>
                <div class="mt-6 text-sm font-semibold text-slate-900">Ratgeber</div>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li><a href="{{ route('guides.show', 'notfallhandbuch') }}" class="hover:text-slate-900 transition">Notfallhandbuch erstellen</a></li>
                    <li><a href="{{ route('guides.show', 'krisenmanagement') }}" class="hover:text-slate-900 transition">Krisenmanagement im Mittelstand</a></li>
                    <li><a href="{{ route('guides.show', 'it-notfallplan') }}" class="hover:text-slate-900 transition">IT-Notfallplan erstellen</a></li>
                    <li><a href="{{ route('guides.show', 'bsi-200-4') }}" class="hover:text-slate-900 transition">BSI 200-4 umsetzen</a></li>
                    <li><a href="{{ route('guides.show', 'nis2-checkliste') }}" class="hover:text-slate-900 transition">NIS2-Checkliste</a></li>
                </ul>
            </div>

            <div>
                <div class="text-sm font-semibold text-slate-900">Unternehmen</div>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li><a href="{{ route('home') }}#kontakt" class="hover:text-slate-900 transition">Kontakt</a></li>
                    <li><a href="{{ route('manual.index') }}" class="hover:text-slate-900 transition">Benutzerhandbuch</a></li>
                    <li><a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a></li>
                    <li><a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a></li>
                    <li><a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a></li>
                </ul>
                <div class="mt-6 text-sm font-semibold text-slate-900">Anbieter-Portal</div>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li><a href="{{ route('home') }}#portal" class="hover:text-slate-900 transition">Was ist das Portal?</a></li>
                    <li><a href="{{ $footerPortalUrl }}/anbieter" class="hover:text-slate-900 transition">Anbieter-Verzeichnis</a></li>
                    <li><a href="{{ $footerPortalUrl }}/register" class="hover:text-slate-900 transition">Als Dienstleister registrieren</a></li>
                </ul>
            </div>

            <div>
                <div class="text-sm font-semibold text-slate-900">Compliance</div>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li><a href="{{ route('legal.av_contract') }}" class="hover:text-slate-900 transition">Auftragsverarbeitung</a></li>
                    <li><a href="{{ route('legal.tom') }}" class="hover:text-slate-900 transition">TOM (Art. 32 DSGVO)</a></li>
                    <li><a href="{{ route('legal.subprocessors') }}" class="hover:text-slate-900 transition">Subprocessors</a></li>
                    <li><a href="{{ route('legal.accessibility') }}" class="hover:text-slate-900 transition">Barrierefreiheit</a></li>
                    <li><a href="{{ url('/.well-known/security.txt') }}" class="hover:text-slate-900 transition">security.txt</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="text-sm text-slate-500">
                &copy; {{ date('Y') }} {{ $footerCompanyName }}. Alle Rechte vorbehalten.
            </div>
            <div class="flex items-center gap-6 text-sm text-slate-500">
                <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a>
            </div>
        </div>
    </div>
</footer>
