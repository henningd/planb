## Konto anlegen

Auf der Startseite der Plattform gibt es oben rechts den Button **„Kostenlos starten"**. Ein Klick führt zum Registrierungs-Formular. Sie brauchen:

- **Name** — Vor- und Nachname, wird in der Sidebar und im Audit-Log angezeigt.
- **E-Mail-Adresse** — wird auch zum Login verwendet, sollte langfristig erreichbar sein.
- **Passwort** — mindestens 8 Zeichen, am besten ein Passwort-Manager-generiertes.
- **Passwort-Wiederholung** — Tippfehler-Schutz.

Nach Klick auf **„Konto erstellen"** wird automatisch:

- ein **persönliches Team** für Sie angelegt (das ist die organisatorische Klammer);
- eine **Bestätigungs-E-Mail** verschickt — Link anklicken, sonst sind manche Funktionen gesperrt;
- der **Onboarding-Wizard** auf dem Dashboard sichtbar gemacht.

## Anmelden

Auf der Startseite oben rechts der Link **„Anmelden"** oder direkt über `/login`. Mit E-Mail + Passwort kommen Sie ins Dashboard. Wenn Sie Zwei-Faktor-Authentifizierung aktiviert haben (siehe unten), erscheint nach dem Passwort eine zweite Eingabe für den 6-stelligen Code aus Ihrer Authenticator-App.

## Passwort vergessen

Auf der Anmelde-Seite gibt es den Link **„Passwort vergessen?"** Sie geben Ihre E-Mail-Adresse ein, bekommen einen Link zugeschickt, klicken ihn an, vergeben ein neues Passwort. Der Link ist 60 Minuten gültig und kann nur einmal verwendet werden.

> **Wenn Sie keine E-Mail bekommen**: Spam-Ordner prüfen. Falls die E-Mail-Konfiguration der Plattform noch nicht steht, landet die Mail im Server-Log statt in Ihrem Posteingang — dann den Plattform-Betreiber kontaktieren.

## Zwei-Faktor-Authentifizierung (2FA)

Stark empfohlen für alle Admin-Konten. Aktivierung:

1. Oben rechts auf das Profil-Icon klicken → **„Profil"**.
2. Bereich **„Zwei-Faktor-Authentifizierung"** öffnen.
3. **„2FA aktivieren"** drücken — die Plattform zeigt einen QR-Code.
4. **Authenticator-App** (Google Authenticator, Authy, 1Password, …) öffnen, QR scannen.
5. Der 6-stellige Code aus der App in das Feld eintragen — bestätigt.
6. **Recovery-Codes notieren**: zehn Einmal-Codes, falls Sie das Smartphone verlieren. Im Tresor oder Passwort-Manager ablegen.

Die Plattform kann **2FA für Admins erzwingen** (Einstellung pro Mandant). Wenn das aktiv ist und ein Admin sich ohne 2FA anmeldet, wird er sofort auf die 2FA-Seite umgeleitet, bis er es eingerichtet hat.

## Teams und Mandanten

Jeder Benutzer gehört zu mindestens einem **Team**. Ein Team ist nicht das Unternehmen, sondern die App-Klammer um eine Firma — pro Team genau eine Firma (Mandant). Wenn Sie für mehrere Firmen arbeiten (z. B. als Berater oder IT-Dienstleister), bekommen Sie für jede Firma ein eigenes Team und schalten oben in der Sidebar zwischen ihnen um.

Innerhalb eines Teams gibt es drei Rollen:

- **Owner** — der Anlegende, hat alle Rechte und kann das Team nicht verlassen.
- **Admin** — sieht und ändert alles, kann andere einladen.
- **Member** — sieht und ändert die meisten Bereiche, aber nicht Versicherungen, Audit-Log, Freigabelinks und Branding.

## Konto löschen

Im Profil unten gibt es den Button **„Konto löschen"**. Achtung: das ist endgültig. Wenn Sie der einzige Owner Ihres Teams sind, müssen Sie zuerst entweder einen anderen Owner ernennen oder das Team mit-löschen. Vorher unbedingt das Mandanten-Archiv exportieren (System-Settings → Vollständiges Archiv).

## Eigene Daten herunterladen / Account löschen

Unter **Einstellungen → Daten & Datenschutz** finden Sie die zwei DSGVO-Selbstbedienungs-Funktionen:

- **Daten exportieren (Art. 15 DSGVO)** — der Button **„JSON herunterladen"** liefert ein strukturiertes JSON-Dokument mit Ihren Stammdaten (Name, E-Mail, Anmelde-Zeitstempel, 2FA-Status), allen Mandanten-Mitgliedschaften (Mandant, Rolle, Beitritts-Datum) und allen Audit-Log-Einträgen, die Sie persönlich verursacht haben. Die Datei heißt `planb-account-{user-id}-{datum}.json` und kann z. B. an eine Aufsichtsbehörde oder zur eigenen Kontrolle weitergegeben werden.
- **Löschung beantragen (Art. 17 DSGVO)** — der Button **„Löschung beantragen"** öffnet einen Bestätigungs-Dialog mit optionaler Begründung. Die Anfrage wird gespeichert; ein Administrator prüft und bearbeitet sie **innerhalb von 30 Tagen** manuell. Solange eine Anfrage offen ist, kann keine zweite gestellt werden — die Karte zeigt stattdessen einen Hinweis mit dem Antrags-Datum. Eine sofortige technische Löschung erfolgt aus Aufbewahrungs- und Mandanten-Schutz-Gründen bewusst nicht über diesen Self-Service.

> **Hinweis**: Wenn Sie sofort und endgültig Ihren Account entfernen wollen, ohne auf einen Admin zu warten, nutzen Sie weiterhin den klassischen Button **„Konto löschen"** im Profil (oben). Der DSGVO-Antrag ist die schonende Variante mit Prüfung durch den Plattform-Betreiber.
