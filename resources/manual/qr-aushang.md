## Zwei Arten von Aushängen

Es gibt in der Plattform **zwei verschiedene gedruckte Aushänge** — sie sehen sich ähnlich, tun aber Unterschiedliches:

1. **Der Notfallaushang je Standort** (dieser Abschnitt direkt hier): großer roter A4-Aushang fürs Gebäude. Sein QR-Code wird mit der **Notfall-App gescannt** und öffnet **offline** die passende Szenario-Checkliste — er funktioniert also auch, wenn Server und Internet ausgefallen sind.
2. **Der System-Aushang** (weiter unten): Info-Zettel für den Server-Schrank. Sein QR-Code führt zur **System-Detailseite im Browser** — dafür braucht man Netz und ein Login.

## Der Notfallaushang je Standort

**So drucken Sie ihn:** Auf der Seite **„Standorte"** im Kontextmenü (⋯) eines Standorts auf **„Notfallaushang"** klicken — es öffnet sich der druckfertige A4-Aushang mit rotem „IM NOTFALL"-Kopf, Standortname und QR-Code, dazu eine Drei-Schritt-Anleitung für die Mitarbeitenden.

- Standardmäßig ist der Aushang **standortweit**: Nach dem Scan wählt man in der App das passende Szenario. Sie können den Aushang auch **auf ein festes Szenario festlegen** (z. B. „Stromausfall" für den Technikraum), indem Sie an die Adresse der Aushang-Seite `?scenario=<Szenario-ID>` anhängen.
- **Der Scan funktioniert offline:** Der QR-Code enthält keine Internet-Adresse, sondern eine Kennung, die die Notfall-App gegen ihre lokalen Daten auflöst. Voraussetzung: Die App wurde auf dem Gerät einmal gekoppelt und hat synchronisiert.
- **Fallback ohne App:** Existiert ein aktiver Handbuch-Freigabelink, druckt der Aushang zusätzlich dessen Web-Adresse ab — für Personen ohne eingerichtete App.
- **Wo aufhängen:** am Empfang, im Flur, im Pausenraum, an jedem Standort/Eigenbetrieb — überall dort, wo im Ernstfall jemand als Erstes steht.

## Der System-Aushang (Server-Schrank)

Der System-Aushang ist ein **gedruckter A4-Zettel** für den Server-Schrank oder den Standort eines kritischen Systems. Er enthält:

- Den **System-Namen** und die Kategorie.
- Einen **QR-Code**, der zur System-Detailseite in der App führt.
- **Notfall-Workaround** — was tun bei Ausfall (kompakter Text aus dem System-Datensatz).
- **Runbook-Verweis** falls vorhanden.
- **Kenngrößen** (RTO, RPO, Ausfallkosten).
- **Dienstleister mit Hotline und Vertragsnummer**.
- **Abhängigkeiten** — was muss zuerst laufen.

### So drucken Sie den System-Aushang

Auf der **Systeme-Liste** oder der **System-Detail-Seite** gibt es einen QR-Code-Knopf:

1. Klick öffnet die Aushang-Seite in einem neuen Tab.
2. Oben gibt es einen **Drucken**-Knopf.
3. Browser-Druckdialog → A4 hochformat → drucken.

## Wo den Aushang hinhängen

- **Direkt am Server-Schrank** (die häufigste Stelle).
- **Beim Sicherungskasten** für Strom-Systeme.
- **Am Hauptverteiler** für Internet/Telefon.
- **In der Werkstatt** für branchenspezifische Maschinen.
- **Im Aufzugsraum** für die Aufzugsteuerung.

## Was der QR-Code tut

Beim Scannen mit dem Smartphone wird der QR-Code zur **System-Detail-Seite in der App** weitergeleitet. Dort sehen die berechtigten Mitarbeiter:

- Aktuelle Hotline-Nummern (auch wenn der Aushang veraltet ist).
- Aktuelle Dienstleister-Vertragsdaten.
- Aktuelle Abhängigkeiten und Wiederanlauf-Hinweise.

So kombinieren Sie das Beste aus beiden Welten: das Papier zeigt im Strom-Aus-Fall sofort die wichtigsten Daten, der QR-Code holt die jeweils aktuellen Daten ab.

## Pflege

Wenn sich Dienstleister, Hotlines oder Workaround-Texte ändern, **drucken Sie den Aushang neu**. Faustregel: einmal pro Quartal alle Aushänge prüfen, einmal pro Halbjahr neu drucken.

## Wer das tun darf

Aushang aufrufen darf jeder mit Lesezugriff aufs System (also alle Team-Mitglieder).

> **Praxis-Hinweis**: Befestigen Sie den Aushang nicht direkt mit Klebeband am Server-Lüftungsschlitz. Eine **Klemm-Mappe** an einem Haken neben dem Schrank ist langlebiger.
