<x-mail::message>
# Neue Anfrage über die Website ({{ $lead->source === 'kommunen' ? 'Kommunen-Seite' : 'Startseite' }})

**Name:** {{ $lead->contact_name }}<br>
**Kommune / Organisation:** {{ $lead->company_name }}<br>
**E-Mail:** {{ $lead->email }}<br>
**Telefon:** {{ $lead->answers['phone'] ?? '—' }}

## Nachricht

{{ $lead->answers['message'] ?? '' }}

<x-mail::panel>
Antworten gehen per Reply direkt an die anfragende Person.
</x-mail::panel>
</x-mail::message>
