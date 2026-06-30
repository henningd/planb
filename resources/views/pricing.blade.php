@php
    $contactEmail = (string) \App\Support\Settings\SystemSetting::get('platform_contact_email');
    $companyName = 'Arento AI GmbH';
    $pricingDescription = 'Preise für '.$productName.' – Notfallhandbuch und BCM für Unternehmen. Starter, Business, Advanced, Enterprise. Monatlich oder jährlich.';

    // Preis-Definitionen — eine Quelle für Karten und Vergleichstabelle.
    $monthlyPrices = [
        'starter' => 49,
        'business' => 149,
        'advanced' => 389,
        'enterprise' => null, // individuell
    ];
    // Jahrespreis = 10 × Monatspreis (= "2 Monate gratis", entspricht ~17 % Rabatt).
    $yearlyPrices = [
        'starter' => $monthlyPrices['starter'] * 10,
        'business' => $monthlyPrices['business'] * 10,
        'advanced' => $monthlyPrices['advanced'] * 10,
        'enterprise' => null,
    ];

    $plans = [
        [
            'key' => 'starter',
            'name' => 'Starter',
            'tagline' => 'Notfallhandbuch erstellen — für kleine Organisationen und den Einstieg.',
            'audience' => 'Kleine Organisation · 1 Standort · 1 Admin',
            'monthly' => $monthlyPrices['starter'],
            'yearly' => $yearlyPrices['starter'],
            'cta' => 'Kostenlos starten',
            'cta_target' => 'register',
            'highlight' => false,
            'features' => [
                'Digitales Notfallhandbuch: Stammdaten, Rollen, Standorte',
                'Systeme, Kritikalität & Wiederanlaufplan',
                'Sofortmittel & Notfallbetrieb',
                'RTO / RPO je System',
                'PDF-Handbuch mit Versionen & Lesebestätigungen',
                'Dienstleister & Notfallkontakte',
                '2FA · Audit-Log · Branchen-Templates',
                '1 App-Admin · 1 Standort',
                'E-Mail-Support',
            ],
        ],
        [
            'key' => 'business',
            'name' => 'Business',
            'tagline' => 'Notfallhandbuch pflegen und Prävention steuern — für KMU, Kommunen, Pflege, Schulen.',
            'audience' => 'KMU / kleinere Kommunen · bis 3 Standorte · bis 3 Nutzer',
            'monthly' => $monthlyPrices['business'],
            'yearly' => $yearlyPrices['business'],
            'cta' => '14 Tage kostenlos testen',
            'cta_target' => 'register',
            'highlight' => false,
            'features' => [
                'Alles aus Starter',
                'Geschäftsprozesse mit RTO / RPO / MTPD',
                'Business Impact Analyse (BIA light)',
                'Risiko-Register: Bewertung & Behandlung',
                'Prävention: Prüfaufgaben, Aufgaben-Inbox, Fälligkeiten',
                'BSI 200-4 / NIS2-Orientierung',
                'Verträge / SLA mit Notfallnummern',
                'Tabletop-Übungen (manuell)',
                'bis 3 Nutzer · bis 3 Standorte',
                'Priorisierter E-Mail-Support · 1 h Onboarding',
            ],
        ],
        [
            'key' => 'advanced',
            'name' => 'Advanced',
            'tagline' => 'BCM und Krisenmanagement vollständig abbilden — für Mittelstand, Kommunen, NIS2-nahe Organisationen.',
            'audience' => 'Mittelstand / NIS2 · bis 5 Standorte · bis 10 Nutzer',
            'monthly' => $monthlyPrices['advanced'],
            'yearly' => $yearlyPrices['advanced'],
            'cta' => '14 Tage kostenlos testen',
            'cta_target' => 'register',
            'highlight' => true,
            'features' => [
                'Alles aus Business',
                'Krisen-Cockpit (Live-Lagebild) & War-Room',
                'Lessons Learned mit Maßnahmen',
                'Erweiterte Ausfallkosten & BIA',
                'Restrisiko-Bewertung & Risiko-Reporting',
                'Compliance-Score nach BSI 200-4 / NIS2',
                'Meldepflichten-Workflow mit Fristen-Tracking',
                'Übungsmodus mit Berichten',
                'E-Mail-Benachrichtigungen & Kalendereinladungen',
                'bis 10 Nutzer · bis 5 Standorte',
                'Hotline 8/5 · 2 h Onboarding-Workshop',
            ],
        ],
        [
            'key' => 'enterprise',
            'name' => 'Enterprise',
            'tagline' => 'Plattform für Berater, MSP und große Organisationen sowie KRITIS-Betreiber.',
            'audience' => '250+ / KRITIS · Multi-Mandant · unbegrenzt',
            'monthly' => null,
            'yearly' => null,
            'cta' => 'Demo anfragen',
            'cta_target' => 'demo',
            'highlight' => false,
            'features' => [
                'Alles aus Advanced',
                'Multi-Mandant & mandantenübergreifende Verwaltung',
                'White-Label mit eigener Domain',
                'API, Webhooks & erweiterte Integrationen',
                'IP-Restriktion für Freigabelinks',
                'Verschlüsseltes Mandantenarchiv',
                'Template-Verwaltung für mehrere Kunden',
                'Unbegrenzte Nutzer & Standorte',
                '24/7-Support + dedizierter Customer Success Manager',
                'Implementierungsbegleitung',
            ],
        ],
    ];

    // Vergleichstabelle: einheitliche Quelle, daraus rendern.
    // Zeilenformat: [Label, Starter, Business, Advanced, Enterprise]
    $comparison = [
        'Anwendung & Stammdaten' => [
            ['Stammdaten · Standorte · Rollen · Dienstleister', true, true, true, true],
            ['Systeme · Kritikalität · Wiederanlaufplan', true, true, true, true],
            ['Systemabhängigkeiten', false, true, true, true],
            ['Sofortmittel · Notfallbetrieb', true, true, true, true],
            ['PDF-Handbuch · Versionen · Lesebestätigungen', true, true, true, true],
            ['Branchen-Templates', 'vorgefertigt', 'erweitert', 'erweitert', '+ eigene'],
        ],
        'BCM & Business Impact' => [
            ['RTO / RPO je System', true, true, true, true],
            ['Geschäftsprozesse mit RTO / RPO / MTPD', false, true, true, true],
            ['Wiederanlauf-Reihenfolge · Abhängigkeiten', false, true, true, true],
            ['Ausfallkostenberechnung', false, 'einfach', 'erweitert', 'erweitert'],
            ['Business Impact Analyse', false, 'BIA light', 'erweitert', 'erweitert'],
        ],
        'Risiko & Prävention' => [
            ['Risiko-Register: Bewertung & Behandlung', false, true, true, true],
            ['Prüfaufgaben · Wiederkehrende Prüfungen · Inbox', false, true, true, true],
            ['Fälligkeiten · Verantwortliche zuweisen', false, true, true, true],
            ['Restrisiko-Bewertung · Risiko-Reporting', false, false, true, true],
            ['E-Mail-Benachrichtigungen · Kalendereinladungen', false, false, true, true],
        ],
        'Krise & Übung' => [
            ['Tabletop-Übungen (manuell)', false, true, true, true],
            ['Krisen-Cockpit · War-Room · Krisenprotokoll', false, false, true, true],
            ['Statusverfolgung · Kommunikationslisten', false, false, true, true],
            ['Lessons Learned mit Maßnahmen', false, false, true, true],
            ['Übungsmodus mit Berichten', false, false, true, true],
        ],
        'Compliance' => [
            ['2FA · Audit-Log · Rollen-/Rechtekonzept', true, true, true, true],
            ['BSI 200-4 / NIS2-Orientierung', false, true, true, true],
            ['Compliance-Score / Reifegrad', false, false, true, true],
            ['Meldepflichten-Workflow + Fristen-Tracking', false, false, true, true],
            ['Nachweisführung · Freigabelinks (Auditor / Versicherung)', false, false, true, true],
            ['IP-Restriktion für Freigabelinks', false, false, false, true],
        ],
        'Dienstleister & Verträge' => [
            ['Dienstleister · Ansprechpartner', true, true, true, true],
            ['Verträge / SLA · Notfallnummern je Vertrag', false, true, true, true],
            ['Unterschiedliche SLA je Dienstleister / System', false, true, true, true],
        ],
        'Mandanten, Branding & Integration' => [
            ['Ein Mandant', true, true, true, true],
            ['Multi-Mandant für Berater / MSPs', false, false, false, true],
            ['White-Label mit eigener Domain', false, false, false, true],
            ['API · Webhooks · erweiterte Integrationen', false, false, false, true],
            ['Verschlüsseltes Mandantenarchiv', false, false, false, true],
        ],
        'Nutzer, Standorte & Support' => [
            ['App-User', '1 Admin', 'bis 3', 'bis 10', 'unbegrenzt'],
            ['Standorte', '1', 'bis 3', 'bis 5', 'unbegrenzt'],
            ['Support', 'E-Mail', '+ priorisiert', '+ Hotline 8/5', '+ 24/7 & CSM'],
            ['Onboarding', 'self-service', '1 h', '2 h', 'Implementierung'],
        ],
    ];

    $faq = [
        [
            'q' => 'Was ist im 14-Tage-Test enthalten?',
            'a' => 'Voller Funktionsumfang des Advanced-Tarifs ohne Kreditkarten-Eingabe. Nach 14 Tagen entscheiden Sie, ob Sie auf Starter, Business, Advanced oder Enterprise wechseln — sonst friert das Konto ein, Ihre Daten bleiben 30 Tage exportierbar.',
        ],
        [
            'q' => 'Kann ich monatlich kündigen?',
            'a' => 'Im Monatstarif ja, jederzeit zum Periodenende. Im Jahrestarif zum Ablauf des bezahlten Jahres. Ihre Daten bleiben nach Kündigung 30 Tage als ZIP-Archiv abrufbar.',
        ],
        [
            'q' => 'Was passiert beim Wechsel zwischen den Plänen?',
            'a' => 'Upgrade jederzeit, anteilig zum Periodenende abgerechnet. Downgrade zum Periodenende. Beim Wechsel von Monat auf Jahr rechnen wir den verbleibenden Monat an.',
        ],
        [
            'q' => 'Sind die Preise netto oder brutto?',
            'a' => 'Alle Preise verstehen sich netto zzgl. gesetzlicher Umsatzsteuer (19 % in Deutschland). EU-B2B-Kunden mit gültiger USt-IdNr werden im Reverse-Charge-Verfahren berechnet.',
        ],
        [
            'q' => 'Wo werden meine Daten gespeichert?',
            'a' => 'Ausschließlich in zertifizierten Rechenzentren in Deutschland. Auftragsverarbeitungsvertrag (AVV) und TOM nach Art. 32 DSGVO sind Bestandteil jedes Vertrags.',
        ],
        [
            'q' => 'Geld-zurück-Garantie?',
            'a' => '30 Tage volle Rückerstattung auf das Jahresabonnement, ohne Begründung.',
        ],
        [
            'q' => 'Ich habe mehrere Mandanten / Kunden — wie funktioniert das?',
            'a' => 'Im Enterprise-Tarif können Sie als Berater oder MSP beliebig viele Mandanten verwalten, mit eigenem Branding und konsolidierter Abrechnung. Sprechen Sie uns an.',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pricingDescription }}">
    <title>Preise – {{ $productName }}</title>
    @include('partials.seo-meta', [
        'seoTitle' => 'Preise – '.$productName,
        'seoDescription' => $pricingDescription,
        'seoUrl' => route('pricing.show'),
        'seoBreadcrumbs' => [
            ['name' => $productName, 'item' => route('home')],
            ['name' => 'Preise', 'item' => route('pricing.show')],
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

        <div class="max-w-5xl mx-auto px-6 lg:px-8 pt-16 lg:pt-20 pb-10 text-center">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium ring-1 ring-emerald-100">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                14 Tage kostenlos testen — ohne Kreditkarte
            </span>

            <h1 class="mt-6 text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900 leading-tight">
                Faire Preise — <span class="text-indigo-600">passend zur Unternehmensgröße.</span>
            </h1>

            <p class="mt-4 text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                Vom kleinen Handwerksbetrieb bis zum NIS2-pflichtigen Mittelständler. Alle Pläne enthalten das vollständige Notfallhandbuch, PDF-Export, Audit-Log und 2FA.
            </p>
        </div>
    </section>

    {{-- ============ TOGGLE + KARTEN ============ --}}
    <section data-billing-section class="py-8 lg:py-12">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">

            {{-- Toggle --}}
            <div class="flex justify-center mb-10">
                <div class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white p-1 shadow-sm">
                    <button
                        type="button"
                        data-billing-toggle="monthly"
                        class="billing-btn px-4 py-2 text-sm font-medium rounded-full transition text-slate-600 hover:text-slate-900"
                    >
                        Monatlich
                    </button>
                    <button
                        type="button"
                        data-billing-toggle="yearly"
                        aria-pressed="true"
                        class="billing-btn px-4 py-2 text-sm font-medium rounded-full transition bg-slate-900 text-white shadow-sm flex items-center gap-2"
                    >
                        Jährlich
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                            2 Monate gratis
                        </span>
                    </button>
                </div>
            </div>

            {{-- Karten --}}
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5 lg:gap-6">
                @foreach ($plans as $plan)
                    @php
                        $cardClasses = $plan['highlight']
                            ? 'ring-2 ring-indigo-600 shadow-lg shadow-indigo-100'
                            : 'ring-1 ring-slate-200';
                        $registerUrl = $plan['cta_target'] === 'register'
                            ? route('register').'?plan='.$plan['key']
                            : route('home').'#kontakt';
                    @endphp
                    <div class="relative flex flex-col rounded-2xl bg-white p-6 {{ $cardClasses }}">
                        @if ($plan['highlight'])
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-600 text-white text-xs font-semibold shadow-sm">
                                    Empfohlen
                                </span>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $plan['tagline'] }}</p>
                            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $plan['audience'] }}</p>
                        </div>

                        {{-- Preis --}}
                        <div class="mt-6 pb-6 border-b border-slate-100">
                            @if ($plan['monthly'] === null)
                                <div class="text-3xl font-semibold text-slate-900">individuell</div>
                                <div class="mt-1 text-sm text-slate-500">auf Anfrage</div>
                            @else
                                {{-- Monatspreis --}}
                                <div data-price-monthly hidden>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-3xl font-semibold text-slate-900">{{ $plan['monthly'] }} €</span>
                                        <span class="text-sm text-slate-500">/Monat</span>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">monatlich abgerechnet · jederzeit kündbar</div>
                                </div>
                                {{-- Jahrespreis --}}
                                <div data-price-yearly>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-3xl font-semibold text-slate-900">{{ number_format($plan['yearly'] / 12, 0, ',', '.') }} €</span>
                                        <span class="text-sm text-slate-500">/Monat</span>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ number_format($plan['yearly'], 0, ',', '.') }} € jährlich abgerechnet
                                    </div>
                                    <div class="mt-1 text-xs font-medium text-emerald-700">
                                        Sie sparen {{ number_format($plan['monthly'] * 12 - $plan['yearly'], 0, ',', '.') }} € pro Jahr
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Features --}}
                        <ul class="mt-6 space-y-3 text-sm text-slate-700 flex-1">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0 {{ $plan['highlight'] ? 'text-indigo-600' : 'text-emerald-600' }}" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        {{-- CTA --}}
                        <a
                            href="{{ $registerUrl }}"
                            class="mt-8 inline-flex items-center justify-center px-5 py-3 rounded-lg font-medium transition
                                {{ $plan['highlight']
                                    ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm'
                                    : 'bg-slate-900 text-white hover:bg-slate-800' }}"
                        >
                            {{ $plan['cta'] }}
                        </a>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 text-center text-sm text-slate-500">
                Alle Preise netto zzgl. gesetzlicher Umsatzsteuer. EU-B2B-Reverse-Charge mit gültiger USt-IdNr.
            </p>
        </div>
    </section>

    {{-- ============ VERGLEICHSTABELLE ============ --}}
    <section class="py-16 lg:py-20 bg-slate-50 border-y border-slate-100">
        <div class="max-w-6xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Im Detail</span>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Welcher Plan passt?</h2>
                <p class="mt-3 text-slate-600">Funktion-für-Funktion-Vergleich für die Entscheidung im Detail.</p>
            </div>

            <div class="overflow-x-auto rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-6 py-4 text-left font-semibold text-slate-900">Funktion</th>
                            <th class="px-4 py-4 text-center font-semibold text-slate-900">Starter</th>
                            <th class="px-4 py-4 text-center font-semibold text-slate-900">Business</th>
                            <th class="px-4 py-4 text-center font-semibold text-indigo-700 bg-indigo-50/50">Advanced</th>
                            <th class="px-4 py-4 text-center font-semibold text-slate-900">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparison as $section => $rows)
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <td colspan="5" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $section }}</td>
                            </tr>
                            @foreach ($rows as $row)
                                <tr class="border-b border-slate-100 last:border-b-0">
                                    <td class="px-6 py-3 text-slate-700">{{ $row[0] }}</td>
                                    @foreach ([$row[1], $row[2], $row[3], $row[4]] as $i => $val)
                                        @php
                                            $isHighlight = $i === 2; // Advanced-Spalte
                                        @endphp
                                        <td class="px-4 py-3 text-center {{ $isHighlight ? 'bg-indigo-50/30' : '' }}">
                                            @if ($val === true)
                                                <svg class="w-5 h-5 mx-auto text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            @elseif ($val === false)
                                                <svg class="w-4 h-4 mx-auto text-slate-300" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <span class="text-xs text-slate-600">{{ $val }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section class="py-16 lg:py-20">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">FAQ</span>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Häufige Fragen zu den Preisen</h2>
            </div>

            <div class="space-y-4">
                @foreach ($faq as $entry)
                    <details class="group rounded-xl bg-white ring-1 ring-slate-200 px-5 py-4 open:shadow-sm transition">
                        <summary class="flex items-center justify-between cursor-pointer list-none">
                            <span class="font-medium text-slate-900">{{ $entry['q'] }}</span>
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </summary>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $entry['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA-BLOCK ============ --}}
    <section class="py-16 lg:py-20 bg-slate-900">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-white">
                Noch unsicher? Testen Sie Advanced 14 Tage kostenlos.
            </h2>
            <p class="mt-4 text-lg text-slate-300 max-w-2xl mx-auto">
                Ohne Kreditkarte. Wenn es passt, wechseln Sie in den Plan Ihrer Wahl. Wenn nicht, friert das Konto automatisch ein.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}?plan=advanced" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-slate-900 font-medium hover:bg-slate-100 transition shadow-sm">
                    14 Tage kostenlos testen
                </a>
                @if ($contactEmail)
                    <a href="mailto:{{ $contactEmail }}?subject=Demo-Anfrage%20Enterprise" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-transparent text-white font-medium ring-1 ring-white/30 hover:ring-white/60 hover:bg-white/5 transition">
                        Demo anfragen
                    </a>
                @endif
            </div>
        </div>
    </section>

    @include('partials.marketing-footer')

    <script>
        (function () {
            const buttons = document.querySelectorAll('[data-billing-toggle]');
            const monthlyEls = document.querySelectorAll('[data-price-monthly]');
            const yearlyEls = document.querySelectorAll('[data-price-yearly]');

            function setMode(mode) {
                buttons.forEach((btn) => {
                    const isActive = btn.dataset.billingToggle === mode;
                    btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    if (isActive) {
                        btn.classList.add('bg-slate-900', 'text-white', 'shadow-sm');
                        btn.classList.remove('text-slate-600', 'hover:text-slate-900');
                    } else {
                        btn.classList.remove('bg-slate-900', 'text-white', 'shadow-sm');
                        btn.classList.add('text-slate-600', 'hover:text-slate-900');
                    }
                });
                monthlyEls.forEach((el) => { el.hidden = mode !== 'monthly'; });
                yearlyEls.forEach((el) => { el.hidden = mode !== 'yearly'; });
            }

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => setMode(btn.dataset.billingToggle));
            });
        })();
    </script>
</body>
</html>
