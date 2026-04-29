@extends('manual.layout')

@section('title', 'Benutzerhandbuch')

@section('content')
    <div class="prose max-w-none">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Benutzerhandbuch</h1>
        <p class="mt-3 text-lg text-slate-600 leading-relaxed">
            Diese Anleitung führt Schritt für Schritt durch die Plattform — von der ersten Registrierung bis
            zur ersten freigegebenen Handbuch-Version. Geschrieben für Menschen, die ein Notfallhandbuch
            zum ersten Mal aufbauen.
        </p>
    </div>

    <form method="GET" action="{{ route('manual.index') }}" class="mt-8" role="search">
        <label for="manual-search" class="sr-only">Im Handbuch suchen</label>
        <div class="relative">
            <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path d="m21 21-4.3-4.3"/>
            </svg>
            <input
                id="manual-search"
                type="search"
                name="q"
                value="{{ $searchQuery ?? '' }}"
                placeholder="Im Handbuch suchen — z. B. „RTO“, „Lessons Learned“, „2FA“…"
                autocomplete="off"
                class="block w-full rounded-lg border border-slate-300 bg-white py-3 pl-12 pr-4 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
    </form>

    @if (! empty($searchQuery) && isset($searchResults))
        @php
            $highlight = function (string $text, string $needle): string {
                if (trim($needle) === '') {
                    return e($text);
                }
                $escaped = e($text);
                $pattern = '/'.preg_quote($needle, '/').'/iu';
                $result = preg_replace_callback(
                    $pattern,
                    fn ($m) => '<mark class="bg-yellow-100 text-slate-900">'.e($m[0]).'</mark>',
                    $escaped
                );

                return $result ?? $escaped;
            };
        @endphp

        <div class="mt-8">
            <h2 class="text-xl font-semibold text-slate-900 border-b border-slate-200 pb-2">
                {{ count($searchResults) }} {{ count($searchResults) === 1 ? 'Treffer' : 'Treffer' }} für „{{ $searchQuery }}"
            </h2>

            @if (count($searchResults) === 0)
                <p class="mt-4 text-slate-600">Keine passenden Kapitel gefunden. Versuchen Sie andere oder weniger Wörter.</p>
            @else
                <ul class="mt-4 space-y-4">
                    @foreach ($searchResults as $hit)
                        <li class="rounded-lg ring-1 ring-slate-200 bg-white p-4 hover:ring-indigo-300 hover:shadow-sm transition">
                            <a href="{{ route('manual.show', $hit['entry']['slug']) }}" class="block">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $hit['entry']['category'] }}</div>
                                <div class="mt-0.5 font-medium text-slate-900">{!! $highlight($hit['entry']['title'], $searchQuery) !!}</div>
                                <p class="mt-1 text-sm text-slate-600 leading-relaxed">{!! $highlight($hit['snippet'], $searchQuery) !!}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6">
                <a href="{{ route('manual.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Zurück zum vollständigen Inhaltsverzeichnis</a>
            </div>
        </div>
    @else
        <div class="mt-10 space-y-8">
            @foreach ($grouped as $category => $entries)
                <section>
                    <h2 class="text-xl font-semibold text-slate-900 border-b border-slate-200 pb-2">{{ $category }}</h2>
                    <div class="mt-4 grid sm:grid-cols-2 gap-4">
                        @foreach ($entries as $entry)
                            <a href="{{ route('manual.show', $entry['slug']) }}" class="block p-4 rounded-lg ring-1 ring-slate-200 bg-white hover:ring-indigo-300 hover:shadow-sm transition">
                                <div class="font-medium text-slate-900">{{ $entry['title'] }}</div>
                                <div class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $entry['summary'] }}</div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
