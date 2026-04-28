## Wozu Freigabelinks

Manchmal soll jemand das Notfallhandbuch sehen, ohne ein Konto zu haben — der Wirtschaftsprüfer, der IT-Auditor, der Versicherungsmakler. Ein **Freigabelink** ist eine read-only-URL mit Ablauf, die Sie ohne weitere Authentifizierung teilen können.

Erreichbar über die Sidebar **„Team & Freigaben → Freigabelinks"** (Admin only).

## Einen Link anlegen

Knopf **„Neuer Freigabelink"**. Pflichtangaben:

- **Bezeichnung** — z. B. „Wirtschaftsprüfer Q2/2026" oder „IT-Audit Allianz".
- **Gültig bis** — Datum, ab dem der Link nicht mehr funktioniert.

Optional:

- **Versions-Bezug** — falls Sie eine konkrete Handbuch-Version teilen, die nicht die aktuellste ist.

Beim Speichern wird ein **Token** generiert. Die vollständige URL sieht aus: `https://app.example.com/shared-handbook/abc123…`

## URL teilen

Auf der Liste pro Link ein **Kopier-Knopf**. Klick legt die URL in die Zwischenablage. Sie verschicken sie dann selbst per Mail oder Whatsapp.

## Was der Empfänger sieht

Bei Klick auf den Link öffnet sich das Notfallhandbuch als **Print-Vorschau** (HTML-Version, inhaltsgleich zum PDF):

- Firma, Standorte, Mitarbeiter, Rollen, Dienstleister, Systeme, Szenarien.
- Read-only — keine Knöpfe zum Bearbeiten.

Versicherungs-Daten und persönliche Notfall-Kontakte werden **nicht** geteilt — auch wenn der Link aktiv ist.

## Widerrufen

Pro Link gibt es einen **„Widerrufen"-Knopf**. Klick setzt den Status auf „widerrufen", der Link funktioniert sofort nicht mehr (Empfänger sieht „Link nicht mehr aktiv").

## Statistik

Pro Link werden Zugriffe gezählt:

- **Letzter Zugriff** — Datum und Uhrzeit.
- **Anzahl Zugriffe** — Counter.

So sehen Sie, ob der Auditor den Link überhaupt geöffnet hat.

## Lebensdauer

Standard-Lebensdauer ist **30 Tage** (System-Setting), kann beim Anlegen überschrieben werden. Maximum: 365 Tage.

## Wer einen Link anlegen darf

Nur **Admin und Owner**.

## Sicherheit

Der Token in der URL ist 64 Zeichen lang und kryptographisch zufällig — praktisch nicht erratbar. Trotzdem: behandeln Sie die URL wie ein Kennwort und teilen Sie sie nicht öffentlich.

> **Praxis-Hinweis**: Setzen Sie die Gültigkeit lieber auf **14 Tage** als auf 90. Wenn der Auditor länger braucht, verlängern Sie einfach — Sie haben die Kontrolle.
