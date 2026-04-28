## Wozu API & Webhooks

Externe Monitoring-Tools wie **Zabbix** oder **Prometheus Alertmanager** können automatisch Vorfälle in der Plattform anlegen — wenn Ihr Server-Monitoring kritische Werte meldet, eskaliert die Plattform sofort, ohne dass jemand zur Tastatur greifen muss.

Erreichbar über die Sidebar **„Einstellungen → API & Webhooks"** (Admin only).

## Schritt 1 — API-Token erstellen

Knopf **„Token erstellen"**. Pflichtangaben:

- **Bezeichnung** — z. B. „Zabbix-Produktion" oder „Prometheus Frankfurt".

Nach Klick auf **„Erstellen"** wird der Token **einmalig im Klartext angezeigt**. Kopieren Sie ihn sofort — danach ist er nur noch als Hash gespeichert und nicht mehr rekonstruierbar. Wenn Sie ihn vergessen, müssen Sie einen neuen erstellen.

## Schritt 2 — System-Mapping pflegen

Auf jedem System können Sie unter **„Monitoring-Hostnamen / Labels"** eine Liste von Bezeichnungen pflegen — z. B.:

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

## Verarbeitungs-Pfade

Pro Alarm einer dieser Status:

- **created_incident** — neuer Incident angelegt.
- **matched_existing** — Folge-Alert, an offenen Incident angehängt.
- **severity_below_threshold** — geloggt, keine Eskalation.
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
