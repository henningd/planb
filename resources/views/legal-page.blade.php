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
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

    @include('partials.marketing-header')

    <main class="max-w-3xl mx-auto px-6 lg:px-8 py-12 lg:py-20">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $heading }}</h1>

        @if (trim($content) !== '')
            <div class="mt-8 prose prose-slate max-w-none whitespace-pre-line text-slate-700 leading-relaxed">{{ $content }}</div>
        @else
            <div class="mt-8 rounded-xl border border-amber-300 bg-amber-50 p-6 text-amber-900">
                <div class="font-semibold">{{ __('Inhalt steht aus.') }}</div>
                <p class="mt-2 text-sm leading-relaxed">
                    {{ $emptyHint }}
                </p>
                <p class="mt-3 text-xs text-amber-800/80">
                    {{ __('Plattform-Betreiber: Text in den Plattform-Einstellungen unter dem Schlüssel') }} <code class="font-mono">{{ $settingKey }}</code> {{ __('hinterlegen.') }}
                </p>
            </div>
        @endif
    </main>

    @include('partials.marketing-footer')
</body>
</html>
