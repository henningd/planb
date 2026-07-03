@php($appName = config('app.name', 'PlanB'))
<x-mail::message>
# Nur noch ein Schritt zu Ihrer NIS2-Auswertung

Vielen Dank für Ihr Interesse am NIS2 Quick-Check. Bitte bestätigen Sie mit einem Klick, dass Sie Ihre persönliche Auswertung an diese E-Mail-Adresse erhalten möchten.

<x-mail::button :url="$confirmUrl">
Auswertung jetzt bestätigen
</x-mail::button>

Nach der Bestätigung senden wir Ihnen umgehend Ihr Ergebnis mit priorisierten Handlungsempfehlungen als PDF zu.

Falls Sie diesen Check nicht angefordert haben, können Sie diese E-Mail einfach ignorieren – ohne Ihre Bestätigung wird nichts weiter versendet.

{{ $appName }}
</x-mail::message>
