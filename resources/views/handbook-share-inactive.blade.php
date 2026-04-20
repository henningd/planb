<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ __('Freigabelink nicht aktiv') }}</title>
    <style>
        body {
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1rem;
        }
        .card {
            max-width: 480px;
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            text-align: center;
        }
        h1 { margin: 0 0 0.5rem; font-size: 1.25rem; }
        p { color: #64748b; margin: 0; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>
            @if ($reason === 'expired')
                {{ __('Dieser Freigabelink ist abgelaufen.') }}
            @else
                {{ __('Dieser Freigabelink wurde widerrufen.') }}
            @endif
        </h1>
        <p>{{ __('Bitte wenden Sie sich an den Herausgeber des Handbuchs, wenn Sie weiter Zugriff benötigen.') }}</p>
    </div>
</body>
</html>
