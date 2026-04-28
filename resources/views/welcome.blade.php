@php
    $productName = \App\Support\Settings\SystemSetting::get('platform_name') ?: config('app.name', 'Notfallplan');
    $contactEmail = (string) \App\Support\Settings\SystemSetting::get('platform_contact_email');
    $contactPhone = (string) \App\Support\Settings\SystemSetting::get('platform_contact_phone');
    $companyName = 'Arento AI GmbH i. G.';

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
    <meta name="description" content="{{ $productName }} – das digitale Notfallhandbuch für kleine und mittelständische Unternehmen. Strukturiert vorbereitet auf Cyberangriff, Ausfall und Krise.">

    <title>{{ $productName }} – Digitales Notfallhandbuch für Unternehmen</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/favicon.svg">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

    {{-- ============ NAVIGATION ============ --}}
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="#" class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 text-white shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </span>
                <span class="font-semibold text-slate-900 tracking-tight">{{ $productName }}</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm text-slate-600">
                <a href="#problem" class="hover:text-slate-900 transition">Problem</a>
                <a href="#loesung" class="hover:text-slate-900 transition">Lösung</a>
                <a href="#features" class="hover:text-slate-900 transition">Funktionen</a>
                <a href="#compliance" class="hover:text-slate-900 transition">Compliance</a>
                <a href="#zielgruppen" class="hover:text-slate-900 transition">Zielgruppen</a>
                <a href="#ablauf" class="hover:text-slate-900 transition">Ablauf</a>
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
                    @if ($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                            Kostenlos starten
                        </a>
                    @else
                        <a href="#kontakt" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                            Demo anfragen
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

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
                        {{ $productName }} führt Ihr Unternehmen strukturiert durch die Erstellung eines Notfallhandbuchs – mit klaren Rollen, Wiederanlaufplänen und Checklisten. Damit bei Cyberangriff, Serverausfall oder Krise jeder weiß, wer entscheidet und was zuerst passiert.
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
                            <span class="ml-3 text-xs text-slate-500 font-medium">{{ strtolower($productName) }}.app / notfallhandbuch</span>
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

    {{-- ============ FOOTER ============ --}}
    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 text-white">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </span>
                        <span class="font-semibold text-slate-900">{{ $productName }}</span>
                    </div>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed max-w-md">
                        Das digitale Notfallhandbuch für kleine und mittelständische Unternehmen. Strukturiert vorbereitet auf Cyberangriff, Ausfall und Krise.
                    </p>
                    <p class="mt-3 text-xs text-slate-500">
                        Ein Produkt der <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">{{ $companyName }}</a>.
                    </p>
                </div>

                <div>
                    <div class="text-sm font-semibold text-slate-900">Produkt</div>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600">
                        <li><a href="#features" class="hover:text-slate-900 transition">Funktionen</a></li>
                        <li><a href="#zielgruppen" class="hover:text-slate-900 transition">Zielgruppen</a></li>
                        <li><a href="#ablauf" class="hover:text-slate-900 transition">So funktioniert es</a></li>
                        <li><a href="#kontakt" class="hover:text-slate-900 transition">Demo anfragen</a></li>
                    </ul>
                </div>

                <div>
                    <div class="text-sm font-semibold text-slate-900">Unternehmen</div>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600">
                        <li><a href="#kontakt" class="hover:text-slate-900 transition">Kontakt</a></li>
                        <li><a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="text-sm text-slate-500">
                    &copy; {{ date('Y') }} {{ $companyName }}. Alle Rechte vorbehalten.
                </div>
                <div class="flex items-center gap-6 text-sm text-slate-500">
                    <a href="{{ route('legal.imprint') }}" class="hover:text-slate-900 transition">Impressum</a>
                    <a href="{{ route('legal.privacy') }}" class="hover:text-slate-900 transition">Datenschutz</a>
                    <a href="{{ route('legal.terms') }}" class="hover:text-slate-900 transition">AGB</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
