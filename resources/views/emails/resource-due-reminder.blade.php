@php
    $name = $resource->name ?: $resource->type->label();
    $dueText = $resource->next_check_at?->translatedFormat('d.m.Y') ?? 'unbekannt';
@endphp

<x-mail::message>
# Notfall-Ressource prüfen: {{ $name }}

Eine Ihrer Notfall-Ressourcen steht zur turnusmäßigen Prüfung an.

<x-mail::panel>
**Ressource:** {{ $name }}
**Typ:** {{ $resource->type->label() }}
@if ($resource->location)
**Standort:** {{ $resource->location }}
@endif
**Nächste Prüfung:** {{ $dueText }}
</x-mail::panel>

@if ($resource->description)
**Beschreibung:**
{{ $resource->description }}
@endif

## So gehen Sie vor

1. Ressource am Standort sichten und auf Vollständigkeit/Funktion prüfen.
2. Bei Bedarf nachfüllen, austauschen oder Zugriffsberechtigte anpassen.
3. Im Handbuch unter „Notfall-Ressourcen" das Datum der letzten Prüfung aktualisieren – das nächste Prüfdatum wird gesetzt.

Viele Grüße
Ihr PlanB-Team
</x-mail::message>
