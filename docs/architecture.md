# PlanB – Architektur & Erweiterung

Kurzreferenz, wie der MVP heute aufgebaut ist und wie zukünftige Module (Checklisten, Systeme, Wiederanlauf, Szenarien, …) anschließen.

## Aktuelle Schichten

```
app/
  Models/                 Eloquent-Entities (Company, Contact, EmergencyLevel)
  Enums/                  Domänen-Enums (Industry, ContactType, TeamRole)
  Concerns/               Wiederverwendbare Traits (BelongsToCurrentCompany, HasTeams)
  Observers/              Model-Lifecycle-Hooks (CompanyObserver, ContactObserver)
  Scopes/                 Eloquent Global Scopes (CurrentCompanyScope)
  Support/                Service-artige Helper (CurrentCompany)

resources/views/
  layouts/                App- & Auth-Layouts
  pages/                  Livewire Single-File-Pages (⚡<name>.blade.php)
  components/             Blade-Komponenten

routes/
  web.php                 Hauptrouten, {current_team}-Gruppe
  settings.php            Settings-Bereich
```

## Mandanten-Modell

```
Team  ──1:1──▶  Company  ──1:n──▶  Contact / EmergencyLevel / … (jedes neue Modul)
  ▲
  └── User via team_members (bestehende Starter-Kit-Logik)
```

- **Team** = Mandant (Auth, Einladungen, Rollen).
- **Company** = Geschäftsprofil. 1:1 pro Team.
- Alle Unterentitäten hängen an `company_id`.

## Regeln für jedes neue Modul

Beispiel: **Systeme** (IT-Systeme, die im Wiederanlauf priorisiert werden).

### 1. Migration + Model

- Tabelle mit `foreignId('company_id')->constrained()->cascadeOnDelete()`.
- Model erbt `App\Models\Model` (Eloquent) und nutzt den Trait `BelongsToCurrentCompany`.
- Keine eigene Relation `company()` ins Model schreiben – kommt aus dem Trait.
- `HasFactory` + `#[Fillable]` + `casts()` konsequent setzen.

```php
#[Fillable(['company_id', 'name', 'criticality', 'recovery_time_objective'])]
class System extends Model
{
    use BelongsToCurrentCompany, HasFactory;

    protected function casts(): array
    {
        return ['criticality' => Criticality::class];
    }
}
```

Ergebnis: Queries sind automatisch auf den aktiven Mandanten gefiltert, `company_id` wird beim Create automatisch gesetzt.

### 2. Seitenstruktur

- Single-File-Livewire-Page unter `resources/views/pages/<resource>/⚡index.blade.php`.
- Route im `{current_team}`-Block registrieren:

```php
Route::livewire('systems', 'pages::systems.index')->name('systems.index');
```

- Sidebar-Eintrag in der Gruppe „Notfallhandbuch" (`layouts/app/sidebar.blade.php`).

### 3. Side-Effects per Observer

Nicht in Livewire-Pages verstreuen, sondern in `app/Observers/<Model>Observer.php` kapseln und am Model mit `#[ObservedBy(...)]` registrieren. Beispiele:

- `CompanyObserver::created` → seedet 3 EmergencyLevels.
- `ContactObserver::creating` → Auto-Primary für ersten Kontakt.

### 4. Tests

Minimum pro Modul:

- Eine Lifecycle-Prüfung (Factory create + Invariants).
- Eine Mandanten-Isolation (zwei Companies, sauber getrennte Daten).
- Ein Smoke-Test für die Index-Page (HTTP 200 + erwarteter Text).

Alle Tests nutzen `uses(RefreshDatabase::class)`.

## Grenzen ziehen (wann neue Schichten rechtfertigt sind)

| Signal | Reaktion |
|---|---|
| Mehr als 2 Modelle hängen eng an einem Thema (Checklist + ChecklistItem + ChecklistRun) | Domain-Ordner: `app/Domain/Checklists/…` mit Models, Actions, Events |
| Ein Workflow erzeugt Seiteneffekte in mehreren Modulen | Event + Listener statt direkter Kopplung |
| Berechtigungen gehen über „User gehört zum Team" hinaus | Policy erstellen (`php artisan make:policy`) |
| Dieselbe Business-Logik in Livewire-Page und Command/API-Endpoint | Logik in Action-Klasse: `app/Actions/<Domain>/<DoX>.php` |
| Mehrere Eingabepunkte validieren dieselben Regeln | Form Request oder Rule-Objekt |

Vorher nicht. Ein Modul mit 3 CRUD-Operationen braucht keine Domain-Schicht.

## Geplante Module im Notfallhandbuch-Kontext

| Modul | Eigen­schaften | Besonderheiten |
|---|---|---|
| **Systeme** | Name, Kritikalität, RTO, Abhängigkeiten | Self-Referential Relation für Abhängigkeiten |
| **Wiederanlauf­pläne** | Schritte, Reihenfolge, Verantwortliche | Polymorphe Verknüpfung mit Systemen/Prozessen |
| **Szenarien** | Vordefinierte Krisen­fälle (Ransomware, Stromausfall …) | Seeds aus Branchen­vorlagen |
| **Checklisten** | Checklist + Items; pro Ernstfall instanziierbar | ChecklistRun mit Status/Protokoll separat von der Vorlage |
| **Dienstleister** | Externe Kontakte mit Vertrags­daten, SLAs | Spezialisierung von Contact oder eigenes Modell |

Alle folgen demselben Pattern (FK `company_id` + Trait + Livewire-Page + Observer bei Bedarf).

## Was bewusst nicht eingebaut ist

- **Kein Domain-Layer** für die aktuellen 3 Entitäten – ein Trait reicht.
- **Keine Policies** – Mandanten-Scoping via Global Scope + Middleware `EnsureTeamMembership` deckt die Autorisierung in der aktuellen Breite ab.
- **Keine Events/Listener** – es gibt noch keine modulübergreifenden Side Effects.
- **Kein API/Resource-Layer** – rein Livewire, bis ein externer Konsument existiert (Versicherungs-Portal etc.).

Sobald eines dieser Signale auftritt, gezielt ausbauen.
