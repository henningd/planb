@php
    $name = $test->name ?: $test->type->label();
    $dueText = $test->next_due_at?->translatedFormat('d.m.Y') ?? 'unbekannt';
    $intervalLabel = $test->interval->label();
@endphp

<x-mail::message>
# Handbuch-Test fällig: {{ $name }}

Ein turnusmäßiger Test aus Ihrem Notfallhandbuch steht an.

<x-mail::panel>
**Test:** {{ $name }}
**Typ:** {{ $test->type->label() }}
**Intervall:** {{ $intervalLabel }}
**Fällig bis:** {{ $dueText }}
</x-mail::panel>

@if ($test->description)
**Beschreibung:**
{{ $test->description }}
@endif

## So gehen Sie vor

1. Test gemäß Beschreibung durchführen.
2. Ergebnis (was hat funktioniert, was nicht) kurz festhalten.
3. Im Handbuch unter „Tests" als „durchgeführt" markieren – das nächste Fälligkeitsdatum wird automatisch gesetzt.

Bleibt der Test offen, taucht er weiterhin als überfällig im Dashboard auf.

Viele Grüße
Ihr PlanB-Team
</x-mail::message>
