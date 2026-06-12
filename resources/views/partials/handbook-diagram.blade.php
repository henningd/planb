{{--
    Bausteine eines Notfallhandbuchs als Hub-and-Spoke-Diagramm:
    Zentrum = Notfallhandbuch mit Pflege-Kreislauf (Erstellen/Pflegen/Üben),
    außen die sieben Bausteine aus dem Ratgeber. Inline-SVG, damit es scharf
    skaliert und die Markenfarben (Indigo/Slate) direkt nutzt.
--}}
<svg viewBox="0 0 800 660" xmlns="http://www.w3.org/2000/svg" role="img"
     aria-label="Die Bausteine eines Notfallhandbuchs: Rollen und Vertretungen, kritische Systeme, Wiederanlaufpläne, Notfallkontakte, Kommunikationsvorlagen, Szenario-Checklisten und Meldepflichten – verbunden durch den Kreislauf Erstellen, Pflegen, Üben."
     class="w-full h-auto" font-family="ui-sans-serif, system-ui, sans-serif">
    <defs>
        <radialGradient id="hubGradient" cx="35%" cy="30%" r="80%">
            <stop offset="0%" stop-color="#6366f1"/>
            <stop offset="100%" stop-color="#3730a3"/>
        </radialGradient>
        <filter id="cardShadow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="2" stdDeviation="4" flood-color="#0f172a" flood-opacity="0.08"/>
        </filter>
        <marker id="cycleArrow" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
            <path d="M0 0L10 5L0 10z" fill="#818cf8"/>
        </marker>
    </defs>

    {{-- Verbindungslinien Hub → Bausteine --}}
    <g stroke="#c7d2fe" stroke-width="1.5">
        <line x1="400" y1="195" x2="400" y2="123"/>
        <line x1="505" y1="246" x2="565" y2="198"/>
        <line x1="531" y1="360" x2="610" y2="378"/>
        <line x1="458" y1="451" x2="492" y2="514"/>
        <line x1="342" y1="451" x2="308" y2="514"/>
        <line x1="269" y1="360" x2="190" y2="378"/>
        <line x1="295" y1="246" x2="235" y2="198"/>
    </g>

    {{-- Pflege-Kreislauf um den Hub --}}
    <g fill="none" stroke="#818cf8" stroke-width="2">
        <path d="M 466 218 A 130 130 0 0 1 528 342" marker-end="url(#cycleArrow)"/>
        <path d="M 466 442 A 130 130 0 0 1 334 442" marker-end="url(#cycleArrow)"/>
        <path d="M 272 342 A 130 130 0 0 1 334 218" marker-end="url(#cycleArrow)"/>
    </g>
    <g font-size="13" font-weight="600" fill="#4f46e5" text-anchor="middle">
        <g transform="translate(540, 268)">
            <rect x="-38" y="-12" width="76" height="22" rx="11" fill="#eef2ff"/>
            <text y="4">Erstellen</text>
        </g>
        <g transform="translate(400, 478)">
            <rect x="-32" y="-12" width="64" height="22" rx="11" fill="#eef2ff"/>
            <text y="4">Pflegen</text>
        </g>
        <g transform="translate(262, 268)">
            <rect x="-28" y="-12" width="56" height="22" rx="11" fill="#eef2ff"/>
            <text y="4">Üben</text>
        </g>
    </g>

    {{-- Hub: Notfallhandbuch --}}
    <circle cx="400" cy="330" r="86" fill="url(#hubGradient)"/>
    <circle cx="400" cy="330" r="86" fill="none" stroke="#312e81" stroke-opacity="0.3"/>
    <g transform="translate(385, 280) scale(1.3)" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" transform="translate(-0.5, -1)"/>
    </g>
    <text x="400" y="345" text-anchor="middle" font-size="17" font-weight="700" fill="#ffffff">Notfall-</text>
    <text x="400" y="365" text-anchor="middle" font-size="17" font-weight="700" fill="#ffffff">handbuch</text>

    {{-- Bausteine --}}
    @php($cards = [
        ['x' => 400, 'y' => 92,  'label1' => 'Rollen &', 'label2' => 'Vertretungen', 'icon' => 'users'],
        ['x' => 588, 'y' => 181, 'label1' => 'Kritische', 'label2' => 'Systeme', 'icon' => 'server'],
        ['x' => 634, 'y' => 384, 'label1' => 'Wiederanlauf-', 'label2' => 'pläne', 'icon' => 'refresh'],
        ['x' => 504, 'y' => 547, 'label1' => 'Notfall-', 'label2' => 'kontakte', 'icon' => 'phone'],
        ['x' => 296, 'y' => 547, 'label1' => 'Kommunikations-', 'label2' => 'vorlagen', 'icon' => 'message'],
        ['x' => 166, 'y' => 384, 'label1' => 'Szenario-', 'label2' => 'Checklisten', 'icon' => 'check'],
        ['x' => 212, 'y' => 181, 'label1' => 'Meldepflichten', 'label2' => 'NIS2 / DSGVO', 'icon' => 'bell'],
    ])
    @foreach ($cards as $card)
        <g transform="translate({{ $card['x'] - 88 }}, {{ $card['y'] - 31 }})" filter="url(#cardShadow)">
            <rect width="176" height="62" rx="14" fill="#ffffff" stroke="#e2e8f0"/>
            <circle cx="31" cy="31" r="18" fill="#eef2ff"/>
            <g transform="translate(20, 20) scale(0.92)" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                @switch($card['icon'])
                    @case('users')
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        @break
                    @case('server')
                        <rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>
                        @break
                    @case('refresh')
                        <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/><path d="M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                        @break
                    @case('phone')
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        @break
                    @case('message')
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        @break
                    @case('check')
                        <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        @break
                    @case('bell')
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        @break
                @endswitch
            </g>
            <text x="58" y="27" font-size="13.5" font-weight="600" fill="#0f172a">{{ $card['label1'] }}</text>
            <text x="58" y="45" font-size="13.5" font-weight="600" fill="#0f172a">{{ $card['label2'] }}</text>
        </g>
    @endforeach
</svg>
