## Worum es geht

Behörden, Auditoren und Versicherer wollen vor einer Auftragsvergabe oder Vertrags-Unterschrift formale Compliance-Dokumente sehen. Die Plattform stellt sie unter eigenen URLs bereit, sodass Sie den Link weitergeben können statt PDFs als Anlage zu mailen.

## Verfügbare Dokumente

| Dokument | URL | Wofür |
|---|---|---|
| **Impressum** | `/impressum` | Pflichtangaben nach §5 TMG |
| **Datenschutzerklärung** | `/datenschutz` | DSGVO Art. 13/14 |
| **AGB** | `/agb` | Vertragsbedingungen B2B |
| **Auftragsverarbeitung (AVV)** | `/auftragsverarbeitung` | Vertrag nach Art. 28 DSGVO |
| **TOM** | `/tom` | Technische und organisatorische Maßnahmen, Art. 32 DSGVO |
| **Subprocessors** | `/subprocessors` | Liste der Unterauftragsverarbeiter |
| **Barrierefreiheit** | `/barrierefreiheit` | Erklärung zur digitalen Barrierefreiheit (BITV 2.0 / BFSG) |
| **security.txt** | `/.well-known/security.txt` | Kontakt für Sicherheits-Meldungen (RFC 9116) |

## Pflege

Alle Inhalte werden in den **Plattform-Settings** (Super-Admin) gepflegt — kein Code-Edit, keine Re-Deployments. Pro Dokument gibt es einen Setting-Schlüssel:

| Setting-Schlüssel | URL |
|---|---|
| `platform_imprint` | `/impressum` |
| `platform_privacy` | `/datenschutz` |
| `platform_terms` | `/agb` |
| `platform_av_contract` | `/auftragsverarbeitung` |
| `platform_tom` | `/tom` |
| `platform_subprocessors` | `/subprocessors` |
| `platform_accessibility` | `/barrierefreiheit` |
| `platform_security_contact` | wird in `/.well-known/security.txt` als Contact-Adresse genutzt |

Wenn Sie keinen eigenen Wert setzen, greift jeweils der ausgelieferte Default — eine substanzielle Vorlage, die jedoch **vor produktiver Nutzung juristisch geprüft** werden sollte.

## Markdown-Unterstützung

Die Dokumente AVV, TOM, Subprocessors und Barrierefreiheit werden als Markdown gerendert — Sie können dort Überschriften, Listen, Tabellen und Hervorhebungen setzen. Impressum, Datenschutz und AGB werden als Plain-Text mit Zeilenumbrüchen gerendert.

## Wer was sehen darf

Alle Dokumente sind **öffentlich** unter den oben genannten URLs erreichbar — auch ohne Login. So können Sie die Links direkt an Behörden, Auditoren und Versicherer weitergeben.

## Was im Vertrieb zu beachten ist

- **Vor jedem Behörden-Auftrag**: AVV unterschreiben lassen oder den eigenen AVV des Kunden akzeptieren. Für öffentliche Auftraggeber zusätzlich die Barrierefreiheits-Erklärung als Nachweis nach BITV 2.0 vorhalten.
- **Vor jeder Cyberversicherung**: TOM-Liste und Subprocessor-Liste einreichen.
- **Vor Vergabe-Verfahren**: Eignungs-Nachweise (Referenzen, Zertifikate, Versicherungen) zusätzlich zu den hier verlinkten Dokumenten bereithalten.
- **`security.txt`** ist ab dem EU Cyber Resilience Act (CRA, ab 2027) für Anbieter von „Produkten mit digitalen Elementen" Pflicht — schon heute Best Practice.
- **Barrierefreiheits-Erklärung** ist für öffentliche Stellen seit BITV 2.0 Pflicht und ab dem Barrierefreiheitsstärkungsgesetz (BFSG, Juni 2025) Best Practice für privatwirtschaftliche Anbieter. Die Erklärung sollte **mindestens einmal jährlich** sowie bei jeder wesentlichen Plattform-Änderung überprüft und aktualisiert werden.

> **Praxis-Hinweis**: Halten Sie alle Dokumente **synchron** aktuell. Wenn die Datenschutzerklärung einen neuen Subprocessor erwähnt, muss er auch in der Subprocessor-Liste auftauchen — sonst ist die Kette aus AVV-Sicht unvollständig.
