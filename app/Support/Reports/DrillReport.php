<?php

namespace App\Support\Reports;

use App\Enums\ScenarioRunMode;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Auswertung eines abgeschlossenen Übungs-Laufs (mode=drill) für die
 * Übungsberichte. Alle Kennzahlen werden on-demand aus den vorhandenen
 * Run-Daten (Steps + Quittierungen) berechnet — der Bericht ist der
 * Prüfer-/Versicherungs-Nachweis, dass die Übung stattgefunden hat.
 *
 * Kennzahlen-Definitionen:
 *  - Dauer: started_at → ended_at bzw. aborted_at.
 *  - Erste Reaktion: started_at → früheste Quittierung (egal ob „gesehen"
 *    oder „übernehme").
 *  - Übernahme: started_at → früheste Quittierung mit Status taking_over.
 *  - Quittierungs-Quote: Nutzer mit Quittierung ÷ aktuelle Team-Mitglieder.
 */
class DrillReport
{
    public function __construct(public readonly ScenarioRun $run) {}

    /**
     * Bericht inkl. aller benötigten Relationen aufbauen.
     */
    public static function for(ScenarioRun $run): self
    {
        $run->loadMissing([
            'scenario',
            'startedBy',
            'steps.checkedBy',
            'acknowledgements.user',
        ]);

        return new self($run);
    }

    /**
     * Abgeschlossene Übungs-Läufe (beendet oder abgebrochen) — die
     * Grundmenge der Übungsberichte. Company-Scoping übernimmt der
     * globale CurrentCompanyScope des Models.
     *
     * @return Builder<ScenarioRun>
     */
    public static function completedDrillsQuery(): Builder
    {
        return ScenarioRun::query()
            ->where('mode', ScenarioRunMode::Drill)
            ->where(fn (Builder $query) => $query
                ->whereNotNull('ended_at')
                ->orWhereNotNull('aborted_at'));
    }

    public function isReportable(): bool
    {
        return $this->run->isDrill() && ! $this->run->isActive();
    }

    public function endedAt(): ?CarbonInterface
    {
        return $this->run->ended_at ?? $this->run->aborted_at;
    }

    public function wasAborted(): bool
    {
        return $this->run->aborted_at !== null;
    }

    public function outcomeLabel(): string
    {
        return $this->wasAborted() ? 'Abgebrochen' : 'Beendet';
    }

    public function wasEscalated(): bool
    {
        return $this->run->escalated_at !== null;
    }

    public function durationSeconds(): ?int
    {
        $end = $this->endedAt();

        if ($this->run->started_at === null || $end === null) {
            return null;
        }

        return max(0, (int) $this->run->started_at->diffInSeconds($end));
    }

    /**
     * Sekunden von Start bis zur ersten Quittierung (gesehen oder übernommen).
     */
    public function secondsToFirstAcknowledgement(): ?int
    {
        return $this->secondsFromStartTo(
            $this->run->acknowledgements->min('acknowledged_at'),
        );
    }

    /**
     * Sekunden von Start bis zur ersten Übernahme („übernehme").
     */
    public function secondsToTakeover(): ?int
    {
        return $this->secondsFromStartTo(
            $this->run->acknowledgements
                ->where('status', ScenarioRunAcknowledgement::STATUS_TAKING_OVER)
                ->min('acknowledged_at'),
        );
    }

    public function stepsTotal(): int
    {
        return $this->run->steps->count();
    }

    public function stepsDone(): int
    {
        return $this->run->steps->whereNotNull('checked_at')->count();
    }

    public function stepsOpen(): int
    {
        return $this->stepsTotal() - $this->stepsDone();
    }

    public function hasTakeover(): bool
    {
        return $this->run->acknowledgements
            ->contains('status', ScenarioRunAcknowledgement::STATUS_TAKING_OVER);
    }

    /**
     * Namen aller Beteiligten: Starter, Schritt-Abhaker und Quittierende.
     *
     * @return Collection<int, string>
     */
    public function participantNames(): Collection
    {
        return collect([$this->run->startedBy?->name])
            ->merge($this->run->steps->map(fn ($step) => $step->checkedBy?->name))
            ->merge($this->run->acknowledgements->map(fn ($ack) => $ack->user?->name))
            ->filter()
            ->unique()
            ->values();
    }

    public function participantCount(): int
    {
        return $this->participantNames()->count();
    }

    /**
     * Anzahl der Nutzer, die den Alarm quittiert haben (max. eine
     * Quittierung je Nutzer garantiert das Datenmodell).
     */
    public function acknowledgedUserCount(): int
    {
        return $this->run->acknowledgements->unique('user_id')->count();
    }

    /**
     * Aktuelle Mitgliederzahl des Teams — Bezugsgröße der Quittierungs-Quote.
     */
    public function teamMemberCount(): int
    {
        return (int) $this->run->company?->team?->members()->count();
    }

    /**
     * Quittierungs-Quote in Prozent (0–100), null ohne Team-Mitglieder.
     * Die Mitgliederzahl kann vorberechnet übergeben werden (Listen-Ansicht,
     * vermeidet eine Query je Zeile).
     */
    public function acknowledgementRate(?int $memberCount = null): ?int
    {
        $members = $memberCount ?? $this->teamMemberCount();

        if ($members < 1) {
            return null;
        }

        return (int) round(min(1, $this->acknowledgedUserCount() / $members) * 100);
    }

    /**
     * Festgestellte Lücken für die Hinweis-Box bzw. den PDF-Bericht.
     *
     * @return array<int, string>
     */
    public function gaps(): array
    {
        $gaps = [];

        if ($this->stepsOpen() > 0) {
            $gaps[] = $this->stepsOpen() === 1
                ? '1 Schritt blieb offen und wurde nicht abgehakt.'
                : $this->stepsOpen().' Schritte blieben offen und wurden nicht abgehakt.';
        }

        if ($this->run->acknowledgements->isEmpty()) {
            $gaps[] = 'Keine Alarm-Quittierung — niemand hat den Alarm bestätigt.';
        } elseif (! $this->hasTakeover()) {
            $gaps[] = 'Keine Übernahme — niemand hat die Verantwortung übernommen.';
        }

        if ($this->wasEscalated()) {
            $gaps[] = 'Die Eskalation wurde ausgelöst, weil zunächst keine Reaktion erfolgte.';
        }

        if ($this->wasAborted()) {
            $gaps[] = 'Die Übung wurde abgebrochen statt regulär beendet.';
        }

        return $gaps;
    }

    /**
     * Sekundenwert lesbar formatieren: „1 Std. 12 Min.", „8 Min.", „45 Sek.".
     */
    public static function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '–';
        }

        if ($seconds < 60) {
            return $seconds.' Sek.';
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            $rest = $seconds % 60;

            return $rest > 0 ? $minutes.' Min. '.$rest.' Sek.' : $minutes.' Min.';
        }

        $hours = intdiv($minutes, 60);
        $restMinutes = $minutes % 60;

        return $restMinutes > 0 ? $hours.' Std. '.$restMinutes.' Min.' : $hours.' Std.';
    }

    private function secondsFromStartTo(mixed $timestamp): ?int
    {
        if ($this->run->started_at === null || ! $timestamp instanceof CarbonInterface) {
            return null;
        }

        return max(0, (int) $this->run->started_at->diffInSeconds($timestamp));
    }
}
