<x-mail::message>
# {{ $sourceLabel }} zugewiesen: {{ $title }}

Hallo {{ $recipientName }},

Ihnen wurde die folgende {{ $sourceLabel }} zugewiesen. Sie bleibt zusätzlich in Ihrer Aufgaben-Inbox sichtbar.

<x-mail::panel>
**{{ $sourceLabel }}:** {{ $title }}
**Fällig:** {{ $dueLabel }}
@if ($intervalLabel)
**Wiederholung:** {{ $intervalLabel }}
@endif
</x-mail::panel>

@if ($description)
**Beschreibung:**
{{ $description }}
@endif

@if ($hasCalendar)
Im Anhang finden Sie eine Kalender-Einladung (`termin.ics`), die Sie in Outlook, Google oder Apple Kalender übernehmen können.
@endif

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Im Notfallhandbuch öffnen
</x-mail::button>
@endif

Viele Grüße
Ihr PlanB-Team
</x-mail::message>
