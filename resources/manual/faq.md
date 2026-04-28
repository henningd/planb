## Häufige Fragen

### Wie lange dauert die Erst-Einrichtung?

Realistisch zwischen 2 und 6 Stunden, verteilt auf 1–2 Tage. Wenn Sie alle Daten griffbereit haben (Versicherungs-Police, Dienstleister-Hotlines, Lagerschlüssel-Liste), schaffen Sie es in einem Vormittag. Branchen-Templates verkürzen die Zeit nochmal um etwa 60 %.

### Brauche ich technisches Wissen?

Nein. Die App ist explizit für Geschäftsführung und Mitarbeiter ohne IT-Hintergrund gemacht. Das einzige, was Sie idealerweise vorbereiten: die Liste Ihrer Geschäfts-Systeme (Warenwirtschaft, E-Mail, Server …) und eine grobe Vorstellung von Wichtigkeiten.

### Kann ich die App auf dem Smartphone bedienen?

Die App ist responsive — Sie können sie auf Tablet und Smartphone nutzen, vor allem für Lese-Zugriff. Ein vollständiger Stammdaten-Aufbau geht aber am Desktop-Browser deutlich angenehmer.

### Was passiert mit unseren Daten, wenn die Plattform offline ist?

Sie haben jederzeit den **vollständigen Mandanten-Export** als ZIP — daten.json plus alle PDFs. Wenn die Plattform tatsächlich nicht erreichbar wäre, hätten Sie immer noch das letzte freigegebene Handbuch als PDF auf dem Schreibtisch.

### Wir sind nur 8 Mitarbeiter — brauchen wir das wirklich?

Ja, wenn:

- Sie auf IT angewiesen sind (Warenwirtschaft, Buchhaltungs-Software, E-Mail).
- Sie Kunden- oder Patientendaten verarbeiten (DSGVO-Pflichten gelten ab 1).
- Eine Cyberversicherung haben oder anstreben (fast alle fragen nach Notfallplänen).

Wenn Sie ein 3-Personen-Café ohne IT sind — eher nicht.

### Werden personenbezogene Daten von Mitarbeitern gespeichert?

Ja: Name, Position, Abteilung, Telefonnummern, E-Mail, ggf. Krisenrolle. Rechtsgrundlage typischerweise § 26 BDSG (Beschäftigtendaten im Rahmen des Arbeitsverhältnisses). Sie sollten Mitarbeiter darüber informieren — am besten mit einer separaten Beschäftigten-Datenschutzinformation.

### Wie sicher sind unsere Daten gehostet?

Die Plattform läuft in Deutschland (DigitalOcean Frankfurt). Datenhaltung ausschließlich in der EU. Verbindungen sind TLS-verschlüsselt, Passwörter werden mit bcrypt gehasht. Optional 2FA. Ausführlich siehe Datenschutzerklärung der Plattform.

### Können wir das Notfallhandbuch mit unserem Versicherer teilen?

Ja, über **Freigabelinks** (Read-only-URLs mit Ablauf). Sie können die Lebensdauer auf 14 Tage setzen, dann hat der Versicherer Zugang ohne dass er ein Konto braucht — und nach 14 Tagen ist der Link automatisch tot.

### Was passiert bei Vertragsende?

- Mandanten-Archiv (ZIP) herunterladen — Sie haben alle Daten.
- Konto löschen — Sie verlieren den Zugang.

Die Plattform behält Ihre Daten nicht länger als technisch nötig. Konkrete Fristen siehe AGB.

### Kann ich mehrere Firmen verwalten?

Ja. Pro App-Benutzer können beliebig viele Teams (= Mandanten) angelegt werden. Sie schalten oben in der Sidebar zwischen ihnen um. Sinnvoll für Berater, Steuerkanzleien, IT-Dienstleister.

### Bekomme ich Erinnerungen, wenn ein Test fällig ist?

Ja, auf zwei Wegen:

- Auf dem **Dashboard** in der „Was muss ich heute tun?"-Liste, sobald Fälligkeit < 14 Tage.
- Per **E-Mail** an den jeweiligen Verantwortlichen (sofern E-Mail-Versand konfiguriert).

### Wie aktualisiere ich das Notfallhandbuch?

Stammdaten ändern Sie jederzeit. Wenn die Änderungen wesentlich sind, **legen Sie eine neue Handbuch-Version** an, geben sie frei und teilen das neue PDF. Das alte PDF bleibt im Versions-Verlauf erhalten.

### Was ist der Unterschied zwischen Tabletop und echter Lage?

- **Tabletop-Übung**: geplante Übung am Schreibtisch, alle wissen, dass es kein echter Notfall ist. Kein Banner, kein Cockpit-Ramp-Up.
- **Echte Lage**: tatsächlicher Notfall. Rotes Banner für alle, Krisen-Cockpit aktivierbar, mit Vorfall verknüpfbar.

### Kann ich Daten aus einer Word-/Excel-Vorlage importieren?

Direkt nein. Aber: das Datenmodell ist sauber strukturiert, sodass Sie ein Backup-JSON aus einer anderen Plattform-Instanz importieren können. Für Word/Excel müssten Sie händisch erfassen — was meistens schneller geht als das Import-Mapping zu schreiben.

### Wer haftet, wenn das Handbuch falsch ist?

Wie bei jedem Tool: die Verantwortung liegt bei dem, der die Daten gepflegt hat. Die Plattform liefert die Struktur, Sie liefern den Inhalt. Falsche Telefonnummern, fehlende Stellvertretungen — das ist Pflege-Arbeit.

### Wo bekomme ich Hilfe bei Problemen?

Über die im Impressum hinterlegte Kontakt-E-Mail. Für Notfälle (Login geht nicht) hat der Plattform-Betreiber meist eine eigene Hotline.
