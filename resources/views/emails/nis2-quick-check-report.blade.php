@php($appName = config('app.name', 'PlanB'))
<x-mail::message>
# Ihre NIS2-Auswertung ist da

Vielen Dank für Ihre Bestätigung. Im Anhang finden Sie Ihre persönliche NIS2-Auswertung als PDF.

**Ergebnis:** {{ $readiness->label() }} — {{ $lead->score }} von {{ \App\Support\Marketing\Nis2QuickCheckCatalog::maxScore() }} Punkten

{{ $readiness->description() }}

@if (count($openRecommendations) > 0)
## Ihre wichtigsten Handlungsfelder

@foreach ($openRecommendations as $item)
- **{{ $item['title'] }}:** {{ $item['recommendation'] }}
@endforeach
@endif

<x-mail::button :url="route('home')">
Wie {{ $appName }} diese Lücken schließt
</x-mail::button>

Gerne begleiten wir Sie von der Selbsteinschätzung zur nachweisbaren Umsetzung – Notfallhandbuch, Rollen, Wiederanlaufpläne und Meldeprozesse an einem Ort.

{{ $appName }}
</x-mail::message>
