## Was Notfallbetrieb / Ersatzprozesse sind

**Notfallbetrieb** ist die Art, wie das Unternehmen weiterarbeitet, wenn ein wichtiges System ausgefallen ist — *bevor* der Wiederanlauf den Normalzustand wiederherstellt. Ein **Ersatzprozess** ist eine konkrete Vorgehensweise dafür: meist mit **reduzierter Kapazität**, anderen Werkzeugen, anderen Verantwortlichen.

Klassische Beispiele:

- **„Papier statt ERP"** — Aufträge werden auf Lieferschein-Vordrucken erfasst, später nachgebucht.
- **„Telefonkette statt E-Mail"** — wenn das Mailsystem ausfällt, läuft die interne Abstimmung über eine zuvor festgelegte Telefonliste.
- **„Bargeld statt Kartenterminal"** — Ladenkasse wechselt auf reine Bargeld-Annahme.
- **„Mobiles Hotspot-Büro"** — Internet-Ausfall im Hauptsitz: Mitarbeiter arbeiten temporär über Mobilfunk-Hotspots oder im Home-Office.

Erreichbar über die Sidebar **„Notfallhandbuch → Notfallbetrieb"**.

## Abgrenzung zu „Notbetrieb / Ersatzprozess (Kurznotiz)" am System

In der **System-Erfassung** gibt es ein kurzes Feld *Notbetrieb / Ersatzprozess* — das ist eine **systemspezifische Kurznotiz** („Drucker manuell rebooten", „Cache-Server temporär abschalten"). Sie bleibt am System und ist gut für 1-zu-1-Hinweise.

Ein **Ersatzprozess** als eigene Entität ist anders: er kann

- **mehrere Systeme** gleichzeitig abdecken (z. B. „Papier-Lieferschein" ersetzt ERP + Lagerverwaltung + Etikettendruck),
- **organisatorisch** sein, ohne überhaupt an ein System gebunden zu sein („Telefonkette"),
- **Verantwortliche, Auslöser, Dauer und Übergabe** mitführen.

Die Faustregel: **Kurznotiz** für „dieses eine System macht nervt manchmal so", **Ersatzprozess** für „so läuft die Firma im Notfall weiter".

## Einen Ersatzprozess anlegen

Knopf **„Neuer Ersatzprozess"**. Pflichtangabe:

- **Titel** — z. B. „Papierbasierter Auftragsdurchlauf".

Empfohlen — das ist es, was den Eintrag im Ernstfall tragfähig macht:

- **Beschreibung** — Schritt für Schritt, was zu tun ist. Inkl. **Kapazität**: „30 % Durchsatz mit Papier statt ERP", „nur kritische Aufträge, keine Reklamationen".
- **Auslöser** — *wann* wird dieser Prozess aktiviert? z. B. „ERP länger als 2 Stunden nicht erreichbar". Klare Auslöser sparen Diskussion in der Krise.
- **Verantwortliche Rolle / Person** — wer entscheidet die Aktivierung und führt durch.
- **Priorität** — *Hoch / Mittel / Niedrig*. Bei zwei gleichzeitig möglichen Ersatzprozessen wird der mit höherer Priorität bevorzugt.
- **Max. Dauer (Stunden)** — wie lange darf dieser Ersatzbetrieb laufen, bevor eskaliert wird? Verhindert, dass aus einem Workaround ein Dauerzustand wird.
- **Übergabe an Wiederanlauf** — was muss nachgeholt werden, sobald das System wieder da ist? z. B. „Papierbelege ins ERP nachbuchen, Reklamationen aus Mappe abarbeiten". Ohne diese Notiz geht im Wiederanlauf Information verloren.
- **Betroffene Systeme** — welche Systeme deckt dieser Prozess ab? Mehrfachauswahl. Kann auch leer bleiben (rein organisatorischer Ablauf).

## Einsatz im Ernstfall

Im Krisen-Cockpit oder im Wiederanlauf-Plan sieht der Notfallbeauftragte:

1. **Welches System ist ausgefallen?** → System-Detailseite öffnen.
2. **Welche Ersatzprozesse sind dafür hinterlegt?** → Badges am System zeigen direkt, wer welchen Ersatzbetrieb führt.
3. **Aktivieren und protokollieren** — die verantwortliche Rolle setzt den Ersatzprozess auf, die max. Dauer wird notiert.
4. **Übergabe** — sobald das System wieder läuft, werden die *Übergabe-Notizen* abgearbeitet, bevor der Ersatzprozess deaktiviert wird.

## Querverweise

- **[Systeme](systeme)** — am System gibt es weiterhin die Kurznotiz `fallback_process`.
- **[Wiederanlauf](wiederanlauf)** — der Ersatzprozess überbrückt die Zeit *bis* der Wiederanlauf greift.
- **[Sofortmittel](sofortmittel)** — physische Voraussetzungen (Papierformulare, Bargeld, Notebook-Pool), die ein Ersatzprozess braucht.
- **[Rollen](rollen)** — die verantwortliche Rolle eines Ersatzprozesses sollte in den Pflichtrollen oder einer eigenen Rolle hinterlegt sein.
