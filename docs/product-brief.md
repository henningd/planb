# PlanB – Produkt-Brief & MVP-Plan

## Produkt

Digitales Notfallhandbuch- und Krisenmanagement-Tool für kleine und mittelständische Unternehmen.

**Stack:** Laravel 13, Livewire 4, Flux UI, Tailwind v4, Fortify. (Filament optional – aktuell nicht installiert.)

## MVP-Ziel

Ein Unternehmen soll:

- ein Profil anlegen
- Ansprechpartner definieren
- Notfall-Szenarien verstehen
- eine erste Struktur für ein Notfallhandbuch aufbauen

## Leitplanken für die Umsetzung

- Sauberer, verständlicher Code
- Laravel Best Practices
- Einfache Lösungen statt Overengineering
- SaaS-Denke: mehrere Firmen / Mandanten
- Strukturierte, geführte Erstellung des Notfallhandbuchs

## Fachliche Kernideen

- Unternehmen haben im Notfall keinen klaren Plan.
- Klar sein muss:
  - **Wer entscheidet.**
  - **Wer informiert wird.**
  - **Was zuerst passiert.**
- Ziel: einfache, geführte Erstellung eines Notfallhandbuchs.

---

## Schritt 1 – Datenbank-Design

### companies

- `id`
- `name`
- `industry` (enum: `handwerk`, `handel`, `dienstleistung`, `produktion`, `sonstiges`)
- `employee_count`
- `locations_count`
- `created_at`, `updated_at`

### users (Laravel default erweitern)

- gehört zu Company (`company_id`)

### emergency_levels

- `id`
- `name` (Kritisch, Wichtig, Beobachten)
- `description`
- `reaction`

### contacts (Ansprechpartner)

- `id`
- `company_id`
- `name`
- `role`
- `phone`
- `email`
- `type` (intern / extern)
- `is_primary` (bool)
- `created_at`, `updated_at`

## Schritt 2 – Multi-Tenant-Ansatz

- Ein User gehört genau einer Firma.
- Alle Daten werden global nach `company_id` gefiltert.
- Kein komplexes Paket – einfache Lösung (Global Scope + auth-basierte Zuordnung).

## Schritt 3 – Admin Panel (optional Filament)

Filament Resources für Company, Contacts, Emergency Levels.
Alternative: Livewire/Flux-basierte Admin-Views, da Flux im Projekt bereits vorhanden ist.

## Schritt 4 – Erste Logik

- Beim Anlegen einer Company: automatisch 3 `emergency_levels` seeden (Kritisch, Wichtig, Beobachten).
- Validierung: mindestens 1 Hauptansprechpartner muss existieren.

## Schritt 5 – Erste UI (sehr einfach)

Dashboard mit:

- Firmenname
- Anzahl Kontakte
- Hinweis, wenn kein Ansprechpartner existiert
- einfache Übersicht

## Schritt 6 – Architektur

Erweiterungsfähig halten für spätere Module:

- Checklisten
- Systeme
- Wiederanlauf
- Szenarien

Skalierbarkeit durch:

- klare Mandantentrennung
- Domain-Ordner pro Modul (`app/Domain/*` oder `app/Modules/*`)
- Policies & Global Scopes
- Eventing, wenn Module lose gekoppelt sein sollen

---

## Hinweis zum bestehenden Code

Das Projekt basiert auf dem Laravel Livewire Starter-Kit und enthält bereits eine Mandantenstruktur (`Team`, `team_members`, `team_invitations`) inklusive Rollen. Der MVP sollte darauf aufbauen statt eine parallele `company_id`-Spalte auf `users` zu legen. Vorgeschlagenes Mapping:

- `Team` bleibt der Mandant (Auth, Membership, Invitations).
- `Company` wird als 1:1-Profil an `Team` angehängt (Branche, Mitarbeiterzahl, Standorte).
- `Contact`, `EmergencyLevel` etc. hängen an `company_id` (oder direkt an `team_id`).
- User-zu-Mandant-Zuordnung läuft über das bestehende `team_members`-Pivot – kein `company_id` auf `users` nötig.
