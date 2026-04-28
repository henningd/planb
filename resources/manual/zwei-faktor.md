## Was 2FA ist

Zwei-Faktor-Authentifizierung (2FA) heißt: Sie melden sich nicht nur mit **Passwort**, sondern auch mit einem **6-stelligen Code aus einer Authenticator-App** an. Selbst wenn jemand Ihr Passwort kennt, kommt er nicht ohne das zweite Gerät rein.

## Aktivierung

1. Oben rechts auf das Profil-Icon → **„Profil"**.
2. Bereich **„Zwei-Faktor-Authentifizierung"** → **„2FA aktivieren"**.
3. Eine Authenticator-App auf dem Smartphone installieren — z. B.:
   - **Google Authenticator** (kostenlos, einfach).
   - **Microsoft Authenticator** (Microsoft-Ökosystem).
   - **Authy** (Cloud-Backup).
   - **1Password** / **Bitwarden** (Passwort-Manager mit 2FA-Funktion).
4. Den **QR-Code** mit der App scannen.
5. Den 6-stelligen Code aus der App ins Bestätigungs-Feld eintragen.
6. Die zehn **Recovery-Codes** notieren oder im Passwort-Manager ablegen — sie sind die einzige Möglichkeit, sich anzumelden, wenn das Smartphone weg ist.

## Anmelden mit 2FA

Nach Eingabe von E-Mail + Passwort erscheint ein zweites Feld für den **6-stelligen Code**. Authenticator-App öffnen, Code abtippen oder kopieren, fertig.

## Recovery-Codes

Wenn das Smartphone verloren geht oder die App nicht mehr funktioniert: einer der zehn Recovery-Codes hilft. Auf der Login-Seite gibt es einen Link **„Mit Recovery-Code anmelden"**. Pro Code nur ein Login möglich.

Wenn alle Codes verbraucht sind: neue erzeugen (Profil → 2FA → „Neue Codes").

## 2FA deaktivieren

Im Profil-Bereich kann jeder Benutzer 2FA selbst wieder deaktivieren — nicht empfohlen, aber möglich. Sie müssen dafür das Passwort erneut eingeben.

## 2FA-Pflicht für Admins

Pro Mandant kann eingestellt werden, dass **alle Admin-Konten 2FA aktiv haben müssen** (System-Settings → 2FA-Pflicht für Team-Admins). Wenn das aktiv ist:

- Admin meldet sich an.
- Wenn 2FA fehlt: automatischer Redirect auf die 2FA-Setup-Seite.
- Bis 2FA eingerichtet ist, sind alle anderen Bereiche gesperrt.

Das ist die empfohlene Einstellung für regulierte Sektoren.

## Wer das selbst tun darf

Jeder Benutzer pflegt sein eigenes 2FA. Admins können sehen, **wer 2FA aktiviert hat** (in der Benutzer-Liste), aber niemand kann es für jemand anderen einrichten.

> **Praxis-Hinweis**: Authy erlaubt Cloud-Backup der Codes — wenn Sie das Smartphone wechseln, sind Ihre 2FA-Einstellungen gleich wieder da. Bei Google Authenticator müssen Sie alle 2FA-Konten neu einrichten.
