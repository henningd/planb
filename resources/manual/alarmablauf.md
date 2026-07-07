## Auf einen Blick

Wenn ein Notfall ausgelöst wird, läuft in PlanB eine feste Kette automatisch ab — vom ersten Push bis zur SMS-Eskalation, falls niemand reagiert. Diese Seite zeigt die komplette Reihenfolge, damit Sie im Ernstfall genau wissen, was das System bereits für Sie erledigt hat — und was von Ihrem Team erwartet wird.

![Ablauf der automatischen Alarmierung — von der Auslösung über das Quittierungsfenster bis zur SMS-Eskalation und Entwarnung](/images/manual/alarmablauf.svg)

## Schritt für Schritt

### Minute 0 — der Notfall wird ausgelöst

Ein Notfall kann auf drei Wegen starten: von einem **Mitarbeiter in der Notfall-App**, von der **Leitung im Web-Cockpit** — oder **automatisch durch das IT-Monitoring**, wenn ein kritischer Alert eingeht und dem betroffenen System ein Notfall-Szenario zugeordnet ist. In allen drei Fällen passiert ab diesem Moment dasselbe, ohne dass jemand etwas tun muss:

- **Push an alle registrierten Geräte** — als zeitkritische Mitteilung, die auf dem iPhone Fokus-Modi wie „Nicht stören" durchbricht; auf Android klingelt sie in Alarm-Lautstärke und weckt das gesperrte Gerät als Vollbild-Alarm.
- **Karte in Slack / Microsoft Teams** — in den Krisen-Kanal, sofern eine Webhook-URL hinterlegt ist (siehe Kapitel „API &amp; Webhooks").
- **Eintrag im Krisenprotokoll** — revisionssicher mit Zeitstempel und Auslöser. Jeder weitere Schritt des Ablaufs wird dort mitprotokolliert.
- **„Aktiver Notfall" überall** — die rote Karte erscheint in beiden Apps und im Web-Cockpit; die Szenario-Checkliste mit Schritten und Zuständigkeiten ist geöffnet.

**Wichtig:** In diesem Moment werden noch **keine SMS** verschickt. SMS sind die Eskalationsstufe (siehe unten) — oder werden bewusst manuell über die Kommunikations-Vorlagen versendet.

### Das Quittierungsfenster — Standard 10 Minuten

Jetzt ist das Team am Zug: Jede und jeder kann den Alarm in der App mit einem Tipp quittieren — **„Gesehen"** oder **„Ich übernehme"**. Der Stand ist für alle live sichtbar: Wer hat den Alarm wahrgenommen, wer hat die Führung übernommen?

Die Länge des Fensters legen Sie in den **Einstellungen** fest („Alarm-Eskalation", Standard **10 Minuten**, `0` schaltet die Eskalation ab).

### Weg A: Jemand übernimmt

Sobald mindestens eine Person quittiert hat, ist keine Eskalation mehr nötig. Das Team arbeitet die Checkliste ab und hakt Schritte ab — das funktioniert **auch ohne Internet**; die App überträgt den Fortschritt automatisch nach, sobald wieder Verbindung besteht. Alle Beteiligten sehen denselben Stand.

### Weg B: Niemand quittiert — die Eskalation

Läuft das Fenster ab, ohne dass irgendjemand quittiert hat, eskaliert PlanB **automatisch und genau einmal**:

1. **Dringlicher, erneuter Push** an alle Geräte („Noch niemand hat den Notfall übernommen").
2. **SMS an den gesamten Krisenstab** — alle Pflichtrollen **inklusive Vertretungen**, an die Mobilnummern aus den Benutzerprofilen. SMS erreichen jedes Handy, ohne App und ohne Internet — die letzte Meile, die auch beim IT-Totalausfall funktioniert. Voraussetzung: Das SMS-Gateway ist konfiguriert (siehe unten).
3. **Hinweis in Slack/Teams** und ein **Eintrag im Krisenprotokoll**.
4. Im Ablauf-Detail der App erscheint ein **Eskalations-Banner** mit Uhrzeit.

### Entwarnung

Wird der Notfall **beendet** oder als Fehlalarm **abgebrochen**, informiert PlanB erneut alle: Push an die Geräte und eine Karte in den Chat-Kanal. Die „Aktiver Notfall"-Karte verschwindet überall, und der vollständige Verlauf — von der Auslösung über jede Quittierung bis zum Abschluss — steht im Krisenprotokoll. Bei Übungen entsteht daraus zusätzlich der **Übungsbericht** (eigenes Kapitel).

## Die Szenarien im Vergleich

| Szenario | Push | Vollbild (Android) | Slack/Teams | Eskalation + SMS | Dashboard-Banner |
| --- | --- | --- | --- | --- | --- |
| **Ernstfall** (App/Web ausgelöst) | ja, zeitkritisch | ja | ja | ja, nach Frist ohne Quittierung | ja |
| **Automatisch** (Monitoring-Alert) | ja, zeitkritisch | ja | ja | ja, nach Frist ohne Quittierung | ja |
| **Übung** (Testalarm) | ja, mit ÜBUNG-Kennzeichnung | nein | ja, mit ÜBUNG-Präfix | **nein — Übungen eskalieren nie** | nein |
| **Wartungsfenster** (Monitoring pausiert) | nein | nein | nein | nein | nein — Alert wird nur protokolliert |

Und zur Einordnung: **Manuell versendete SMS** über die Kommunikations-Vorlagen (an Mitarbeiter mit Mobilnummer) sind unabhängig von dieser Kette — sie ersetzen nicht die Eskalation, sondern ergänzen sie, z. B. für die Erstinformation an die Belegschaft.

## Voraussetzungen-Checkliste

Damit die komplette Kette im Ernstfall trägt, einmal prüfen:

- **App verteilt:** Krisenstab und Schlüsselpersonen haben die Notfall-App gekoppelt (am schnellsten über den Massen-Rollout, siehe Kapitel „Notfall-App").
- **Mobilnummern gepflegt:** in den Benutzerprofilen des Krisenstabs — dorthin gehen die Eskalations-SMS.
- **SMS-Gateway aktiv:** serverseitig ist der seven.io-API-Key hinterlegt und das Konto hat Guthaben. Ohne Gateway warnt der SMS-Dialog deutlich und die Eskalation läuft nur mit Push.
- **Eskalationsfrist passend:** Einstellungen → „Alarm-Eskalation" (Standard 10 Minuten).
- **Chat-Kanal verbunden:** Slack-/Teams-Webhook-URL hinterlegt, falls gewünscht.
- **Einmal geprobt:** Lösen Sie eine **Übung** aus — sie durchläuft dieselbe Kette (ohne Eskalation und ohne Vollbild-Alarm) und liefert danach den Übungsbericht als Nachweis.
