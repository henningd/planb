@php
    $contactEmail = (string) \App\Support\Settings\SystemSetting::get('platform_contact_email');
    $companyName = 'Arento AI GmbH';

    $metaDescription = $productName.' für Kommunen – digitales Notfallhandbuch und Krisenmanagement für Städte, Gemeinden und Eigenbetriebe. Fachverfahren, Bürgerdienste (OZG) und E-Akte im Ernstfall absichern – inkl. NIS2-/KRITIS-Orientierung und offline verfügbarer Notfall-App.';

    // Warum PlanB für Kommunen — sachliche Begründungen, ausschließlich mit
    // Fähigkeiten, die das Produkt tatsächlich hat.
    $reasons = [
        [
            'title' => 'Kommunen stehen im Visier – Bürgerdienste müssen trotzdem weiterlaufen.',
            'text'  => 'Verwaltungen sind attraktive Ziele für Ransomware und Cyberangriffe: viele sensible Daten, viele Verfahren, oft knappe IT-Ressourcen. Genau dann braucht es ein Notfallhandbuch, das sofort greifbar ist – mit klaren Zuständigkeiten und den ersten Schritten für die kritischen ersten Stunden.',
        ],
        [
            'title' => 'Wenn die IT steht, stehen Fachverfahren, E-Akte und Bürgerportal.',
            'text'  => 'PlanB dokumentiert die Wiederanlauf-Reihenfolge Ihrer Verfahren (Einwohnerwesen, Kfz-Zulassung, Haushalts-/Kassensystem, DMS/E-Akte, OZG-Dienste) mit Prioritäten und Zuständigkeiten – und hält Checklisten und Kontakte auch dann verfügbar, wenn Server und Büro-IT ausfallen: offline in der Notfall-App und per QR-Notfallaushang im Gebäude.',
        ],
        [
            'title' => 'Kleines IT-Team, viele Pflichten – Struktur ohne Beratungsprojekt.',
            'text'  => 'Die wenigsten Kommunen haben einen eigenen BCM- oder Krisenstab. PlanB bringt die Struktur mit: Krisenstab mit Hauptpersonen und Vertretungen, Alarmierung mit Quittierung („gesehen“ / „übernehme“), fertige Szenario-Checklisten und ein geführtes Onboarding – ohne monatelanges Projekt.',
        ],
        [
            'title' => 'NIS2, KRITIS und Nachweispflichten im Blick.',
            'text'  => 'Kommunale Einrichtungen rücken zunehmend in den Anwendungsbereich von NIS2 und KRITIS. PlanB unterstützt mit Compliance-Orientierung nach BSI 200-4 / NIS2, einem Meldepflichten-Workflow mit Fristen und revisionssicherer Dokumentation: versionierte, freigegebene Handbuch-Stände mit Lesebestätigungen.',
        ],
        [
            'title' => 'Rathaus, Bauhof, Stadtwerke, Schulen – eine Struktur für alle Liegenschaften.',
            'text'  => 'Mehrere Standorte und Eigenbetriebe bilden Sie in PlanB als Standorte ab – mit eigenen Kontakten und Notfallaushängen je Gebäude. So weiß jede Liegenschaft im Ernstfall, was zu tun ist.',
        ],
    ];

    // Passende Bausteine aus dem bestehenden Produkt — nichts erfunden.
    $modules = [
        [
            'title' => 'Verwaltungs-Template',
            'text'  => 'Branchen-Template für Kommunen, Behörden, Schulen und Eigenbetriebe: Fachverfahren, Bürgerportal (OZG), E-Akte, Haushalts-/Kassensystem – als Startpunkt vorbefüllt statt leerer Vorlagen.',
        ],
        [
            'title' => 'Notfall-App – offline verfügbar',
            'text'  => 'Das freigegebene Notfallhandbuch, Kontakte und Checklisten liegen offline auf dem Smartphone des Krisenstabs – nutzbar, auch wenn Server, Netz und Telefonanlage ausfallen.',
        ],
        [
            'title' => 'QR-Notfallaushang',
            'text'  => 'Aushänge mit QR-Code je Standort und Szenario: Mitarbeitende kommen ohne Login direkt zur richtigen Checkliste – am Empfang, im Bauhof, im Serverraum.',
        ],
        [
            'title' => 'Krisenstab & Alarmierung',
            'text'  => 'Pflichtrollen mit Vertretungen besetzen, im Ernstfall per App-Push und SMS alarmieren – mit Quittierung, wer den Alarm gesehen hat und wer übernimmt.',
        ],
        [
            'title' => 'Meldepflichten-Workflow',
            'text'  => 'Meldepflichten strukturiert erfassen und Fristen nachverfolgen – vom Vorfall bis zur abgeschlossenen Meldung, revisionssicher protokolliert.',
        ],
        [
            'title' => 'Versionen & Lesebestätigungen',
            'text'  => 'Jede Handbuch-Änderung dokumentiert, jede Version durch die Leitung freigegeben, Lesebestätigungen der Mitarbeitenden nachvollziehbar – belastbar gegenüber Prüfern.',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription }}">
    <title>{{ $productName }} für Kommunen – Notfallhandbuch &amp; Krisenmanagement für die Verwaltung</title>
    @include('partials.seo-meta', [
        'seoTitle' => $productName.' für Kommunen – Notfallhandbuch & Krisenmanagement für die Verwaltung',
        'seoDescription' => $metaDescription,
        'seoUrl' => route('kommunen.show'),
        'seoBreadcrumbs' => [
            ['name' => $productName, 'item' => route('home')],
            ['name' => 'Für Kommunen', 'item' => route('kommunen.show')],
        ],
    ])
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

    @include('partials.marketing-header')

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-slate-50 via-white to-white"></div>
        <div class="absolute inset-x-0 top-0 -z-10 h-96 bg-[radial-gradient(ellipse_at_top,rgba(79,70,229,0.08),transparent_60%)]"></div>

        <div class="max-w-4xl mx-auto px-6 lg:px-8 pt-16 lg:pt-24 pb-14 lg:pb-20">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium ring-1 ring-indigo-100">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                Für Kommunen, Behörden &amp; Eigenbetriebe
            </span>

            <h1 class="mt-6 text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900 leading-[1.1]">
                Wenn die Verwaltung steht, zählt jede Minute – <span class="text-indigo-600">und jeder klare Plan.</span>
            </h1>

            <p class="mt-6 text-lg text-slate-600 leading-relaxed max-w-2xl">
                {{ $productName }} ist das digitale Notfallhandbuch für Städte, Gemeinden und Eigenbetriebe:
                Fachverfahren, Bürgerdienste (OZG) und E-Akte im Ernstfall absichern, Krisenstab und Alarmierung
                vorbereiten – und alles offline verfügbar halten, wenn die eigene IT ausfällt.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                @if ($canRegister)
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                        Kostenlos starten
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                @endif
                <a href="{{ route('pricing.show') }}#kommunal" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium ring-1 ring-slate-200 hover:ring-slate-300 hover:bg-slate-50 transition">
                    Kommunal-Tarif ansehen
                </a>
                <a href="{{ route('home') }}#kontakt" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium ring-1 ring-slate-200 hover:ring-slate-300 hover:bg-slate-50 transition">
                    Demo anfragen
                </a>
            </div>

            <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-500">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Verwaltungs-Template inklusive
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Offline in der Notfall-App
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    NIS2- / BSI-200-4-Orientierung
                </div>
            </div>
        </div>
    </section>

    {{-- ============ WARUM PLANB FÜR KOMMUNEN ============ --}}
    <section id="warum-kommunen" class="py-16 lg:py-24 bg-slate-50 border-y border-slate-100">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Warum {{ $productName }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Warum gerade Kommunen ein belastbares Notfallhandbuch brauchen.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Nicht, weil es auf einer Checkliste steht – sondern weil Verwaltungen im Ernstfall besondere
                    Pflichten haben und selten eigene Stäbe dafür. Fünf Gründe, sachlich begründet.
                </p>
            </div>

            <div class="mt-12 grid gap-8 lg:gap-10">
                @foreach ($reasons as $reason)
                    <div class="border-l-4 border-indigo-600 pl-5">
                        <h3 class="text-xl font-semibold text-slate-900">{{ $reason['title'] }}</h3>
                        <p class="mt-2 text-slate-700 leading-relaxed">{{ $reason['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ BAUSTEINE ============ --}}
    <section id="bausteine-kommunen" class="py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Die passenden Bausteine</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Alles drin, was eine Verwaltung im Ernstfall braucht.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Keine Sonderlösung, sondern die bewährten {{ $productName }}-Bausteine – auf kommunale Abläufe angewendet.
                </p>
            </div>

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($modules as $module)
                    <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-600 text-white">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M9 17h1M14 9h1M14 13h1M14 17h1"/>
                                </svg>
                            </span>
                            <h3 class="font-semibold text-slate-900">{{ $module['title'] }}</h3>
                        </div>
                        <p class="mt-4 text-sm text-slate-600 leading-relaxed">{{ $module['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ WIEDERANLAUF-BEISPIEL ============ --}}
    <section class="py-16 lg:py-24 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Aus dem Verwaltungs-Template</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Vom leeren Blatt zur Wiederanlauf-Reihenfolge – in Minuten.
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Das Verwaltungs-Template bringt die typischen Systeme einer Kommune fertig klassifiziert mit –
                        von der Stromversorgung über Fachverfahren und E-Akte bis zum Bürgerportal. Sie passen an,
                        statt bei null zu beginnen.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">Prioritäten &amp; Wiederanlaufzeiten (RTO/RPO)</span> je System vorbelegt – anpassbar auf Ihre Verwaltung.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">Zuständigkeiten &amp; Dienstleister</span> zentral hinterlegt – vom IT-Dienstleister bis zum Rechenzentrum.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">Szenario-Checklisten</span> für Cyberangriff, IT-Ausfall oder Stromausfall – im Ernstfall abarbeiten und protokollieren.</span>
                        </li>
                    </ul>
                </div>

                {{-- Mockup: Wiederanlauf-Prioritäten einer Verwaltung --}}
                <div class="rounded-xl bg-white ring-1 ring-slate-200 shadow-sm p-6 lg:p-8">
                    <div class="text-sm font-medium text-slate-900 mb-4">Wiederanlauf · Verwaltung</div>
                    <div class="space-y-3">
                        @foreach ([
                            ['Stromversorgung / USV', 'Kritisch', 'bg-rose-500', 'w-11/12'],
                            ['Fachverfahren (Einwohner, Kfz, Soziales)', 'Kritisch', 'bg-rose-500', 'w-10/12'],
                            ['DMS / E-Akte', 'Kritisch', 'bg-rose-500', 'w-9/12'],
                            ['Haushalts- & Kassensystem', 'Kritisch', 'bg-amber-500', 'w-8/12'],
                            ['Bürgerportal / Online-Dienste (OZG)', 'Hoch', 'bg-amber-500', 'w-6/12'],
                            ['Website / Bekanntmachungen', 'Normal', 'bg-indigo-500', 'w-4/12'],
                        ] as [$label, $prio, $color, $width])
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-slate-700 font-medium">{{ $label }}</span>
                                    <span class="text-slate-500">{{ $prio }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full {{ $color }} {{ $width }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500">
                        Beispielhafte Prioritäten aus dem Verwaltungs-Template – individuell anpassbar.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ NOTFALL-APP ============ --}}
    <section id="notfall-app" class="py-16 lg:py-24 bg-slate-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(99,102,241,0.22),transparent_60%)]"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-300">Die Notfall-App (iOS &amp; Android)</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">
                        Funktioniert, wenn sonst nichts mehr geht.
                    </h2>
                    <p class="mt-4 text-lg text-slate-300 leading-relaxed">
                        Im Kommunal-Tarif inklusive: die {{ $productName }}-App für den Krisenstab und die
                        Schlüsselpersonen jeder Liegenschaft.
                    </p>
                    <ul class="mt-8 space-y-6">
                        <li class="flex gap-4">
                            <span class="inline-flex items-center justify-center w-10 h-10 shrink-0 rounded-lg bg-indigo-500/20 ring-1 ring-indigo-400/30 text-indigo-300">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M2 12h4M18 12h4M12 18v4"/><circle cx="12" cy="12" r="4"/></svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-white">Alle Daten offline auf dem Gerät</h3>
                                <p class="mt-1 text-sm text-slate-300 leading-relaxed">
                                    Notfallhandbuch, Krisenstab-Kontakte, Wiederanlauf-Pläne und Checklisten liegen
                                    lokal auf dem Smartphone. Lesen <span class="font-medium text-white">und Abhaken funktionieren ohne
                                    Internet</span> – fällt das Netz aus, arbeitet die App weiter und überträgt alles
                                    automatisch, sobald wieder Verbindung besteht.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="inline-flex items-center justify-center w-10 h-10 shrink-0 rounded-lg bg-indigo-500/20 ring-1 ring-indigo-400/30 text-indigo-300">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-white">Alarme, die wirklich ankommen</h3>
                                <p class="mt-1 text-sm text-slate-300 leading-relaxed">
                                    Notfall-Benachrichtigungen sind als <span class="font-medium text-white">zeitkritisch</span> markiert:
                                    Sie durchbrechen Fokus-Modi wie „Nicht stören" oder „Schlafen" auf dem iPhone; auf
                                    Android klingelt der Alarm in Alarm-Lautstärke und kann „Nicht stören" umgehen.
                                    Jeder im Krisenstab quittiert mit einem Tipp: <span class="font-medium text-white">„Gesehen"
                                    oder „Ich übernehme"</span> – alle sehen sofort, wer reagiert hat.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="inline-flex items-center justify-center w-10 h-10 shrink-0 rounded-lg bg-indigo-500/20 ring-1 ring-indigo-400/30 text-indigo-300">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4c0-1.1.9-2 2-2h8a2 2 0 0 1 2 2z"/><path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"/></svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-white">Automatische SMS-Eskalation</h3>
                                <p class="mt-1 text-sm text-slate-300 leading-relaxed">
                                    Übernimmt innerhalb der eingestellten Zeit niemand den Notfall, eskaliert
                                    {{ $productName }} von selbst: erneuter Alarm plus <span class="font-medium text-white">SMS an den
                                    gesamten Krisenstab inklusive Vertretungen</span>. SMS erreichen das Handy auch ohne
                                    Internetverbindung und ohne installierte App – die letzte Meile, die immer funktioniert.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="inline-flex items-center justify-center w-10 h-10 shrink-0 rounded-lg bg-indigo-500/20 ring-1 ring-indigo-400/30 text-indigo-300">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-white">Sicherheit &amp; Datenschutz eingebaut</h3>
                                <p class="mt-1 text-sm text-slate-300 leading-relaxed">
                                    Auf Wunsch schützt eine <span class="font-medium text-white">App-Sperre per Face ID bzw.
                                    Fingerabdruck</span> die sensiblen Krisenstab-Kontakte auf dem Gerät. Zugangsdaten
                                    liegen verschlüsselt im Geräte-Schlüsselbund, beim Abmelden werden alle lokalen
                                    Daten gelöscht – und die App enthält <span class="font-medium text-white">kein Tracking
                                    und keine Werbung</span>. DSGVO-konform, gehostet in Deutschland.
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                {{-- Mockup: iOS-App im Alarmfall --}}
                @include('partials.notfall-app-mockup', [
                    'mockupCaption' => 'Beispielansicht der '.$productName.'-App im Alarmfall – offline, mit Quittierungen.',
                ])
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="py-16 lg:py-24">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                Machen Sie Ihre Verwaltung handlungsfähig – bevor es ernst wird.
            </h2>
            <p class="mt-4 text-lg text-slate-600">
                In wenigen Stunden zur ersten belastbaren Version des kommunalen Notfallhandbuchs.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                @if ($canRegister)
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                        Kostenlos starten
                    </a>
                @endif
                <a href="{{ route('home') }}#kontakt" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium ring-1 ring-slate-200 hover:ring-slate-300 hover:bg-slate-50 transition">
                    Demo anfragen
                </a>
            </div>
            @if (filled($contactEmail))
                <p class="mt-6 text-sm text-slate-500">
                    Fragen zu Ausschreibung oder Beschaffung? <a href="mailto:{{ $contactEmail }}" class="text-indigo-600 hover:text-indigo-700 font-medium">{{ $contactEmail }}</a>
                </p>
            @endif
        </div>
    </section>

    @include('partials.marketing-footer')

</body>
</html>
