@php
    $team = $invitation->team;
    $inviter = $invitation->inviter;
    $role = $invitation->role->label();
    $acceptUrl = url("/invitations/{$invitation->code}/accept");
    $expiresAt = $invitation->expires_at?->copy()->setTimezone('Europe/Berlin')->format('d.m.Y \u\m H:i \U\h\r');
@endphp

<x-mail::message>
# Willkommen bei PlanB 👋

**{{ $inviter->name }}** hat Sie eingeladen, dem Team
**„{{ $team->name }}"** beizutreten.

PlanB ist Ihr digitales Notfallhandbuch: klar, geführt, einsatzbereit –
damit Ihr Unternehmen im Ernstfall nicht planlos ist.

<x-mail::panel>
**Ihre Rolle im Team:** {{ $role }}
**Eingeladen von:** {{ $inviter->name }}
**Einladung gültig bis:** {{ $expiresAt }}
</x-mail::panel>

<x-mail::button :url="$acceptUrl" color="primary">
Einladung annehmen
</x-mail::button>

## Was Sie mit PlanB tun können

- **Systeme & Dienstleister strukturiert erfassen** – inkl. Prioritäten und Ansprechpartnern
- **Notfall-Szenarien durchspielen** – mit klaren Rollen (RACI) und Aufgaben
- **Meldefristen nicht verpassen** – NIS2, DSGVO, Cyber-Versicherung
- **Notfallhandbuch auf Knopfdruck** – immer aktuell, auch offline als PDF

---

Falls Sie diese Einladung nicht erwartet haben, können Sie diese Mail
einfach ignorieren – es passiert nichts.

Viele Grüße
Ihr PlanB-Team

<x-slot:subcopy>
Falls der Button „Einladung annehmen" nicht funktioniert, kopieren Sie
diesen Link in Ihren Browser:
[{{ $acceptUrl }}]({{ $acceptUrl }})
</x-slot:subcopy>
</x-mail::message>
