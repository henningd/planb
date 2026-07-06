{{--
    iPhone-Mockup der Notfall-App im Alarmfall (reines HTML/CSS, kein Screenshot).
    Per @include mit Daten überschreibbar: mockupTime, mockupPush, mockupTitle,
    mockupMeta, mockupChips (Array), mockupSteps (Array [text, done|pending|open]),
    mockupCaption.
--}}
@php
    $mockupTime = $mockupTime ?? '03:12';
    $mockupPush = $mockupPush ?? 'Notfall gemeldet: Stromausfall Rathaus';
    $mockupTitle = $mockupTitle ?? 'Stromausfall Rathaus';
    $mockupMeta = $mockupMeta ?? 'Gestartet 03:12 · Standort Rathaus';
    $mockupChips = $mockupChips ?? ['3× gesehen', 'M. Weber übernimmt'];
    $mockupSteps = $mockupSteps ?? [
        ['USV-Status im Serverraum prüfen', 'done'],
        ['Hausmeister & IT-Dienstleister informieren', 'done'],
        ['Notbetrieb Bürgerbüro ausrufen', 'pending'],
        ['Aushänge an den Eingängen anbringen', 'open'],
    ];
    $mockupCaption = $mockupCaption ?? 'Beispielansicht der Notfall-App im Alarmfall – offline, mit Quittierungen.';
@endphp
<div class="relative mx-auto w-[300px] max-w-full">
    <div class="absolute -inset-6 bg-gradient-to-tr from-indigo-500/25 via-indigo-400/10 to-transparent rounded-[3rem] blur-2xl"></div>
    <div class="relative rounded-[2.6rem] bg-slate-800 p-2.5 ring-1 ring-slate-700 shadow-2xl">
        <div class="rounded-[2.1rem] bg-slate-100 overflow-hidden">
            {{-- Statusleiste --}}
            <div class="flex items-center justify-between px-6 pt-3 pb-1 text-[10px] font-semibold text-slate-600">
                <span>{{ $mockupTime }}</span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3.5 h-2 rounded-[2px] ring-1 ring-slate-400"></span>
                </span>
            </div>
            {{-- Push-Banner --}}
            <div class="mx-2.5 mt-1.5 rounded-2xl bg-white/95 ring-1 ring-slate-200 shadow-lg px-3 py-2.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-rose-600 text-white text-[9px] font-bold">PB</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-slate-900">PlanB · Notfall</span>
                            <span class="text-[9px] text-slate-500">jetzt</span>
                        </div>
                        <p class="text-[10px] text-slate-700 truncate">{{ $mockupPush }}</p>
                    </div>
                </div>
            </div>
            {{-- App-Inhalt --}}
            <div class="px-3.5 pt-3 pb-4">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-slate-900">Aktiver Notfall</span>
                    <span class="inline-flex items-center gap-1 text-[9px] font-medium text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200 rounded-full px-1.5 py-0.5">
                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                        Offline verfügbar
                    </span>
                </div>
                <div class="mt-2 rounded-xl bg-rose-50 ring-1 ring-rose-200 px-3 py-2.5">
                    <div class="text-[11px] font-semibold text-rose-900">{{ $mockupTitle }}</div>
                    <div class="mt-0.5 text-[9px] text-rose-700">{{ $mockupMeta }}</div>
                    <div class="mt-1.5 flex gap-1">
                        @foreach ($mockupChips as $i => $chip)
                            @if ($i === count($mockupChips) - 1)
                                <span class="text-[8.5px] font-medium bg-indigo-600 text-white rounded-full px-1.5 py-0.5">{{ $chip }}</span>
                            @else
                                <span class="text-[8.5px] font-medium bg-white text-slate-700 ring-1 ring-slate-200 rounded-full px-1.5 py-0.5">{{ $chip }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="mt-2.5 space-y-1.5">
                    @foreach ($mockupSteps as [$step, $state])
                        <div class="flex items-center gap-2 rounded-lg bg-white ring-1 ring-slate-200 px-2.5 py-1.5">
                            @if ($state === 'done')
                                <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-emerald-500 text-white">
                                    <svg class="w-2 h-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                            @elseif ($state === 'pending')
                                <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-amber-400 text-white">
                                    <svg class="w-2 h-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                </span>
                            @else
                                <span class="w-3.5 h-3.5 rounded-full ring-1 ring-slate-300"></span>
                            @endif
                            <span class="text-[9.5px] {{ $state === 'done' ? 'text-slate-400 line-through' : 'text-slate-700' }}">{{ $step }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 flex items-center justify-between text-[8.5px] text-slate-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        1 Änderung wartet auf Sync
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        Face ID aktiv
                    </span>
                </div>
            </div>
        </div>
    </div>
    <p class="relative mt-4 text-center text-xs text-slate-400">{{ $mockupCaption }}</p>
</div>
