<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
**PlanB** – Digitales Notfallhandbuch für den Mittelstand
BSI 200-4 · ISO 22301 · NIS2-ready

© {{ date('Y') }} PlanB. Alle Rechte vorbehalten.
Diese E-Mail wurde automatisch versendet – bitte antworten Sie nicht direkt.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
