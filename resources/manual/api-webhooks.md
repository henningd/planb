## Wozu API & Webhooks

Externe Monitoring-Tools wie **Zabbix** oder **Prometheus Alertmanager** können automatisch Vorfälle in der Plattform anlegen — wenn Ihr Server-Monitoring kritische Werte meldet, eskaliert die Plattform sofort, ohne dass jemand zur Tastatur greifen muss.

Erreichbar über die Sidebar **„Einstellungen → API & Webhooks"** (Admin only).

## Schritt 1 — API-Token erstellen

Knopf **„Token erstellen"**. Pflichtangaben:

- **Bezeichnung** — z. B. „Zabbix-Produktion" oder „Prometheus Frankfurt".

Der Token erhält automatisch den Berechtigungsumfang (Scope) **`monitoring.write`** — er darf also ausschließlich Monitoring-Alarme einliefern und sonst nichts. Selbst wenn ein Token in falsche Hände gerät, kann damit niemand Daten lesen oder ändern.

Nach Klick auf **„Erstellen"** wird der Token **einmalig im Klartext angezeigt**. Kopieren Sie ihn sofort — danach ist er nur noch als Hash gespeichert und nicht mehr rekonstruierbar. Wenn Sie ihn vergessen, müssen Sie einen neuen erstellen.

## Schritt 2 — System-Mapping pflegen

Auf jedem System können Sie unter **„Monitoring-Hostnamen / Labels"** (die Monitoring-Keys des Systems) eine Liste von Bezeichnungen pflegen — z. B.:

```
srv-prod-01
fileserver.local
WAWI
```

Wenn ein Alarm einen dieser Namen in `host` oder `subject` trägt, wird er automatisch dem System zugeordnet.

## Schritt 3 — Tools konfigurieren

### Zabbix

In Zabbix unter **Configuration → Actions → Webhook**:

- **URL**: `https://app.example.com/api/v1/webhooks/zabbix`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer planb_…`
- **Body** (JSON): `{"host":"{HOST.NAME}","event_id":"{EVENT.ID}","trigger_id":"{TRIGGER.ID}","severity":"{TRIGGER.SEVERITY}","status":"{EVENT.VALUE}","subject":"{TRIGGER.NAME}"}`

### Prometheus Alertmanager

In `alertmanager.yml`:

```yaml
receivers:
  - name: planb
    webhook_configs:
      - url: https://app.example.com/api/v1/webhooks/prometheus
        http_config:
          authorization:
            type: Bearer
            credentials: planb_…
```

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
- Standard ist **keine automatische Alarmierung** — Sie entscheiden pro System, ob ein Monitoring-Alert nur einen Vorfall dokumentiert oder gleich die Mannschaft weckt.

Sinnvoll für die wirklich kritischen Systeme (z. B. zentraler Server, Fachverfahren), bei denen im Ernstfall keine Minute verloren gehen soll.

## Wartungsfenster: Monitoring-Alarme pausieren

Geplante Wartung — Server-Update am Samstag, Umzug des Racks — löst sonst genau die Fehlalarme aus, die niemand um 3 Uhr nachts sehen will. Dafür gibt es pro System ein **Wartungsfenster**:

1. Das System öffnen und im Monitoring-Bereich das Feld **„Monitoring-Alarme pausiert bis"** auf das Ende der Wartung setzen (Datum und Uhrzeit).
2. Bis zu diesem Zeitpunkt werden eingehende Alerts für dieses System **nur protokolliert** — es wird **kein Vorfall angelegt und kein automatischer Alarm gestartet**.
3. **Entwarnungen** (resolved) werden weiterhin normal verarbeitet — ein offener Vorfall bekommt seine „System wieder online"-Notiz also trotzdem.

Solange das Fenster läuft, zeigt die System-Seite einen gut sichtbaren Hinweis („Aktuell pausiert bis …"). Nach Ablauf ist das Monitoring automatisch wieder scharf — Sie müssen nichts zurückstellen. Ein leeres Feld bedeutet: Monitoring aktiv.

## Alarm-Posts in Slack / Microsoft Teams

Wenn Ihr Team ohnehin in Slack oder Teams lebt, soll ein Notfall auch dort sichtbar sein. Sind in den System-Einstellungen die **Slack-Webhook-URL** und/oder die **Microsoft-Teams-Webhook-URL** hinterlegt (dieselben, die auch die [Kommunikations-Vorlagen](/handbuch/kommunikations-vorlagen) nutzen), postet die Plattform drei Ereignisse **automatisch als Karte** in den Kanal:

- **Notfall gemeldet** — beim Start eines Alarms.
- **Eskalation** — wenn ein echter Alarm nach Ablauf der Eskalationsfrist von niemandem quittiert wurde.
- **Entwarnung** — wenn der Lauf beendet oder abgebrochen wird.

**Übungen** werden dabei deutlich mit dem Präfix **„ÜBUNG:"** gekennzeichnet — niemand im Kanal muss rätseln, ob es ernst ist.

Die Funktion ist standardmäßig aktiv und lässt sich über die Einstellung **„Alarm-Posts in Slack/Teams"** abschalten. Ohne hinterlegte Webhook-URL wird schlicht nichts gesendet.

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
