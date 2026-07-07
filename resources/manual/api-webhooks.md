## Wozu API & Webhooks

Externe Monitoring-Tools wie **Zabbix** oder **Prometheus Alertmanager** können automatisch Vorfälle in der Plattform anlegen — wenn Ihr Server-Monitoring kritische Werte meldet, eskaliert die Plattform sofort, ohne dass jemand zur Tastatur greifen muss.

Erreichbar über die Sidebar **„Einstellungen → API & Webhooks"** (Admin only).

## Schritt 1 — API-Token erstellen

Knopf **„Token erstellen"**. Pflichtangaben:

- **Bezeichnung** — z. B. „Zabbix-Produktion" oder „Prometheus Frankfurt".

Der Token erhält automatisch den Berechtigungsumfang (Scope) **`monitoring.write`** — er darf also ausschließlich Monitoring-Alarme einliefern und sonst nichts. Selbst wenn ein Token in falsche Hände gerät, kann damit niemand Daten lesen oder ändern.

Nach Klick auf **„Erstellen"** wird der Token **einmalig im Klartext angezeigt**. Kopieren Sie ihn sofort — danach ist er nur noch als Hash gespeichert und nicht mehr rekonstruierbar. Wenn Sie ihn vergessen, müssen Sie einen neuen erstellen.

**Wohin mit dem Token?** Er wird bei jedem Webhook-Aufruf als Bearer-Token mitgeschickt. Beim **Prometheus Alertmanager** gehört er in die `alertmanager.yml` unter `http_config`:

```yaml
webhook_configs:
  - url: https://<ihre-instanz>/api/v1/webhooks/prometheus
    send_resolved: true
    http_config:
      authorization:
        type: Bearer
        credentials: planb_…     # ← Ihr Token
```

Bei **Zabbix** als HTTP-Header der Webhook-Action: `Authorization: Bearer planb_…` — die vollständigen Konfigurationen für beide Tools stehen in Schritt 3.

**Tipp:** Legen Sie **ein Token pro Quelle** an (eines für Prometheus, eines für Zabbix). Dann können Sie später gezielt einen Zugang widerrufen, ohne die andere Anbindung zu kappen.

## Schritt 2 — System-Mapping pflegen

Auf jedem System können Sie unter **„Monitoring-Hostnamen / Labels"** (die Monitoring-Keys des Systems) eine Liste von Bezeichnungen pflegen — z. B.:

```
srv-prod-01
fileserver.local
WAWI
```

Wenn ein Alarm einen dieser Namen in `host` oder `subject` trägt, wird er automatisch dem System zugeordnet.

**Alternative: eindeutige Zuordnung per System-ID.** Auf der System-Detailseite finden Sie im Monitoring-Bereich die **„System-ID für die Monitoring-Anbindung"** zum Kopieren. Geben Sie diese ID im Alarm mit — als Prometheus-Label **`planb_system_id`** bzw. als Zabbix-Feld **`system_id`** — dann wird der Alarm **direkt und eindeutig** diesem System zugeordnet. Die ID hat Vorrang vor dem Namens-Matching und ist die sauberste Wahl, wenn Hostnamen mehrdeutig sind oder mehrere Systeme auf derselben Maschine laufen. Eine unbekannte oder fremde ID wird ignoriert; dann greift wieder das normale Matching.

## Schritt 3 — Tools konfigurieren

### Zabbix

In Zabbix unter **Configuration → Actions → Webhook**:

- **URL**: `https://app.example.com/api/v1/webhooks/zabbix`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer planb_…`
- **Body** (JSON): `{"host":"{HOST.NAME}","event_id":"{EVENT.ID}","trigger_id":"{TRIGGER.ID}","severity":"{TRIGGER.SEVERITY}","status":"{EVENT.VALUE}","subject":"{TRIGGER.NAME}"}`
- **Optional** für die eindeutige Zuordnung: zusätzlich `"system_id":"<System-ID aus der Plattform>"` in den Body aufnehmen (siehe Schritt 2).

### Prometheus Alertmanager — Schritt für Schritt

Zur Einordnung: **Prometheus** sammelt die Messwerte Ihrer Server und wertet darauf Alarmregeln aus; der **Alertmanager** bündelt die Alarme und stellt sie zu — in unserem Fall per Webhook an die Plattform. Konfiguriert wird an zwei Stellen:

**a) Alarmregeln in Prometheus mit Schweregrad versehen.** Ein Vorfall entsteht nur bei den Severity-Werten **`critical`** oder **`page`** — alles darunter (z. B. `warning`) wird nur protokolliert. Beispielregel:

```yaml
# prometheus: rules.yml
groups:
  - name: planb
    rules:
      - alert: ServerNichtErreichbar
        expr: probe_success{instance="srv-prod-01"} == 0
        for: 5m
        labels:
          severity: critical
          planb_system_id: "a1b2c3d4-…"   # optional: eindeutige Zuordnung (Schritt 2)
        annotations:
          summary: "srv-prod-01 ist seit 5 Minuten nicht erreichbar"
```

Wichtig: Der Wert von `instance` bzw. der Text in `summary` muss zu den **Monitoring-Keys** des Systems passen (Schritt 2) — darüber ordnet die Plattform den Alarm zu. Oder Sie geben mit dem Label **`planb_system_id`** die System-ID direkt mit — dann ist die Zuordnung eindeutig und unabhängig von Hostnamen.

**b) Webhook-Receiver im Alertmanager eintragen** (`alertmanager.yml`), mit dem Token aus Schritt 1:

```yaml
route:
  receiver: planb

receivers:
  - name: planb
    webhook_configs:
      - url: https://app.example.com/api/v1/webhooks/prometheus
        send_resolved: true            # Entwarnungen mitschicken
        http_config:
          authorization:
            type: Bearer
            credentials: planb_…       # Token mit Scope monitoring.write
```

`send_resolved: true` sorgt dafür, dass auch die Entwarnung ankommt — der offene Vorfall bekommt dann automatisch seine „RESOLVED"-Notiz. Wenn Sie nur kritische Alarme an die Plattform schicken wollen, ergänzen Sie eine Route mit `matchers: ['severity=~"critical|page"']` — nötig ist das nicht, die Plattform filtert selbst.

**c) Verbindung testen**, ohne einen echten Ausfall zu provozieren — direkt per `curl`:

```bash
curl -X POST https://app.example.com/api/v1/webhooks/prometheus \
  -H "Authorization: Bearer planb_…" \
  -H "Content-Type: application/json" \
  -d '{"status":"firing","alerts":[{"status":"firing",
       "labels":{"alertname":"Test","severity":"critical",
                 "instance":"srv-prod-01"},
       "annotations":{"summary":"Testalarm aus dem Monitoring"}}]}'
```

Die Antwort zeigt pro Alarm, wie die Plattform ihn behandelt hat (siehe „Verarbeitungs-Pfade" unten) — z. B. `created_incident`, wenn alles passt, oder `no_system_match`, wenn noch ein Monitoring-Key fehlt. Anschließend sehen Sie den Eintrag in der Alarm-Liste auf der API-Seite und den Vorfall in der Vorfalls-Dokumentation. Den Test-Vorfall können Sie danach einfach schließen.

## Was passiert beim Eingang

Pro Alarm prüft die Plattform:

1. **Authentifizierung**: ist der Token gültig und nicht widerrufen?
2. **Idempotenz**: ist dieser Alarm schon mal eingegangen (über die Idempotency-Key-Logik)? Wenn ja: ignorieren.
3. **System-Mapping**: kann der Host einem System zugeordnet werden?
4. **Severity-Filter**: ist die Severity high, disaster, critical oder page?

Wenn alles passt: ein **IncidentReport** wird angelegt und die Eskalations-Kette greift.

## Optional: automatische Alarmierung

Zusätzlich zur automatischen Vorfallseröffnung können Sie pro System ein **Notfall-Szenario hinterlegen** (auf der System-Detailseite unter **„Automatische Alarmierung bei kritischem Monitoring-Alert"**):

- Eröffnet ein kritischer Alert für dieses System einen neuen Vorfall, startet das gewählte Szenario **automatisch als echter Alarm** — inklusive Push-Benachrichtigung an alle gekoppelten Geräte der Notfall-App, Quittierung („Gesehen" / „Ich übernehme") und Eskalation, falls niemand reagiert.
- Der Auslöser bleibt dabei sichtbar: Krisen-Cockpit, Benachrichtigungs-Feed und Slack/Teams-Karte zeigen **„Automatisch · IT-Monitoring"** samt dem auslösenden Host — niemand muss rätseln, wer den Alarm gestartet hat.
- Standard ist **keine automatische Alarmierung** — Sie entscheiden pro System, ob ein Monitoring-Alert nur einen Vorfall dokumentiert oder gleich die Mannschaft weckt.

Sinnvoll für die wirklich kritischen Systeme (z. B. zentraler Server, Fachverfahren), bei denen im Ernstfall keine Minute verloren gehen soll.

## Wartungsfenster: Monitoring-Alarme pausieren

Geplante Wartung — Server-Update am Samstag, Umzug des Racks — löst sonst genau die Fehlalarme aus, die niemand um 3 Uhr nachts sehen will. Dafür gibt es pro System ein **Wartungsfenster**:

1. Das System öffnen und im Monitoring-Bereich das Feld **„Monitoring-Alarme pausiert bis"** auf das Ende der Wartung setzen (Datum und Uhrzeit).
2. Bis zu diesem Zeitpunkt werden eingehende Alerts für dieses System **nur protokolliert** — es wird **kein Vorfall angelegt und kein automatischer Alarm gestartet**.
3. **Entwarnungen** (resolved) werden weiterhin normal verarbeitet — ein offener Vorfall bekommt seine „System wieder online"-Notiz also trotzdem.

Solange das Fenster läuft, zeigt die System-Seite einen gut sichtbaren Hinweis („Aktuell pausiert bis …"). Nach Ablauf ist das Monitoring automatisch wieder scharf — Sie müssen nichts zurückstellen. Ein leeres Feld bedeutet: Monitoring aktiv.

## Alarm-Posts in Slack / Microsoft Teams

Wenn Ihr Team ohnehin in Slack oder Teams lebt, soll ein Notfall auch dort sichtbar sein. Sind die Webhook-URLs hinterlegt (dieselben, die auch die [Kommunikations-Vorlagen](/handbuch/kommunikations-vorlagen) nutzen), postet die Plattform drei Ereignisse **automatisch als Karte** in den Kanal:

- **Notfall gemeldet** — beim Start eines Alarms.
- **Eskalation** — wenn ein echter Alarm nach Ablauf der Eskalationsfrist von niemandem quittiert wurde.
- **Entwarnung** — wenn der Lauf beendet oder abgebrochen wird.

**Übungen** werden dabei deutlich mit dem Präfix **„ÜBUNG:"** gekennzeichnet — niemand im Kanal muss rätseln, ob es ernst ist.

Die Funktion ist standardmäßig aktiv und lässt sich über die Einstellung **„Alarm-Posts in Slack/Teams"** abschalten. Ohne hinterlegte Webhook-URL wird schlicht nichts gesendet.

### Slack anbinden — Schritt für Schritt

1. Öffnen Sie [api.slack.com/apps](https://api.slack.com/apps) → **„Create New App" → „From scratch"**, Name z. B. „PlanB", Ihren Workspace wählen.
2. Links im Menü **„Incoming Webhooks"** → Schalter aktivieren → **„Add New Webhook to Workspace"** → den Ziel-Kanal wählen (z. B. `#krisenstab`).
3. Die erzeugte URL kopieren (beginnt mit `https://hooks.slack.com/services/…`).
4. In der Plattform unter **„Systemeinstellungen"** ins Feld **„Slack-Webhook-URL"** einfügen und speichern.

### Microsoft Teams anbinden — Schritt für Schritt

1. Im Ziel-Kanal (z. B. „Krisenstab") auf **„⋯" → „Konnektoren"** → **„Incoming Webhook"** einrichten, Namen vergeben (z. B. „PlanB"), die URL kopieren. In neueren Teams-Versionen läuft das über **„⋯" → „Workflows"** mit der Vorlage „Bei Empfang einer Webhookanforderung in einem Kanal posten" — kommt darüber später keine Test-Karte an, nutzen Sie den klassischen Incoming-Webhook-Konnektor.
2. In der Plattform unter **„Systemeinstellungen"** ins Feld **„Microsoft-Teams-Webhook-URL"** einfügen und speichern.

### Testen und gut zu wissen

- **Schnellster Test:** eine [Kommunikations-Vorlage](/handbuch/kommunikations-vorlagen) mit Kanal „Slack" oder „Teams" anlegen und senden — Fehler (falsche URL, gelöschter Kanal) werden direkt angezeigt. Oder einen **Übungsalarm** auslösen: Die Karte erscheint mit „ÜBUNG:"-Präfix im Kanal.
- **Ein Kanal pro Dienst:** Es gibt je eine URL für Slack und eine für Teams — sinnvollerweise der Krisen- oder Info-Kanal, den alle abonniert haben. Sind beide hinterlegt, wird in beide gepostet.
- **Nur Senden, kein Rückkanal:** Antworten im Kanal landen nicht in der Plattform; quittiert wird in der Notfall-App.
- **Die URL ist ein Geheimnis:** Wer sie kennt, kann in den Kanal posten. Nicht weitergeben — und bei Verdacht in Slack/Teams einfach neu erzeugen und in den Systemeinstellungen austauschen.

## Verarbeitungs-Pfade

Pro Alarm einer dieser Status:

- **created_incident** — neuer Incident angelegt.
- **matched_existing** — Folge-Alert, an offenen Incident angehängt.
- **severity_below_threshold** — geloggt, keine Eskalation.
- **muted** — Wartungsfenster aktiv, Alert nur protokolliert (kein Vorfall, kein Alarm).
- **no_system_match** — geloggt, kein passendes System gefunden.
- **ignored** — z. B. resolved-Status ohne vorherigen Incident.

## Liste der eingegangenen Alarme

Auf der API-Seite unten die letzten 20 Alarme — Zeit, Quelle, Host, Status, Verarbeitung. Klick auf ein Alarm-Symbol führt zum Incident (falls verknüpft).

## Token widerrufen

Pro Token ein **„Widerrufen"-Knopf**. Sofortiger Effekt — der Token wird vom System abgelehnt. Sinnvoll, wenn ein Token kompromittiert ist oder ein Mitarbeiter geht.

## Wer das tun darf

Nur **Admin und Owner**.

## Feature-Schalter

API-Modul kann pro Plattform abgeschaltet sein (`FEATURE_MONITORING_API_ENABLED=false`). Dann fehlen die Routen und der Sidebar-Eintrag.

> **Praxis-Hinweis**: Setzen Sie die **Severity-Schwelle** in Zabbix bewusst hoch. Es ist besser, einen einzelnen kritischen Alarm sauber zu behandeln, als von 50 „warning"-Alarmen erschlagen zu werden.
