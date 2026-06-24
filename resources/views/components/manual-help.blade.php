@props(['slug', 'label' => null])

{{-- Kontextueller Hilfe-Link: springt in einem neuen Tab direkt in das
     passende Kapitel des Benutzerhandbuchs (/handbuch/{slug}). --}}
<flux:button
    :href="route('manual.show', $slug)"
    target="_blank"
    variant="ghost"
    size="sm"
    icon="question-mark-circle"
>
    {{ $label ?? __('Hilfe') }}
</flux:button>
