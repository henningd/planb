@php
    $productName = \App\Support\Settings\SystemSetting::get('platform_name') ?: config('app.name', 'Notfallplan');
    $contactEmail = (string) \App\Support\Settings\SystemSetting::get('platform_contact_email');
    $contactPhone = (string) \App\Support\Settings\SystemSetting::get('platform_contact_phone');
    $companyName = 'Arento AI GmbH';
    $portalUrl = rtrim((string) config('services.portal.url'), '/');

    $problems = [
        [
            'title' => 'Kein klarer Plan im Notfall',
            'text'  => 'Wenn Systeme ausfallen, fehlt oft eine klare Reihenfolge. Entscheidungen werden unter Druck getroffen – und kosten wertvolle Zeit.',
        ],
        [
            'title' => 'Ansprechpartner nicht griffbereit',
            'text'  => 'IT-Dienstleister, Versicherung, Datenschutz: Die wichtigsten Kontakte stehen häufig verteilt in E-Mails, Notizen oder im Kopf einzelner Personen.',
        ],
        [
            'title' => 'Unklare Reihenfolge bei Ausfällen',
            'text'  => 'Was muss zuerst wiederhergestellt werden? Welche Prozesse sind kritisch? Ohne dokumentierte Priorisierung entstehen Folgefehler.',
        ],
        [
            'title' => 'Kommunikation bricht weg',
            'text'  => 'Ohne Outlook, Telefonanlage oder interne Chats fehlt vielen Teams der Weg, Mitarbeiter, Kunden und Partner strukturiert zu informieren.',
        ],
        [
            'title' => 'Handbücher sind veraltet',
            'text'  => 'Einmal erstellte PDF-Pläne werden selten gepflegt. Im Ernstfall zeigen sich Lücken, alte Kontakte und längst abgeschaltete Systeme.',
        ],
        [
            'title' => 'Word & Excel reichen nicht',
            'text'  => 'Dokumente auf einem Server, der selbst ausgefallen ist, helfen nicht weiter. Es braucht eine Lösung, die auch dann funktioniert, wenn sonst nichts geht.',
        ],
    ];

    $solutions = [
        'Strukturiertes Erstellen eines Notfallhandbuchs – geführt und verständlich',
        'Klare Rollen, Verantwortlichkeiten und Eskalationsketten',
        'Wiederanlaufplan für die wirklich geschäftskritischen Systeme',
        'Zentrale Kontakt- und Dienstleisterlisten',
        'Notfall-Checklisten für konkrete Szenarien wie Cyberangriff oder Stromausfall',
        'PDF-Export des gesamten Notfallhandbuchs für Offline-Nutzung',
        'Verständlich auch ohne IT-Fachwissen – geschrieben für Entscheider',
    ];

    $features = [
        [
            'title' => 'Geführtes Onboarding',
            'text'  => 'Ein strukturierter Einstieg führt Schritt für Schritt durch alle relevanten Bereiche. Ohne Vorwissen, ohne leere Vorlagen.',
            'icon'  => 'compass',
        ],
        [
            'title' => 'Ansprechpartner & Eskalationsketten',
            'text'  => 'Definieren Sie Entscheider, Stellvertreter und externe Partner. Jeder weiß im Ernstfall, wer kontaktiert wird – und in welcher Reihenfolge.',
            'icon'  => 'users',
        ],
        [
            'title' => 'Betriebskontinuität & Wiederanlauf',
            'text'  => 'Priorisieren Sie Systeme und Prozesse nach ihrer Bedeutung. Klare Wiederanlaufpläne zeigen, was zuerst zurück ans Netz muss.',
            'icon'  => 'refresh',
        ],
        [
            'title' => 'Ausfallrechner',
            'text'  => 'Berechnen Sie auf Knopfdruck den geschätzten Schaden, wenn ein oder mehrere Systeme für eine bestimmte Dauer ausfallen — als Grundlage für Investitions-Entscheidungen und Versicherungs-Gespräche.',
            'icon'  => 'calculator',
        ],
        [
            'title' => 'System- & Zugangsübersicht',
            'text'  => 'Alle relevanten Systeme, Zugänge und Abhängigkeiten an einem Ort – strukturiert, nachvollziehbar und jederzeit aktuell.',
            'icon'  => 'server',
        ],
        [
            'title' => 'Notfall-Checklisten',
            'text'  => 'Fertige Checklisten für typische Szenarien: Cyberangriff, Datenpanne, Serverausfall. Abarbeiten, protokollieren, nachvollziehen.',
            'icon'  => 'clipboard',
        ],
        [
            'title' => 'PDF-Export',
            'text'  => 'Das vollständige Notfallhandbuch als PDF – ausdruckbar, offline nutzbar und unabhängig von der eigenen IT verfügbar.',
            'icon'  => 'document',
        ],
        [
            'title' => 'White-Label & Versicherung',
            'text'  => 'Optionale Varianten für Cyberversicherungen, IT-Dienstleister und Berater – inklusive eigenem Branding und Mandantenstruktur.',
            'icon'  => 'shield',
        ],
        [
            'title' => 'Branchenspezifische Vorlagen',
            'text'  => 'Praxisnahe Vorlagen für Kanzleien, Handel, Produktion und Dienstleister – als solide Grundlage, individuell anpassbar.',
            'icon'  => 'template',
        ],
    ];

    $audiences = [
        [
            'title' => 'Kleine Unternehmen',
            'text'  => 'Pragmatische Notfallplanung, ohne eigene IT-Abteilung aufzubauen oder komplexe Frameworks einzuführen.',
        ],
        [
            'title' => 'Mittelständische Betriebe',
            'text'  => 'Strukturierte Dokumentation für wachsende Organisationen – nachvollziehbar für Geschäftsführung, Fachbereiche und Prüfer.',
        ],
        [
            'title' => 'Betriebe ohne eigene IT',
            'text'  => 'Verständliche Sprache, klare Führung und praxistaugliche Vorlagen – auch wenn IT von externen Dienstleistern betreut wird.',
        ],
        [
            'title' => 'Kanzleien & Dienstleister',
            'text'  => 'Mandantenschutz und Verfügbarkeit sind zentrale Versprechen. Eine belastbare Notfallplanung sichert genau das ab.',
        ],
        [
            'title' => 'Handel & Produktion',
            'text'  => 'Wenn Kassensysteme, Warenwirtschaft oder Maschinen stillstehen, zählt jede Minute. Klare Abläufe sichern den Geschäftsbetrieb.',
        ],
        [
            'title' => 'Versicherungen & IT-Partner',
            'text'  => 'White-Label-Angebote für Cyberversicherungen, IT-Dienstleister und Berater, die ihren Kunden einen echten Mehrwert bieten wollen.',
        ],
    ];

    $steps = [
        [
            'number' => '01',
            'title'  => 'Unternehmen anlegen',
            'text'  => 'Basisdaten, Standorte und Organisationsstruktur in wenigen Minuten erfassen. Kein Projektaufwand, kein Kickoff-Workshop nötig.',
        ],
        [
            'number' => '02',
            'title'  => 'Kontakte & Systeme erfassen',
            'text'  => 'Die wichtigsten Ansprechpartner, Dienstleister und IT-Systeme strukturiert hinterlegen – geführt durch verständliche Masken.',
        ],
        [
            'number' => '03',
            'title'  => 'Prioritäten & Maßnahmen festlegen',
            'text'  => 'Festlegen, welche Prozesse geschäftskritisch sind, wer entscheidet und welche Maßnahmen im Ernstfall wie ablaufen.',
        ],
        [
            'number' => '04',
            'title'  => 'Handbuch & Checklisten nutzen',
            'text'  => 'Notfallhandbuch als PDF exportieren, Checklisten im Ernstfall abarbeiten und alle Schritte nachvollziehbar dokumentieren.',
        ],
    ];

    $benefits = [
        [
            'title' => 'Schneller startklar',
            'text'  => 'In wenigen Stunden statt wochenlanger Projekte zur ersten belastbaren Version des Notfallhandbuchs.',
        ],
        [
            'title' => 'Kein Expertenwissen nötig',
            'text'  => 'Verständliche Sprache und geführte Abläufe – auch ohne IT- oder Compliance-Hintergrund sicher nutzbar.',
        ],
        [
            'title' => 'Klare Struktur im Ernstfall',
            'text'  => 'Wer entscheidet, wer informiert, was zuerst wiederhergestellt wird – alles vorbereitet und dokumentiert.',
        ],
        [
            'title' => 'Besser vorbereitet',
            'text'  => 'Gegenüber Kunden, Partnern, Versicherungen und Prüfern nachvollziehbar zeigen, wie mit Notfällen umgegangen wird.',
        ],
        [
            'title' => 'Weniger Chaos',
            'text'  => 'Reduziert Reibungsverluste, Panikreaktionen und Zeitverlust in den ersten kritischen Stunden nach einem Vorfall.',
        ],
        [
            'title' => 'Dokumentiert & nachvollziehbar',
            'text'  => 'Alle Entscheidungen, Rollen und Maßnahmen sind sauber dokumentiert – intern wie extern belastbar.',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($metaDescription = $productName.' – digitales Notfallhandbuch und Krisenmanagement für kleine und mittelständische Unternehmen. Strukturiert vorbereitet auf Cyberangriff, IT-Ausfall und Krise – inkl. NIS2- und BSI-200-4-Unterstützung.')
    <meta name="description" content="{{ $metaDescription }}">

    <title>{{ $productName }} – Digitales Notfallhandbuch &amp; Krisenmanagement für Unternehmen</title>

    <link rel="canonical" href="{{ route('home') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $productName }}">
    <meta property="og:locale" content="de_DE">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:title" content="{{ $productName }} – Digitales Notfallhandbuch &amp; Krisenmanagement für Unternehmen">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="author" content="{{ $productName }} – Arento AI GmbH">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $productName }} – Digitales Notfallhandbuch &amp; Krisenmanagement für Unternehmen">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ url('/og-image.png') }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => route('home').'#organization',
                    'name' => $productName,
                    'url' => route('home'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => route('home').'#website',
                    'name' => $productName,
                    'url' => route('home'),
                    'inLanguage' => 'de',
                    'publisher' => ['@id' => route('home').'#organization'],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    'name' => $productName,
                    'url' => route('home'),
                    'description' => $metaDescription,
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'inLanguage' => 'de',
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => route('pricing.show'),
                        'priceCurrency' => 'EUR',
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/favicon.svg">

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

        <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-16 lg:pt-24 pb-20 lg:pb-28">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                {{-- Left: Copy --}}
                <div>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium ring-1 ring-indigo-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        Digitales Notfallhandbuch für Unternehmen
                    </span>

                    <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-slate-900 leading-[1.1]">
                        Im Ernstfall wissen, <span class="text-indigo-600">was zu tun ist.</span>
                    </h1>

                    <p class="mt-6 text-lg text-slate-600 leading-relaxed max-w-xl">
                        {{ $productName }} führt Ihr Unternehmen strukturiert durch die Erstellung eines Notfallhandbuchs – mit klaren Rollen, Wiederanlaufplänen und Checklisten als Fundament für professionelles Krisenmanagement. Damit bei Cyberangriff, Serverausfall oder Krise jeder weiß, wer entscheidet und was zuerst passiert.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                                Zum Dashboard
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </a>
                        @else
                            @if ($canRegister)
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                                    Kostenlos starten
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </a>
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium ring-1 ring-slate-200 hover:ring-slate-300 hover:bg-slate-50 transition">
                                    Anmelden
                                </a>
                            @else
                                <a href="#features" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                                    Funktionen ansehen
                                </a>
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium ring-1 ring-slate-200 hover:ring-slate-300 hover:bg-slate-50 transition">
                                    Anmelden
                                </a>
                            @endif
                        @endauth
                    </div>

                    <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Ohne IT-Fachwissen nutzbar
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            In wenigen Stunden startklar
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            PDF-Export offline verfügbar
                        </div>
                    </div>
                </div>

                {{-- Right: Mockup --}}
                <div class="relative">
                    <div class="absolute -inset-4 bg-gradient-to-tr from-indigo-200/40 via-blue-100/30 to-transparent rounded-3xl blur-2xl -z-10"></div>
                    <div class="relative rounded-2xl bg-white ring-1 ring-slate-200 shadow-xl shadow-slate-900/10 overflow-hidden">

                        {{-- Mockup top bar --}}
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                            <span class="ml-3 text-xs text-slate-500 font-medium">{{ $productName }} / Notfallhandbuch</span>
                        </div>

                        <div class="p-6 space-y-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-slate-500 font-medium">Notfallhandbuch</div>
                                    <div class="text-lg font-semibold text-slate-900 mt-0.5">Musterfirma GmbH</div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium ring-1 ring-emerald-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktuell
                                </span>
                            </div>

                            {{-- Status row --}}
                            <div class="grid grid-cols-3 gap-3">
                                <div class="p-3 rounded-lg bg-slate-50 ring-1 ring-slate-100">
                                    <div class="text-xs text-slate-500">Rollen</div>
                                    <div class="text-xl font-semibold text-slate-900 mt-1">12</div>
                                </div>
                                <div class="p-3 rounded-lg bg-slate-50 ring-1 ring-slate-100">
                                    <div class="text-xs text-slate-500">Systeme</div>
                                    <div class="text-xl font-semibold text-slate-900 mt-1">28</div>
                                </div>
                                <div class="p-3 rounded-lg bg-slate-50 ring-1 ring-slate-100">
                                    <div class="text-xs text-slate-500">Szenarien</div>
                                    <div class="text-xl font-semibold text-slate-900 mt-1">9</div>
                                </div>
                            </div>

                            {{-- Checklist --}}
                            <div>
                                <div class="text-sm font-medium text-slate-900 mb-3">Checkliste · Ransomware-Vorfall</div>
                                <ul class="space-y-2.5">
                                    @foreach ([
                                        ['Betroffene Systeme isolieren', true],
                                        ['Geschäftsführung informieren', true],
                                        ['IT-Dienstleister kontaktieren', true],
                                        ['Cyberversicherung benachrichtigen', false],
                                        ['Kommunikation an Mitarbeiter', false],
                                    ] as [$label, $done])
                                        <li class="flex items-center gap-3 text-sm">
                                            <span class="flex items-center justify-center w-5 h-5 rounded-md {{ $done ? 'bg-emerald-500' : 'bg-white ring-1 ring-slate-200' }}">
                                                @if ($done)
                                                    <svg class="w-3 h-3 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                @endif
                                            </span>
                                            <span class="{{ $done ? 'text-slate-500 line-through' : 'text-slate-900' }}">{{ $label }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- Priority row --}}
                            <div class="pt-4 border-t border-slate-100">
                                <div class="text-sm font-medium text-slate-900 mb-3">Wiederanlauf · Priorität</div>
                                <div class="space-y-2">
                                    @foreach ([
                                        ['ERP / Warenwirtschaft', 'Kritisch', 'bg-rose-500', 'w-11/12'],
                                        ['E-Mail & Kommunikation', 'Hoch', 'bg-amber-500', 'w-8/12'],
                                        ['Dateiserver', 'Mittel', 'bg-indigo-500', 'w-5/12'],
                                    ] as [$label, $prio, $color, $width])
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between text-xs mb-1">
                                                    <span class="text-slate-700 font-medium">{{ $label }}</span>
                                                    <span class="text-slate-500">{{ $prio }}</span>
                                                </div>
                                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                                    <div class="h-full {{ $color }} {{ $width }}"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ============ PROBLEM ============ --}}
    <section id="problem" class="py-20 lg:py-28 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Die Ausgangslage</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Wenn wirklich etwas passiert, fehlt oft genau das, was jetzt helfen müsste.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Die meisten Unternehmen sind operativ gut aufgestellt – bis ein IT-Notfall oder eine Krise alles durcheinanderbringt. Dann zeigen sich immer wieder die gleichen Muster:
                </p>
            </div>

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($problems as $problem)
                    <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-50 text-rose-600 shrink-0">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ $problem['title'] }}</h3>
                                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $problem['text'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ AUSFALLRECHNER ============ --}}
    <section id="ausfallrechner" class="py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                {{-- Copy --}}
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Ausfallrechner</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Was kostet Sie ein IT-Ausfall – pro Stunde?
                    </h2>
                    <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                        Serverausfall, Ransomware-Angriff oder eine längere Betriebsunterbrechung summieren sich schneller, als man denkt – aus stillstehender Belegschaft und entgangenem Umsatz. Rechnen Sie Ihre Ausfallkosten in Sekunden durch.
                    </p>
                    <ul class="mt-6 space-y-3 text-slate-600">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>Jede Stunde Stillstand kostet doppelt: Personal <em>und</em> Umsatz.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>{{ $productName }} verkürzt Ausfall- und Wiederanlaufzeit durch klare Pläne, Rollen und Checklisten.</span>
                        </li>
                    </ul>
                    <div class="mt-8">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                                Zum Dashboard
                            </a>
                        @else
                            @if ($canRegister)
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                                    Jetzt vorsorgen – Notfallhandbuch starten
                                </a>
                            @else
                                <a href="#loesung" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
                                    Lösung ansehen
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>

                {{-- Interaktiver Rechner --}}
                <div class="rounded-2xl ring-1 ring-slate-200 shadow-lg p-6 sm:p-8 bg-gradient-to-b from-white to-indigo-50/40">
                    <form class="space-y-6" onsubmit="return false" aria-label="Ausfallkosten berechnen">
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="ar-employees" class="text-sm font-medium text-slate-700">Betroffene Mitarbeitende</label>
                                <span id="ar-employees-val" class="text-sm font-semibold text-indigo-700 tabular-nums">25</span>
                            </div>
                            <input id="ar-employees" type="range" min="1" max="500" step="1" value="25"
                                class="mt-2 w-full accent-indigo-600 cursor-pointer" aria-label="Betroffene Mitarbeitende">
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="ar-laborcost" class="text-sm font-medium text-slate-700">Personalkosten je Mitarbeitende:r / Stunde</label>
                                <span id="ar-laborcost-val" class="text-sm font-semibold text-indigo-700 tabular-nums">45 €</span>
                            </div>
                            <input id="ar-laborcost" type="range" min="20" max="150" step="5" value="45"
                                class="mt-2 w-full accent-indigo-600 cursor-pointer" aria-label="Personalkosten je Mitarbeitende:r und Stunde">
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="ar-revenue" class="text-sm font-medium text-slate-700">Umsatz pro Stunde</label>
                                <span id="ar-revenue-val" class="text-sm font-semibold text-indigo-700 tabular-nums">1.200 €</span>
                            </div>
                            <input id="ar-revenue" type="range" min="0" max="10000" step="100" value="1200"
                                class="mt-2 w-full accent-indigo-600 cursor-pointer" aria-label="Umsatz pro Stunde">
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="ar-hours" class="text-sm font-medium text-slate-700">Ausfalldauer</label>
                                <span id="ar-hours-val" class="text-sm font-semibold text-indigo-700 tabular-nums">8 h</span>
                            </div>
                            <input id="ar-hours" type="range" min="1" max="72" step="1" value="8"
                                class="mt-2 w-full accent-indigo-600 cursor-pointer" aria-label="Ausfalldauer in Stunden">
                        </div>
                    </form>

                    <div class="mt-6 space-y-2 border-t border-slate-200 pt-5 text-sm">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Personalausfall</span>
                            <span id="ar-labor-result" class="font-medium text-slate-900 tabular-nums">9.000 €</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Umsatzausfall</span>
                            <span id="ar-revenue-result" class="font-medium text-slate-900 tabular-nums">9.600 €</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between rounded-lg bg-slate-900 px-4 py-3 text-white">
                            <span class="font-medium">Geschätzte Ausfallkosten</span>
                            <span id="ar-total-result" class="text-xl font-semibold tabular-nums">18.600 €</span>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">
                        Beispielrechnung, keine verbindliche Kalkulation. Indirekte Folgekosten (Reputation, Vertragsstrafen, Datenverlust) sind hier nicht enthalten.
                    </p>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var fmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
                var inputs = ['ar-employees', 'ar-laborcost', 'ar-revenue', 'ar-hours'];

                function val(id) {
                    var n = parseFloat(document.getElementById(id).value);
                    return (isNaN(n) || n < 0) ? 0 : n;
                }

                var dec = new Intl.NumberFormat('de-DE');
                var badge = {
                    'ar-employees': function (v) { return dec.format(v); },
                    'ar-laborcost': function (v) { return fmt.format(v); },
                    'ar-revenue': function (v) { return fmt.format(v); },
                    'ar-hours': function (v) { return dec.format(v) + ' h'; }
                };

                function recalc() {
                    inputs.forEach(function (id) {
                        var b = document.getElementById(id + '-val');
                        if (b) { b.textContent = badge[id](val(id)); }
                    });

                    var laborLoss = val('ar-employees') * val('ar-laborcost') * val('ar-hours');
                    var revenueLoss = val('ar-revenue') * val('ar-hours');
                    document.getElementById('ar-labor-result').textContent = fmt.format(laborLoss);
                    document.getElementById('ar-revenue-result').textContent = fmt.format(revenueLoss);
                    document.getElementById('ar-total-result').textContent = fmt.format(laborLoss + revenueLoss);
                }

                function init() {
                    inputs.forEach(function (id) {
                        var el = document.getElementById(id);
                        if (el) { el.addEventListener('input', recalc); }
                    });
                    recalc();
                }

                if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
            })();
        </script>
    </section>

    {{-- ============ LÖSUNG ============ --}}
    <section id="loesung" class="py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">

                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Die Lösung</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Ein digitales Notfallhandbuch, das auch dann hilft, wenn nichts mehr funktioniert.
                    </h2>
                    <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                        {{ $productName }} führt Sie strukturiert durch alle Themen, die in einem belastbaren Notfallhandbuch stehen sollten – und erstellt daraus klare Handlungspläne, Verantwortlichkeiten und Checklisten. Keine leeren Vorlagen, keine überfrachteten Frameworks.
                    </p>

                    <ul class="mt-8 space-y-4">
                        @foreach ($solutions as $item)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white shrink-0">
                                    <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                                <span class="text-slate-700 leading-relaxed">{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative">
                    <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 p-8 lg:p-10 text-white shadow-xl">
                        <div class="text-xs uppercase tracking-wider text-indigo-200 font-semibold">Im Ernstfall zählt</div>
                        <div class="mt-6 space-y-6">
                            @foreach ([
                                ['Wer entscheidet.',         'Klare Entscheidungsträger und Stellvertreter für jeden Bereich.'],
                                ['Wer wen informiert.',      'Mitarbeiter, Kunden, Partner, Versicherung, Behörden – nach Plan.'],
                                ['Was zuerst läuft.',        'Priorisierter Wiederanlauf der geschäftskritischen Systeme.'],
                                ['Wie Sie handlungsfähig bleiben.', 'Auch wenn Outlook, Telefon oder interne IT ausgefallen sind.'],
                            ] as [$title, $text])
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/10 ring-1 ring-white/10 text-indigo-200 text-sm font-semibold">
                                            {{ $loop->iteration }}
                                        </span>
                                        @unless ($loop->last)
                                            <span class="w-px flex-1 bg-white/10 mt-2"></span>
                                        @endunless
                                    </div>
                                    <div class="pb-2">
                                        <div class="font-semibold text-white">{{ $title }}</div>
                                        <div class="mt-1 text-sm text-indigo-100/80 leading-relaxed">{{ $text }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ============ BAUSTEINE ============ --}}
    <section id="bausteine" class="py-20 lg:py-28 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Business Continuity Management</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Die Bausteine eines belastbaren Notfallhandbuchs
                </h2>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                    Ein Notfallhandbuch ist das Herzstück des betrieblichen Kontinuitätsmanagements (BCM).
                    {{ $productName }} führt Sie durch alle sieben Bausteine – und hält sie mit dem
                    Kreislauf aus Erstellen, Pflegen und Üben dauerhaft aktuell.
                </p>
            </div>

            <div class="mt-12 max-w-3xl mx-auto">
                @include('partials.handbook-diagram')
            </div>

            <p class="mt-8 text-center text-slate-600">
                Was im Detail in jeden Baustein gehört, zeigt unser
                <a href="{{ route('guides.show', 'notfallhandbuch') }}" class="font-medium text-indigo-600 hover:text-indigo-700 transition">Ratgeber „Notfallhandbuch erstellen"</a>.
            </p>
        </div>
    </section>

    {{-- ============ FEATURES ============ --}}
    <section id="features" class="py-20 lg:py-28 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Funktionen</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Alles, was ein belastbares Notfallhandbuch braucht.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Praxisnahe Werkzeuge statt akademischer Frameworks – konzipiert für den operativen Alltag im Mittelstand.
                </p>
            </div>

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($features as $feature)
                    <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-indigo-200 hover:shadow-md transition">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600">
                            @switch($feature['icon'])
                                @case('compass')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                                    @break
                                @case('users')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    @break
                                @case('refresh')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                                    @break
                                @case('calculator')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="11" x2="8.01" y2="11"/><line x1="12" y1="11" x2="12.01" y2="11"/><line x1="16" y1="11" x2="16.01" y2="11"/><line x1="8" y1="15" x2="8.01" y2="15"/><line x1="12" y1="15" x2="12.01" y2="15"/><line x1="16" y1="15" x2="16.01" y2="15"/><line x1="8" y1="19" x2="16" y2="19"/></svg>
                                    @break
                                @case('server')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                                    @break
                                @case('clipboard')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2h6a2 2 0 0 1 2 2v0H7v0a2 2 0 0 1 2-2z"/><rect x="5" y="4" width="14" height="18" rx="2"/><path d="m9 14 2 2 4-4"/></svg>
                                    @break
                                @case('document')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                    @break
                                @case('shield')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    @break
                                @case('template')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                    @break
                            @endswitch
                        </span>
                        <h3 class="mt-5 font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $feature['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ SMS-ALARMIERUNG ============ --}}
    <section id="sms" class="py-20 lg:py-28">
        <style>
            @keyframes smsToastIn {
                0%, 8%   { opacity: 0; transform: translateY(-16px) scale(0.98); }
                16%, 82% { opacity: 1; transform: translateY(0) scale(1); }
                92%,100% { opacity: 0; transform: translateY(-16px) scale(0.98); }
            }
            @keyframes smsBuzz {
                0%,12%,20%,100% { transform: translateX(0); }
                13%,17%         { transform: translateX(-2px); }
                15%,19%         { transform: translateX(2px); }
            }
            .sms-toast { animation: smsToastIn 6s ease-in-out infinite; }
            .sms-phone { animation: smsBuzz 6s ease-in-out infinite; }
            @media (prefers-reduced-motion: reduce) {
                .sms-toast { animation: none; opacity: 1; transform: none; }
                .sms-phone { animation: none; }
            }
        </style>
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                {{-- Copy --}}
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">SMS-Alarmierung</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Alarm per SMS – auch wenn E-Mail, Chat und Internet ausfallen.
                    </h2>
                    <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                        Genau dann, wenn die IT steht, versagen die üblichen Kanäle. {{ $productName }} alarmiert Ihren Krisenstab und Schlüsselpersonen zusätzlich per SMS – über die Anbindung an seven.io. Eine Nachricht, die garantiert ankommt.
                    </p>
                    <ul class="mt-6 space-y-3 text-slate-600">
                        @foreach ([
                            'Erreicht jeden sofort – auf jedem Handy, ohne App, ohne Login.',
                            'Funktioniert, wenn es darauf ankommt: Die SMS kommt auch an, wenn E-Mail, Messenger oder das Firmennetz stehen.',
                            'Krisenstab und Verantwortliche in Sekunden alarmiert – nicht erst, wenn jemand sein Postfach öffnet.',
                            'Direkt aus dem Notfallhandbuch ausgelöst – inklusive Kurzlink zur aktuellen Lage.',
                        ] as $smsBenefit)
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <span>{{ $smsBenefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Animiertes Handy --}}
                <div class="flex justify-center">
                    <div class="sms-phone relative w-[260px] rounded-[2.5rem] bg-slate-900 p-3 shadow-2xl ring-1 ring-slate-800">
                        <div class="absolute left-1/2 top-3 z-10 h-6 w-32 -translate-x-1/2 rounded-b-2xl bg-slate-900"></div>
                        <div class="relative h-[520px] overflow-hidden rounded-[2rem] bg-gradient-to-b from-slate-700 via-slate-800 to-slate-900">
                            <div class="pt-12 text-center text-white">
                                <div class="text-5xl font-light tracking-tight">14:03</div>
                                <div class="mt-1 text-sm text-white/50">Dienstag, 14. Juni</div>
                            </div>
                            <div class="sms-toast absolute inset-x-3 top-36">
                                <div class="rounded-2xl bg-white/95 p-3 shadow-lg ring-1 ring-black/5 backdrop-blur">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-rose-600 text-[11px] font-bold text-white">PB</span>
                                        <div class="flex-1 text-[13px] font-semibold text-slate-900">PlanB Notfall</div>
                                        <div class="text-[11px] text-slate-400">jetzt</div>
                                    </div>
                                    <p class="mt-2 text-[13px] leading-snug text-slate-700">
                                        IT-Notfall gemeldet 14:03 – Ransomware-Verdacht. Krisenstab aktiviert. Lage öffnen: nfh.eu/k/3f9a2c – bitte umgehend Rückmeldung.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ AUSHANG & QR-CODE ============ --}}
    <section id="aushang" class="py-20 lg:py-28 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                {{-- Poster-Mockup mit QR --}}
                <div class="order-2 lg:order-1 flex justify-center">
                    <div class="relative max-w-sm">
                        <div class="-rotate-2 rounded-xl bg-white p-7 shadow-xl ring-1 ring-slate-200">
                            <div class="text-center">
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-600">Notfall-Aushang</div>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">IT-Ausfall? Im Notfall hier scannen.</h3>
                                <p class="mt-1 text-sm text-slate-500">Serverraum · Standort Hauptsitz</p>
                            </div>
                            <div class="mt-5 flex justify-center">
                                <svg viewBox="0 0 120 120" class="h-40 w-40" role="img" aria-label="Beispiel-QR-Code des Notfall-Aushangs">
                                    <rect width="120" height="120" fill="#ffffff"/>
                                    {{-- Finder-Patterns --}}
                                    @foreach ([[4,4],[88,4],[4,88]] as [$fx,$fy])
                                        <rect x="{{ $fx }}" y="{{ $fy }}" width="28" height="28" fill="#0f172a"/>
                                        <rect x="{{ $fx+4 }}" y="{{ $fy+4 }}" width="20" height="20" fill="#ffffff"/>
                                        <rect x="{{ $fx+8 }}" y="{{ $fy+8 }}" width="12" height="12" fill="#0f172a"/>
                                    @endforeach
                                    {{-- Datenmodule (dekorativ) --}}
                                    @foreach ([[48,8],[64,8],[80,8],[40,16],[96,16],[48,24],[72,24],[40,40],[56,40],[88,40],[104,40],[16,48],[48,48],[72,48],[96,48],[32,56],[64,56],[112,56],[48,64],[80,64],[40,72],[56,72],[88,72],[104,72],[48,88],[64,96],[80,88],[96,104],[112,96],[48,104],[72,112],[88,104],[104,112]] as [$mx,$my])
                                        <rect x="{{ $mx }}" y="{{ $my }}" width="8" height="8" fill="#0f172a"/>
                                    @endforeach
                                </svg>
                            </div>
                            <div class="mt-5 rounded-lg bg-slate-50 p-3 text-center">
                                <div class="text-sm font-medium text-slate-900">Zuständigen Dienstleister & erste Schritte anzeigen</div>
                                <div class="mt-1 text-xs text-slate-500">Kein Login · immer aktuell</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Copy --}}
                <div class="order-1 lg:order-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Aushang & QR-Code</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Ein Aushang, ein Scan – im Ernstfall weiß jeder sofort, was zu tun ist.
                    </h2>
                    <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                        {{ $productName }} erzeugt aus Ihrem Notfallhandbuch druckfertige Aushänge mit QR-Code – für Serverraum, Flur oder Schwarzes Brett. Ein Scan führt direkt zur stets aktuellen digitalen Notfall-Info, ganz ohne Login.
                    </p>
                    <ul class="mt-6 space-y-3 text-slate-600">
                        @foreach ([
                            'Physisch dort, wo es zählt – am Systemstandort sichtbar, auch bei totalem IT-Ausfall.',
                            'QR scannen → sofort die richtigen Schritte und der zuständige Dienstleister.',
                            'Immer aktuell: Der QR-Code zeigt auf die gepflegte digitale Version, nicht auf totes Papier.',
                            'Pro System oder Standort – an jedem kritischen Ort einen eigenen Aushang.',
                        ] as $aushangBenefit)
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <span>{{ $aushangBenefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ COMPLIANCE & AUDIT ============ --}}
    <section id="compliance" class="py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Compliance, Audit & Operations</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Vorbereitet für NIS2, BSI 200-4 und den nächsten Audit.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Sie pflegen nicht nur ein Notfallhandbuch — Sie schaffen messbare Resilienz, dokumentierte Risikosteuerung und nachvollziehbare Audit-Spuren. Genau das, was Versicherer, Wirtschaftsprüfer und Aufsichtsbehörden sehen wollen.
                </p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Compliance-Dashboard --}}
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-indigo-200 hover:shadow-md transition">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-5"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-semibold text-slate-900">Compliance-Dashboard</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Reifegrad-Score nach BSI 200-4 und NIS2 — automatisch errechnet aus Ihren tatsächlichen Daten. 30-Tage-Trend, Top-Aktionen mit größtem Hebel und ein klarer Zustand: nicht vorbereitet, ausbaufähig, gut, hervorragend.
                    </p>
                    <a href="{{ route('feature.show', 'compliance-dashboard') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition">
                        Mehr erfahren <span aria-hidden="true">→</span>
                    </a>
                </div>

                {{-- Risiko-Register --}}
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-indigo-200 hover:shadow-md transition">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-rose-50 text-rose-600">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-semibold text-slate-900">Risiko-Register</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        ISO-27001-konformes Register: 5×5-Heatmap, Eintrittswahrscheinlichkeit × Schaden, Restrisiko nach Maßnahmen, Eigentümer und Review-Termine. Maßnahmen lassen sich direkt in die Aufgaben-Inbox überführen.
                    </p>
                    <a href="{{ route('feature.show', 'risiko-register') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-rose-600 hover:text-rose-800 transition">
                        Mehr erfahren <span aria-hidden="true">→</span>
                    </a>
                </div>

                {{-- Lessons Learned --}}
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-indigo-200 hover:shadow-md transition">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-violet-50 text-violet-600">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-semibold text-slate-900">Lessons Learned</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Strukturierte After-Action-Auswertung pro Vorfall und Übung — Ursache, was lief gut, was nicht, plus konkrete Maßnahmen mit Fälligkeit. Verknüpft mit Handbuch-Versionen, damit Erkenntnisse nachweislich einfließen.
                    </p>
                    <a href="{{ route('feature.show', 'lessons-learned') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-violet-600 hover:text-violet-800 transition">
                        Mehr erfahren <span aria-hidden="true">→</span>
                    </a>
                </div>

                {{-- War-Room / Echtzeit --}}
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-indigo-200 hover:shadow-md transition">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-amber-50 text-amber-600">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-semibold text-slate-900">Live-Krisenstab (War-Room)</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Mehrere Personen sehen in Echtzeit, wer welchen Schritt erledigt hat. Anwesenheits-Liste, Live-Updates auf Schritte und Notizen — aus dem Doku-Tool wird ein operativer Krisen-Kommandostand.
                    </p>
                    <a href="{{ route('feature.show', 'war-room') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-amber-600 hover:text-amber-800 transition">
                        Mehr erfahren <span aria-hidden="true">→</span>
                    </a>
                </div>

                {{-- Audit-Log + Mandanten-Export --}}
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-indigo-200 hover:shadow-md transition">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 8v13H3V8M1 3h22v5H1zM10 12h4"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-semibold text-slate-900">Audit-Log & Daten-Export</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Lückenlose Änderungshistorie pro Mandant, exportierbar als CSV oder PDF. Vollständiges Mandanten-Archiv (ZIP) für DSGVO-Auskunft: alle Stammdaten, Audit-Log und revisionssichere Handbuch-PDFs in einem Download.
                    </p>
                    <a href="{{ route('feature.show', 'audit-export') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-800 transition">
                        Mehr erfahren <span aria-hidden="true">→</span>
                    </a>
                </div>

                {{-- Monitoring-Integration --}}
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-indigo-200 hover:shadow-md transition">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-sky-50 text-sky-600">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-semibold text-slate-900">Monitoring-Integration</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Zabbix oder Prometheus Alertmanager melden kritische Vorfälle automatisch — die Plattform öffnet einen Incident, mappt das richtige System und löst die Eskalations-Kette aus. Krisen-Nachrichten via Slack, Microsoft Teams, E-Mail und SMS, jeweils mit Audit-Spur.
                    </p>
                    <a href="{{ route('feature.show', 'monitoring') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-sky-600 hover:text-sky-800 transition">
                        Mehr erfahren <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            <div class="mt-12 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-5">
                    <div class="text-2xl font-semibold text-slate-900">2FA</div>
                    <div class="mt-1 text-sm text-slate-600">Erzwingbar für Admins, plus Lesebestätigungen für jede Handbuch-Version.</div>
                </div>
                <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-5">
                    <div class="text-2xl font-semibold text-slate-900">EU-only</div>
                    <div class="mt-1 text-sm text-slate-600">Hosting und Datenhaltung ausschließlich in Frankfurt. Keine Drittland-Übermittlung im Standard-Setup.</div>
                </div>
                <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-5">
                    <div class="text-2xl font-semibold text-slate-900">SHA-256</div>
                    <div class="mt-1 text-sm text-slate-600">Revisionssichere Handbuch-PDFs mit Hash im Footer und Versionshistorie.</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ NIS2 VERSTÄNDLICH ============ --}}
    <section id="nis2" class="py-20 lg:py-28 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">NIS2 in verständlicher Sprache</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    10 Erwartungen an die Führung — ohne Technik-Kauderwelsch.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Was NIS2 von der Leitungsebene verlangt, in zehn klaren Punkten. Kein Fachjargon — nur das, was Sie als Geschäftsführung wirklich verantworten und entscheiden.
                </p>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2">
                @php($nis2Points = [
                        ['t' => 'Wissen, was geschäftskritisch ist', 'd' => 'Welche Systeme und Daten dürfen nie ausfallen — Kundendaten, ERP, Fachverfahren? Diese zuerst schützen.', 'p' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v6c0 1.7 4 3 9 3s9-1.3 9-3V5M3 11v6c0 1.7 4 3 9 3s9-1.3 9-3v-6"/>'],
                        ['t' => 'Eine klare Verantwortung', 'd' => 'Eine benannte Person — intern oder als Partner — verantwortet die Cybersicherheit und berichtet direkt der Geschäftsführung.', 'p' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>'],
                        ['t' => 'Zugänge im Griff', 'd' => 'Persönliche Konten statt geteilter Logins, starke Passwörter und MFA für E-Mail, VPN und Fernzugriff.', 'p' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'],
                        ['t' => 'Systeme aktuell halten', 'd' => 'Regelmäßige Updates für Server, Geräte und Software — keine offenen Lücken bei kritischen Systemen.', 'p' => '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>'],
                        ['t' => 'Backups, die im Ernstfall tragen', 'd' => 'Automatisch, getrennt gespeichert und mindestens jährlich per Rückspieltest geprüft.', 'p' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>'],
                        ['t' => 'Grundschutz als Standard', 'd' => 'Virenschutz/EDR, Firewall, sicheres WLAN und verschlüsselte Geräte — selbstverständlich, kein Luxus.', 'p' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
                        ['t' => 'Menschen befähigen', 'd' => 'Kurze, regelmäßige Schulungen zu Phishing und sicherem Umgang mit E-Mail, KI und Cloud.', 'p' => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/>'],
                        ['t' => 'Lieferkette mitdenken', 'd' => 'Auch bei ausgelagerter IT bleibt die Verantwortung: Sicherheitsanforderungen vertraglich an Dienstleister weitergeben.', 'p' => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>'],
                        ['t' => 'Vorbereitet auf den Vorfall', 'd' => 'Ein schriftlicher Plan: Wer macht was, wie und wann werden Kunden und Behörden informiert?', 'p' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>'],
                        ['t' => 'Sache der Leitung', 'd' => 'Sicherheit als fester Tagesordnungspunkt mit Kennzahlen und Entscheidungen — nicht „die IT macht das schon".', 'p' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>'],
                ])
                @foreach ($nis2Points as $i => $point)
                    <div class="group relative p-6 rounded-2xl bg-white ring-1 ring-slate-200 hover:ring-indigo-300 hover:shadow-lg hover:-translate-y-0.5 transition">
                        <div class="flex items-start gap-4">
                            <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white text-lg font-semibold shadow-sm">{{ $i + 1 }}</span>
                            <div class="min-w-0">
                                <h3 class="flex items-center gap-2 font-semibold text-slate-900">
                                    <svg class="w-4 h-4 shrink-0 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $point['p'] !!}</svg>
                                    {{ $point['t'] }}
                                </h3>
                                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $point['d'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 flex flex-col gap-4 rounded-2xl bg-slate-900 px-8 py-7 text-white sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-lg font-semibold">Tiefer einsteigen?</div>
                    <div class="mt-1 text-sm text-slate-300">Die vollständige NIS2-Checkliste: wer betroffen ist, welche Pflichten gelten und wie Sie sie umsetzen.</div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    <a href="{{ route('guides.show', 'nis2-checkliste') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 font-medium text-slate-900 transition hover:bg-slate-100">
                        NIS2-Checkliste lesen
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                    @if ($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-500">
                            Kostenlos starten
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ZIELGRUPPEN ============ --}}
    <section id="zielgruppen" class="py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Für wen</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Gemacht für Unternehmen, die im Ernstfall handlungsfähig bleiben wollen.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    {{ $productName }} richtet sich an Organisationen, die keine eigene Stabsstelle für Krisenmanagement haben – aber trotzdem professionell vorbereitet sein wollen.
                </p>
            </div>

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($audiences as $audience)
                    <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-900 text-white">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M9 17h1M14 9h1M14 13h1M14 17h1"/>
                                </svg>
                            </span>
                            <h3 class="font-semibold text-slate-900">{{ $audience['title'] }}</h3>
                        </div>
                        <p class="mt-4 text-sm text-slate-600 leading-relaxed">{{ $audience['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ ANBIETER-PORTAL ============ --}}
    {{-- ============ FÜR BERATER: EIN LOGIN, ALLE MANDANTEN ============ --}}
    <section id="berater" class="py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Für Berater &amp; Partner</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Ein Login. Alle Mandanten.
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Sie betreuen als Berater, Steuerberater oder IT-Dienstleister mehrere Betriebe? In PlanB verwalten Sie
                        alle Mandanten mit <span class="font-medium text-slate-900">einer</span> E-Mail-Adresse und
                        <span class="font-medium text-slate-900">einem</span> Zugang – und wechseln per Klick zwischen ihnen.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">Ein Zugang für alle Mandanten</span> – kein Passwort-Zettel, kein zweites Konto.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">Wechsel per Klick</span> zwischen den Betrieben über den Team-Switcher.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">Strikte Datentrennung:</span> jeder Mandant sieht nur seine eigenen Daten – DSGVO-konform.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">Klare Rollen:</span> Als „Berater" pflegen Sie die Inhalte – Abrechnung und Audit bleiben beim Kunden.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><span class="font-medium text-slate-900">In Sekunden eingeladen:</span> Der Kunde lädt Ihre E-Mail ein, Sie nehmen an – sofort startklar.</span>
                        </li>
                    </ul>
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        @if ($canRegister)
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-5 py-3 text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                                Kostenlos testen
                            </a>
                        @endif
                        <a href="{{ route('manual.show', 'berater-mehrere-teams') }}" class="inline-flex items-center justify-center px-5 py-3 text-sm font-medium rounded-lg ring-1 ring-slate-300 text-slate-700 hover:bg-white hover:text-slate-900 transition">
                            So funktioniert’s
                        </a>
                    </div>
                </div>
                {{-- Diagramm: mehrere Mandanten ↔ ein Berater ↔ Team-Switcher --}}
                <div class="rounded-xl bg-white ring-1 ring-slate-200 shadow-sm p-6 lg:p-8">
                    <svg viewBox="0 0 480 348" class="w-full h-auto" role="img" aria-labelledby="berater-diagram-title">
                        <title id="berater-diagram-title">Ein Berater betreut mehrere Mandanten mit einem Login und wechselt über den Team-Switcher</title>

                        {{-- Mandanten-Karten --}}
                        <rect x="6" y="6" width="148" height="64" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <text x="80" y="34" text-anchor="middle" font-size="13" font-weight="600" fill="#0f172a">Mandant A</text>
                        <text x="80" y="52" text-anchor="middle" font-size="10.5" fill="#64748b">lädt Berater ein</text>

                        <rect x="166" y="6" width="148" height="64" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <text x="240" y="34" text-anchor="middle" font-size="13" font-weight="600" fill="#0f172a">Mandant B</text>
                        <text x="240" y="52" text-anchor="middle" font-size="10.5" fill="#64748b">lädt Berater ein</text>

                        <rect x="326" y="6" width="148" height="64" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <text x="400" y="34" text-anchor="middle" font-size="13" font-weight="600" fill="#0f172a">Mandant C</text>
                        <text x="400" y="52" text-anchor="middle" font-size="10.5" fill="#64748b">lädt Berater ein</text>

                        {{-- Verbindungen zum Berater --}}
                        <line x1="80" y1="70" x2="175" y2="150" stroke="#cbd5e1" stroke-width="1.5"/>
                        <line x1="240" y1="70" x2="240" y2="150" stroke="#cbd5e1" stroke-width="1.5"/>
                        <line x1="400" y1="70" x2="305" y2="150" stroke="#cbd5e1" stroke-width="1.5"/>
                        <circle cx="80" cy="70" r="3.5" fill="#4f46e5"/>
                        <circle cx="240" cy="70" r="3.5" fill="#4f46e5"/>
                        <circle cx="400" cy="70" r="3.5" fill="#4f46e5"/>

                        {{-- Berater (ein Konto) --}}
                        <rect x="100" y="150" width="280" height="96" rx="16" fill="#4f46e5"/>
                        <text x="240" y="182" text-anchor="middle" font-size="17" font-weight="700" fill="#ffffff">Berater</text>
                        <text x="240" y="206" text-anchor="middle" font-size="12" fill="#c7d2fe">eine E-Mail · ein Login</text>
                        <text x="240" y="226" text-anchor="middle" font-size="12" fill="#c7d2fe">Mitglied in allen Mandanten</text>

                        {{-- Verbindung zum Team-Switcher --}}
                        <line x1="240" y1="246" x2="240" y2="280" stroke="#cbd5e1" stroke-width="1.5"/>

                        {{-- Team-Switcher --}}
                        <rect x="96" y="280" width="288" height="44" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <rect x="100" y="284" width="92" height="36" rx="8" fill="#4f46e5"/>
                        <text x="146" y="307" text-anchor="middle" font-size="12.5" font-weight="600" fill="#ffffff">Mandant A</text>
                        <text x="240" y="307" text-anchor="middle" font-size="12.5" font-weight="600" fill="#475569">Mandant B</text>
                        <text x="336" y="307" text-anchor="middle" font-size="12.5" font-weight="600" fill="#475569">Mandant C</text>
                        <text x="240" y="341" text-anchor="middle" font-size="11" fill="#94a3b8">Team-Switcher · Wechsel per Klick</text>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <section id="portal" class="py-20 lg:py-28 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">PlanB Portal</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Wenn Sie Unterstützung brauchen: Experten im Anbieter-Portal finden.
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Ein Notfallhandbuch ist die Grundlage – manchmal braucht es zusätzlich die richtigen Partner.
                        Im <a href="{{ $portalUrl }}" class="font-medium text-indigo-600 hover:text-indigo-700 transition">PlanB Portal</a>,
                        unserem Marktplatz für Notfall- und Business-Continuity-Dienstleistungen, finden Sie geprüfte
                        Versicherungen, Versicherungsmakler, BCM-Berater und IT-Dienstleister – kostenlos und ohne Registrierung durchsuchbar.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>Cyber-Versicherung, Beratung oder technische Wiederherstellung – passende Anbieter nach Fachgebiet filtern.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>Anfragen direkt über das Portal stellen und Angebote vergleichen.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>Sie sind selbst Dienstleister? Präsentieren Sie Ihr Profil dort, wo Unternehmen nach Notfall-Expertise suchen.</span>
                        </li>
                    </ul>
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <a href="{{ $portalUrl }}/anbieter" class="inline-flex items-center justify-center px-5 py-3 text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                            Anbieter-Verzeichnis öffnen
                        </a>
                        <a href="{{ $portalUrl }}/register" class="inline-flex items-center justify-center px-5 py-3 text-sm font-medium rounded-lg ring-1 ring-slate-300 text-slate-700 hover:bg-white hover:text-slate-900 transition">
                            Als Dienstleister registrieren
                        </a>
                    </div>
                </div>
                {{-- Diagramm: Unternehmen ↔ Portal ↔ Anbieter --}}
                <div class="rounded-xl bg-white ring-1 ring-slate-200 shadow-sm p-6 lg:p-8">
                    <svg viewBox="0 0 480 412" class="w-full h-auto" role="img" aria-labelledby="portal-diagram-title">
                        <title id="portal-diagram-title">So verbindet das PlanB Portal Ihr Unternehmen mit Notfall-Experten</title>

                        {{-- Ihr Unternehmen --}}
                        <rect x="120" y="16" width="240" height="76" rx="14" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <text x="240" y="48" text-anchor="middle" font-size="15" font-weight="600" fill="#0f172a">Ihr Unternehmen</text>
                        <text x="240" y="70" text-anchor="middle" font-size="12" fill="#64748b">mit digitalem Notfallhandbuch</text>

                        {{-- Verbindung --}}
                        <line x1="240" y1="98" x2="240" y2="142" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="4 4"/>
                        <path d="M235 103 L240 95 L245 103 Z" fill="#94a3b8"/>
                        <path d="M235 137 L240 145 L245 137 Z" fill="#94a3b8"/>
                        <text x="252" y="116" font-size="11" fill="#64748b">sucht Expertise</text>
                        <text x="228" y="132" text-anchor="end" font-size="11" fill="#64748b">erhält Angebote</text>

                        {{-- Hub: PlanB Portal --}}
                        <rect x="130" y="148" width="220" height="82" rx="16" fill="#4f46e5"/>
                        <text x="240" y="184" text-anchor="middle" font-size="16" font-weight="600" fill="#ffffff">PlanB Portal</text>
                        <text x="240" y="206" text-anchor="middle" font-size="12" fill="#c7d2fe">Marktplatz für Notfall-Expertise</text>

                        {{-- Verbindungen zu den Anbieter-Kategorien --}}
                        <line x1="240" y1="230" x2="90" y2="298" stroke="#cbd5e1" stroke-width="1.5"/>
                        <line x1="240" y1="230" x2="240" y2="298" stroke="#cbd5e1" stroke-width="1.5"/>
                        <line x1="240" y1="230" x2="390" y2="298" stroke="#cbd5e1" stroke-width="1.5"/>

                        {{-- Anbieter-Kategorien --}}
                        <rect x="16" y="300" width="148" height="92" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <text x="90" y="328" text-anchor="middle" font-size="13" font-weight="600" fill="#0f172a">Versicherungen</text>
                        <text x="90" y="344" text-anchor="middle" font-size="13" font-weight="600" fill="#0f172a">&amp; Makler</text>
                        <text x="90" y="366" text-anchor="middle" font-size="11" fill="#64748b">Cyber-Police &amp;</text>
                        <text x="90" y="380" text-anchor="middle" font-size="11" fill="#64748b">Absicherung</text>

                        <rect x="166" y="300" width="148" height="92" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <text x="240" y="336" text-anchor="middle" font-size="13" font-weight="600" fill="#0f172a">BCM-Berater</text>
                        <text x="240" y="366" text-anchor="middle" font-size="11" fill="#64748b">BSI 200-4 &amp;</text>
                        <text x="240" y="380" text-anchor="middle" font-size="11" fill="#64748b">NIS2</text>

                        <rect x="316" y="300" width="148" height="92" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                        <text x="390" y="336" text-anchor="middle" font-size="13" font-weight="600" fill="#0f172a">IT-Dienstleister</text>
                        <text x="390" y="366" text-anchor="middle" font-size="11" fill="#64748b">Backup &amp;</text>
                        <text x="390" y="380" text-anchor="middle" font-size="11" fill="#64748b">Incident Response</text>
                    </svg>
                </div>
            </div>

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Versicherungen &amp; Makler</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">Cyber-Policen, Betriebsunterbrechung und Beratung zur passenden Absicherung.</p>
                </div>
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 shadow-sm">
                    <h3 class="font-semibold text-slate-900">BCM-Berater</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">Unterstützung bei Notfallplanung, BSI 200-4 und NIS2-Anforderungen.</p>
                </div>
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 shadow-sm">
                    <h3 class="font-semibold text-slate-900">IT-Dienstleister</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">Backup, Wiederherstellung und Incident Response im Ernstfall.</p>
                </div>
                <div class="p-6 rounded-xl bg-white ring-1 ring-slate-200 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Für Anbieter</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">Eigenes Profil anlegen und Anfragen von vorbereiteten Unternehmen erhalten.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ SO FUNKTIONIERT ES ============ --}}
    <section id="ablauf" class="py-20 lg:py-28 bg-slate-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 -z-0 bg-[radial-gradient(ellipse_at_top_right,rgba(79,70,229,0.25),transparent_50%)]"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-300">So funktioniert es</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">
                    In vier Schritten zum einsatzbereiten Notfallhandbuch.
                </h2>
                <p class="mt-4 text-lg text-slate-300">
                    Kein Beratungsprojekt, kein Implementierungsaufwand. Sie arbeiten direkt im Werkzeug und sehen sofort Ergebnisse.
                </p>
            </div>

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($steps as $step)
                    <div class="relative p-6 rounded-xl bg-white/5 ring-1 ring-white/10 backdrop-blur-sm">
                        <div class="text-4xl font-semibold text-indigo-300 tabular-nums">{{ $step['number'] }}</div>
                        <h3 class="mt-4 font-semibold text-white">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $step['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ WARUM DIESES PRODUKT ============ --}}
    <section class="py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Warum {{ $productName }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Vorbereitung, die im Ernstfall tatsächlich hilft.
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Ein Notfallhandbuch entfaltet seinen Wert nicht beim Erstellen, sondern im Moment der Krise. Genau darauf ist {{ $productName }} ausgelegt.
                </p>
            </div>

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($benefits as $benefit)
                    <div class="p-6 rounded-xl ring-1 ring-slate-200 bg-gradient-to-b from-white to-slate-50 hover:shadow-md transition">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            <h3 class="font-semibold text-slate-900">{{ $benefit['title'] }}</h3>
                        </div>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $benefit['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ VERTRAUEN / COMPLIANCE ============ --}}
    <section class="py-20 lg:py-28 bg-slate-50 border-y border-slate-100">
        <div class="max-w-5xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-[auto_1fr] gap-8 lg:gap-12 items-start">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm text-indigo-600 shrink-0">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Nachvollziehbar & dokumentiert</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Notfallplanung, die sich zeigen lässt.
                    </h2>
                    <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                        Mit {{ $productName }} bauen Sie Ihre Notfallplanung strukturiert und dokumentiert auf. So können Sie intern, gegenüber Kunden, Versicherungen, Auditoren oder Prüfern nachvollziehbar darstellen, wie Ihr Unternehmen auf Ausfälle und Krisen vorbereitet ist.
                    </p>

                    <div class="mt-8 grid sm:grid-cols-3 gap-4">
                        @foreach ([
                            'Interne Anforderungen'  => 'Klare Grundlage für Geschäftsführung, Leitungsrunden und Fachbereiche.',
                            'Kunden & Partner'       => 'Belastbare Aussagen zur eigenen Vorbereitung auf Ausfälle und Vorfälle.',
                            'Versicherungen & Prüfer' => 'Dokumentierte Strukturen, die sich im Dialog konkret vorlegen lassen.',
                        ] as $title => $text)
                            <div class="p-4 rounded-lg bg-white ring-1 ring-slate-200">
                                <div class="font-medium text-slate-900 text-sm">{{ $title }}</div>
                                <div class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $text }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    @php($faqs = [
        [
            'q' => 'Was ist ein Notfallhandbuch?',
            'a' => 'Ein Notfallhandbuch bündelt alle Informationen, die ein Unternehmen im Ernstfall braucht: Verantwortlichkeiten und Vertretungen, kritische Systeme mit Wiederanlaufplänen, Notfallkontakte, Kommunikationsvorlagen und Checklisten für typische Szenarien wie Cyberangriff oder Serverausfall. Es macht Krisenreaktion planbar, statt sie dem Zufall zu überlassen.',
        ],
        [
            'q' => 'Was gehört in ein gutes Notfallhandbuch?',
            'a' => 'Mindestens: klar benannte Rollen (Krisenstab, IT-Leitung, Kommunikation) mit Stellvertretern, eine priorisierte Übersicht der kritischen Systeme und Prozesse, Wiederanlaufpläne mit Ausweichverfahren, Erreichbarkeiten von Dienstleistern und Behörden sowie vorbereitete Meldewege. '.$productName.' führt Schritt für Schritt durch genau diese Bausteine.',
        ],
        [
            'q' => 'Wie unterstützt '.$productName.' das Krisenmanagement?',
            'a' => 'Neben der Erstellung des Notfallhandbuchs bietet '.$productName.' einen Vorfallmodus für das akute Krisenmanagement: Szenarien mit Schritt-für-Schritt-Checklisten, dokumentierte Entscheidungen im War Room, Kommunikationsvorlagen und eine lückenlose Nachdokumentation inklusive Lessons Learned für die Zeit nach dem Vorfall.',
        ],
        [
            'q' => 'Hilft '.$productName.' bei NIS2 und BSI 200-4?',
            'a' => 'Ja. Das Compliance-Dashboard zeigt den Umsetzungsstand entlang der Anforderungen aus NIS2 und BSI-Standard 200-4, der Audit-Export liefert prüffähige Nachweise, und das Aktivitätsprotokoll dokumentiert nachvollziehbar, wer wann was geändert hat.',
        ],
        [
            'q' => 'Wie schnell ist das Notfallhandbuch einsatzbereit?',
            'a' => 'Die geführte Struktur ist auf kleine und mittelständische Unternehmen ohne eigene Stabsstelle zugeschnitten: Mit vorhandenen Informationen entsteht in wenigen Arbeitssitzungen eine erste belastbare Version, die danach kontinuierlich gepflegt und durch Tests überprüft wird.',
        ],
    ])
    <section id="faq" class="py-20 lg:py-28 bg-slate-50">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                    Häufige Fragen zu Notfallhandbuch &amp; Krisenmanagement
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Die wichtigsten Antworten rund um Notfallplanung, Krisenmanagement und Compliance.
                </p>
            </div>

            <div class="mt-12 space-y-4">
                @foreach ($faqs as $faq)
                    <details class="group rounded-xl bg-white ring-1 ring-slate-200 p-6">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 font-medium text-slate-900 list-none">
                            {{ $faq['q'] }}
                            <svg class="w-5 h-5 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600 leading-relaxed">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>

            <p class="mt-8 text-center text-slate-600">
                Mehr Tiefe gewünscht? In unseren Ratgebern:
                <a href="{{ route('guides.show', 'notfallhandbuch') }}" class="font-medium text-indigo-600 hover:text-indigo-700 transition">Notfallhandbuch erstellen</a>
                und
                <a href="{{ route('guides.show', 'krisenmanagement') }}" class="font-medium text-indigo-600 hover:text-indigo-700 transition">Krisenmanagement im Mittelstand</a>.
            </p>
        </div>
    </section>

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
            ])->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- ============ FINAL CTA ============ --}}
    <section id="kontakt" class="py-20 lg:py-28">
        <div class="max-w-5xl mx-auto px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-blue-700 px-8 py-14 lg:px-14 lg:py-20 text-white shadow-xl">
                <div class="absolute inset-0 -z-0 opacity-30 bg-[radial-gradient(ellipse_at_top_right,rgba(255,255,255,0.4),transparent_50%)]"></div>
                <div class="relative max-w-2xl">
                    <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight">
                        @auth
                            Weiter mit {{ $productName }} im Dashboard.
                        @else
                            @if ($canRegister)
                                In Minuten zum eigenen Notfallhandbuch.
                            @else
                                Lernen Sie {{ $productName }} in einem kurzen Gespräch kennen.
                            @endif
                        @endauth
                    </h2>
                    <p class="mt-4 text-lg text-indigo-100 leading-relaxed">
                        @auth
                            Sie sind angemeldet — alles ist vorbereitet. Eine kostenlose Testumgebung ist startbereit, sobald Sie ein Firmenprofil anlegen.
                        @else
                            @if ($canRegister)
                                Konto anlegen, Firmenprofil ausfüllen, Branche wählen — der geführte Wizard erledigt den Rest. Kein Beratungsprojekt nötig.
                            @else
                                Wir zeigen Ihnen, wie ein strukturiertes Notfallhandbuch für Ihr Unternehmen aussehen kann – praxisnah, verständlich und ohne Verpflichtung.
                            @endif
                        @endauth
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition shadow-sm">
                                Zum Dashboard
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </a>
                        @else
                            @if ($canRegister)
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition shadow-sm">
                                    Kostenlos starten
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </a>
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                                    Anmelden
                                </a>
                            @endif
                            @if ($contactEmail !== '')
                                <a href="mailto:{{ $contactEmail }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    {{ $contactEmail }}
                                </a>
                            @endif
                            @if ($contactPhone !== '')
                                <a href="tel:{{ preg_replace('/[^+0-9]/', '', $contactPhone) }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-medium ring-1 ring-white/20 hover:bg-white/15 transition">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    {{ $contactPhone }}
                                </a>
                            @endif
                            @if ($contactEmail === '' && $contactPhone === '' && ! $canRegister)
                                <div class="rounded-lg bg-white/10 ring-1 ring-white/20 px-5 py-4 text-sm text-indigo-100">
                                    Kontaktdaten werden in den Plattform-Einstellungen hinterlegt (Schlüssel: platform_contact_email, platform_contact_phone).
                                </div>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.marketing-footer')

</body>
</html>
