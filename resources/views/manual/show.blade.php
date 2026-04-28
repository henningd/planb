@extends('manual.layout')

@section('title', $entry['title'])

@section('content')
    <article class="prose max-w-none">
        <div class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ $entry['category'] }}</div>
        <h1 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $entry['title'] }}</h1>
        <p class="mt-3 text-lg text-slate-600 leading-relaxed">{{ $entry['summary'] }}</p>

        <div class="mt-8">
            {!! $html !!}
        </div>

        <div class="mt-12 pt-6 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4">
            @if ($previous)
                <a href="{{ route('manual.show', $previous['slug']) }}" class="text-sm text-slate-600 hover:text-slate-900 transition">
                    ← {{ $previous['title'] }}
                </a>
            @else
                <span></span>
            @endif
            @if ($next)
                <a href="{{ route('manual.show', $next['slug']) }}" class="text-sm text-slate-600 hover:text-slate-900 transition text-right">
                    {{ $next['title'] }} →
                </a>
            @endif
        </div>
    </article>
@endsection
