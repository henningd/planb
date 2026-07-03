@php
    $companyName = 'Arento AI GmbH';
@endphp
<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 dark:text-zinc-100">
    <main class="mx-auto flex min-h-svh max-w-2xl flex-col items-center justify-center px-6 py-16 text-center">
        <flux:icon.check-circle class="h-14 w-14 text-emerald-600 dark:text-emerald-400" />
        <h1 class="mt-6 text-3xl font-bold tracking-tight">{{ __('E-Mail-Adresse bestätigt') }}</h1>
        <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-300">
            {{ __('Vielen Dank! Ihre ausführliche NIS2-Auswertung ist unterwegs zu Ihnen und sollte in wenigen Minuten in Ihrem Postfach liegen.') }}
        </p>
        <flux:button href="{{ route('home') }}" variant="primary" class="mt-8">
            {{ __('Mehr über') }} {{ $productName }} {{ __('erfahren') }}
        </flux:button>
    </main>

    @fluxScripts
</body>
</html>
