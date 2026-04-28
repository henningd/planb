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
@endsection
