<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }} – {{ $productName }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        .prose h2 { margin-top: 2rem; font-size: 1.4rem; font-weight: 600; color: rgb(15 23 42); }
        .prose h3 { margin-top: 1.5rem; font-size: 1.1rem; font-weight: 600; color: rgb(15 23 42); }
        .prose p { margin-top: 0.75rem; line-height: 1.7; color: rgb(51 65 85); }
        .prose ul { list-style: disc; padding-left: 1.25rem; margin-top: 0.5rem; }
        .prose li { margin-top: 0.2rem; }
        .prose strong { color: rgb(15 23 42); font-weight: 600; }
        .prose table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9em; }
        .prose th, .prose td { border: 1px solid rgb(226 232 240); padding: 0.5rem 0.75rem; text-align: left; vertical-align: top; }
        .prose th { background: rgb(248 250 252); font-weight: 600; }
        .prose code { background: rgb(241 245 249); padding: 0.1em 0.4em; border-radius: 0.25rem; font-size: 0.9em; }
        .prose a { color: rgb(79 70 229); text-decoration: underline; }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

    @include('partials.marketing-header')

    <main class="max-w-3xl mx-auto px-6 lg:px-8 py-12 lg:py-16">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $heading }}</h1>

        @if (trim($content) !== '')
            <div class="mt-6 prose max-w-none">
                {!! $html !!}
            </div>
        @else
            <div class="mt-8 rounded-xl border border-amber-300 bg-amber-50 p-6 text-amber-900">
                <div class="font-semibold">{{ __('Inhalt steht aus.') }}</div>
                <p class="mt-2 text-sm leading-relaxed">{{ $emptyHint }}</p>
                <p class="mt-3 text-xs text-amber-800/80">
                    {{ __('Plattform-Betreiber: Text in den Plattform-Einstellungen unter dem Schlüssel') }} <code>{{ $settingKey }}</code> {{ __('hinterlegen.') }}
                </p>
            </div>
        @endif
    </main>

    @include('partials.marketing-footer')
</body>
</html>
