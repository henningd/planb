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
            </div>

            <div>
                <div class="text-sm font-semibold text-slate-900">Produkt</div>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li><a href="{{ route('home') }}#features" class="hover:text-slate-900 transition">Funktionen</a></li>
                    <li><a href="{{ route('pricing.show') }}" class="hover:text-slate-900 transition">Preise</a></li>
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
