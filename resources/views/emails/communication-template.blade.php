<x-mail::message>
{!! nl2br(e($body)) !!}

{{ __('Mit freundlichen Grüßen') }}<br>
{{ $companyName }}
</x-mail::message>
