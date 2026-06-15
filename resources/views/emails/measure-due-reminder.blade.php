@php
    $dueText = $measure->next_due_at?->translatedFormat('d.m.Y') ?? 'unbekannt';
@endphp

<x-mail::message>
# Präventivmaßnahme fällig: {{ $measure->title }}

Eine wiederkehrende vorbeugende Maßnahme steht zur Durchführung an – sie hilft, einen Ausfall zu verhindern, bevor er passiert.

<x-mail::panel>
**Maßnahme:** {{ $measure->title }}
**System:** {{ $measure->system?->name ?? '—' }}
**Kategorie:** {{ $measure->category->label() }}
**Intervall:** {{ $measure->interval?->label() ?? '—' }}
**Fällig bis:** {{ $dueText }}
</x-mail::panel>

@if ($measure->description)
**Beschreibung:**
{{ $measure->description }}
@endif

## So gehen Sie vor

1. Maßnahme gemäß Beschreibung durchführen.
2. Ergebnis und Wirksamkeit kurz festhalten.
3. Unter „Prävention" als „durchgeführt" markieren – das nächste Fälligkeitsdatum wird automatisch gesetzt.

Bleibt die Maßnahme offen, taucht sie weiterhin als überfällig in der Aufgaben-Inbox auf.

Viele Grüße
Ihr PlanB-Team
</x-mail::message>
