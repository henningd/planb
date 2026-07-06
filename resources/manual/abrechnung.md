# Abrechnung

Die Abrechnung läuft pro **Mandant** (Team), nicht pro Person. Wer mehrere
Mandanten betreut, hat mehrere Abos. Bezahlt wird über **Stripe**.

## Aufruf

**Einstellungen → Abrechnung**

Sichtbar ist diese Seite nur, wenn der Plattform-Betreiber das Modul
aktiviert hat (`FEATURE_BILLING_ENABLED=true` in der `.env`).

## Test-Zeitraum

Wer sich auf der Preise-Seite für **Advanced** entscheidet, bekommt
**14 Tage kostenlosen Test ohne Kreditkarte**. Der volle Funktionsumfang
ist in dieser Zeit freigeschaltet. Nach Ablauf des Tests passiert eines
von beidem:

- **Tarif gewählt:** Sie geben in der Stripe-Kasse Ihre Zahlungsdaten ein,
  das Abo läuft regulär weiter.
- **Kein Tarif gewählt:** Der Mandant wird in den **Read-Only-Modus**
  versetzt — Daten und Exporte bleiben verfügbar, neue Eingaben sind
  gesperrt. Sobald Sie einen Tarif buchen, wird die Sperre aufgehoben.

Während des Tests gibt es **keine Mahnungen** und keine automatischen
Buchungen.

## Tarif buchen oder wechseln

In der Übersicht sehen Sie alle Tarife (Starter, Advanced, Enterprise) als
Karten mit Monats- und Jahres-Toggle. Der **Jahresplan** entspricht zehn
Monatspreisen — also rund 17 % Rabatt gegenüber der monatlichen Buchung.

- **Buchen** öffnet die Stripe-Hosted-Checkout-Seite. Dort geben Sie
  Zahlungsdaten ein und schließen den Kauf ab. Nach Erfolg landen Sie
  zurück auf der Abrechnungs-Seite.
- **Wechseln** zwischen aktiven Tarifen läuft *direkt*, ohne erneute
  Checkout-Seite. Stripe rechnet anteilig den Rest des laufenden Zeitraums
  ab und stellt den neuen Tarif anteilig in Rechnung.
- **Enterprise** hat keinen Self-Service-Checkout — Sie kontaktieren uns
  per Demo-Anfrage und bekommen ein Angebot.
- **Kommunal** ist der Tarif für Städte, Gemeinden und Eigenbetriebe:
  Funktionsumfang wie Advanced, inklusive **Notfall-App** und
  Verwaltungs-Vorlage. Die Buchung läuft **vergabefreundlich** über
  Angebot und Rechnung — ohne Kreditkarte und ohne
  Self-Service-Checkout. Fragen Sie ein individuelles Angebot an.

## Zusatzleistungen

Unter dem Tarif-Block gibt es **Zusatzleistungen**, die Sie unabhängig vom
laufenden Plan dazubuchen können:

| Posten | Modus | Beschreibung |
|---|---|---|
| **Onboarding-Workshop (2 h)** | Einmalzahlung | Strukturierte Einrichtung mit einem unserer Berater. |
| **Coaching-Stunde (60 min)** | Einmalzahlung, Anzahl wählbar | Beratung nach Bedarf — Tabletop-Übung, Score-Review, NIS2-Sparring. |
| **Coaching-Retainer (4 h/Monat)** | **Abo**, monatlich kündbar | Günstiger als Einzelstunden, geeignet für laufende Begleitung. |
| **Zusatz-User** | Einmalzahlung, Anzahl wählbar | Pro App-User über das Plan-Limit hinaus. |

Einmalzahlungen erscheinen sofort in der **Rechnungs-Liste**. Der
Coaching-Retainer ist ein eigenständiges Abo und lässt sich getrennt vom
Haupt-Tarif kündigen.

## Kündigen und fortsetzen

- **Kündigen zum Periodenende** stoppt die nächste Verlängerung. Bis zum
  Ablauf bleibt der Tarif voll aktiv. Danach geht der Mandant in den
  Read-Only-Modus.
- Solange das Abo in der **Schonfrist** ist (Kündigung erfolgt, aber
  Periode noch nicht zu Ende), erscheint **Kündigung zurücknehmen** — ein
  Klick reicht, das Abo läuft normal weiter.
- Daten bleiben **30 Tage nach Ablauf** als ZIP-Archiv exportierbar.

## Rechnungen

Alle bezahlten und offenen Rechnungen erscheinen in der unteren Tabelle.
Jede Rechnung lässt sich als PDF herunterladen.

Die Rechnung enthält:

- Mandanten-Name als Empfänger
- USt-IdNr (sofern bei Stripe hinterlegt)
- Reverse-Charge-Vermerk bei B2B-Kunden im EU-Ausland
- Posten-Details (Tarif, Zeitraum, Add-on)
- Brutto, Netto, Steuersatz

## USt-IdNr und Reverse-Charge

Im Stripe-Checkout können Sie eine **USt-IdNr** angeben. Stripe Tax prüft
sie automatisch und rechnet bei B2B-Kunden im EU-Ausland im
**Reverse-Charge-Verfahren** ab — die Rechnung weist dann keine deutsche
Umsatzsteuer aus.

Innerhalb Deutschlands gilt der reguläre Satz (19 %). Privatkunden zahlen
den Brutto-Preis.

## Stripe-Setup (für Plattform-Betreiber)

Vor dem Aktivieren von `FEATURE_BILLING_ENABLED=true` müssen Products und
Prices im Stripe-Dashboard existieren und ihre IDs in der `.env` stehen.
Die Vorlagen liegen unter `storage/stripe-bootstrap/` und werden vom
Artisan-Command **`stripe:bootstrap`** in das verbundene Stripe-Konto
gespiegelt:

```bash
php artisan stripe:bootstrap --dry-run   # Vorschau, ohne etwas anzulegen
php artisan stripe:bootstrap             # Products + Prices anlegen
```

Voraussetzung: `STRIPE_SECRET` (Test- oder Live-Mode-Key) ist in der
`.env` gesetzt. Der Command ist **idempotent** — bereits angelegte
Products werden anhand des `metadata.planb_key` erkannt, Prices über den
`lookup_key`. Mehrfach-Ausführungen erzeugen keine Duplikate.

Nach dem Lauf gibt der Command einen Block mit `STRIPE_PRICE_*`-Werten
aus, der direkt in die `.env` übernommen werden kann. Anschließend:

1. Webhook-Endpoint im Stripe-Dashboard auf
   `https://<domain>/stripe/webhook` registrieren und das Signing-Secret
   als `STRIPE_WEBHOOK_SECRET` setzen.
2. `FEATURE_BILLING_ENABLED=true` schalten.

## Sicherheit

- Zahlungsdaten erreichen unseren Server **nicht** — die Eingabe erfolgt
  ausschließlich auf Stripe-Seiten (PCI-DSS Level 1).
- Wir speichern nur eine **Stripe-Kunden-ID** und die letzten vier
  Stellen der hinterlegten Karte (für die Anzeige).
- Stripe-Webhooks halten den Abrechnungs-Status synchron — Statuswechsel
  (Zahlung erfolgreich / fehlgeschlagen, Kündigung, Trial-Ende) treffen
  innerhalb weniger Sekunden bei uns ein.
