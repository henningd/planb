<x-mail::message>
# Neue Registrierung

Es hat sich gerade ein neuer Benutzer registriert:

- **Name:** {{ $user->name }}
- **E-Mail:** {{ $user->email }}
- **Zeitpunkt:** {{ $user->created_at?->format('d.m.Y H:i') }} Uhr

Der Account ist erst aktiv, sobald die E-Mail-Adresse bestätigt und die Zwei-Faktor-Authentifizierung eingerichtet wurde.

@php($appName = config('app.name', 'PlanB'))
{{ $appName }}
</x-mail::message>
