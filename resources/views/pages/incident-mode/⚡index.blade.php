<?php

use App\Concerns\SendsCommunicationTemplates;
use App\Enums\CommunicationChannel;
use App\Events\ScenarioRunMessagePosted;
use App\Events\ScenarioRunStepCompleted;
use App\Events\ScenarioRunStepReopened;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\FordecDecision;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunMessage;
use App\Models\ScenarioRunStep;
use App\Models\ServiceProvider as ServiceProviderModel;
use App\Services\Sms\SmsGatewayContract;
use App\Support\BibleVerses;
use App\Support\Incident\Cockpit;
use App\Support\Incident\CockpitData;
use App\Support\Push\PushNotifier;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Krisen-Cockpit')] class extends Component {
    use SendsCommunicationTemplates;

    public ?string $previewTemplateId = null;

    /**
     * Typ für einen neuen manuellen Logbuch-Eintrag (note|decision|action).
     */
    public string $newLogType = 'note';

    /**
     * Freitext für einen neuen manuellen Logbuch-Eintrag.
     */
    public string $newLogMessage = '';

    /**
     * Freitext für eine neue Koordinations-/Lagemeldung (Chat, geteilt mit der App).
     */
    public string $newCoordinationMessage = '';

    // FORDEC-Entscheidungsmaske (Facts, Options, Risks & Benefits, Decision, Execution, Check).
    public string $fordecTitle = '';

    public string $fordecFacts = '';

    public string $fordecOptions = '';

    public string $fordecRisksBenefits = '';

    public string $fordecDecision = '';

    public string $fordecExecution = '';

    public ?string $fordecCheckAt = null;

    /**
     * Beim Page-Load gewählter Bibel-Vers für „kein Notfall". Bleibt für die
     * Lebenszeit der Component stabil — wechselt nicht bei wire-Aktionen.
     *
     * @var array{text: string, reference: string}|null
     */
    public ?array $peaceVerse = null;

    /**
     * Beim Page-Load gewählter Bibel-Vers für „aktiver Notfall".
     *
     * @var array{text: string, reference: string}|null
     */
    public ?array $crisisVerse = null;

    /**
     * Firmen-ID (für den Alarmierungs-Kanal) und ID des aktiven Laufs (für den
     * Schritt-Fortschritts-Kanal). Werden für die Echtzeit-Listener gebraucht,
     * damit ein Abhaken aus der App oder einem anderen Browser sofort ankommt.
     */
    // Leerer String statt null: Livewire wertet einen null-Property in einem
    // dynamischen Echo-Kanalnamen (`{activeRunId}`) als „fehlt" und wirft sonst.
    public string $companyId = '';

    public string $activeRunId = '';

    public function mount(): void
    {
        $this->peaceVerse = BibleVerses::random('peace');
        $this->crisisVerse = BibleVerses::random('crisis');
        $this->companyId = $this->company?->id ?? '';
        $this->activeRunId = $this->cockpit?->activeRun?->id ?? '';
    }

    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()?->currentCompany();
    }

    #[Computed]
    public function cockpit(): ?CockpitData
    {
        $company = $this->company;
        if (! $company) {
            return null;
        }

        return Cockpit::for($company, $this->activeRunId !== '' ? $this->activeRunId : null);
    }

    /**
     * Umschalter bei mehreren parallel aktiven Notfällen: wechselt das gesamte
     * Lagebild (Checkliste, Live-Kanal, Meldepflichten, Beenden) auf den
     * gewählten Ablauf. Fremde/beendete IDs fängt Cockpit::for selbst ab.
     */
    public function selectRun(string $id): void
    {
        $this->activeRunId = $id;
        $this->refreshCockpitLive();
        // Cockpit::for fällt bei unbekannter ID auf den neuesten Run zurück —
        // die Property danach auf die tatsächlich gewählte ID normalisieren
        // (wichtig für den dynamischen Echo-Kanal {activeRunId}).
        $this->activeRunId = $this->cockpit?->activeRun?->id ?? '';
    }

    #[Computed]
    public function isEnabled(): bool
    {
        $company = $this->company;
        if (! $company) {
            return false;
        }

        return Cockpit::isEnabledFor($company);
    }

    /**
     * Beendet den aktuell laufenden ScenarioRun (setzt ended_at = now()).
     */
    public function endRun(): void
    {
        $cockpit = $this->cockpit;
        $run = $cockpit?->activeRun;
        if ($run === null) {
            return;
        }

        $run->update(['ended_at' => now()]);
        $this->writeLogEntry($run, 'system', __('Szenario beendet'));
        unset($this->cockpit);
        // Läuft parallel ein weiterer Notfall, direkt dorthin umschalten.
        $this->activeRunId = $this->cockpit?->activeRun?->id ?? '';

        Flux::toast(variant: 'success', text: __('Szenario beendet.'));
    }

    /**
     * Hakt einen Schritt ab oder entfernt das Häkchen wieder.
     */
    public function toggleStep(string $id): void
    {
        $step = ScenarioRunStep::find($id);
        if ($step === null) {
            return;
        }

        $cockpit = $this->cockpit;
        $run = $cockpit?->activeRun;
        if ($run === null || $step->scenario_run_id !== $run->id) {
            return;
        }

        if ($step->checked_at === null) {
            $step->update([
                'checked_at' => now(),
                'checked_by_user_id' => Auth::id(),
            ]);
            $step->refresh();
            $this->writeLogEntry($run, 'step', __('Schritt erledigt: :title', ['title' => $step->title]));

            // Live an alle anderen Browser broadcasten (Reverb) – best-effort,
            // ein nicht erreichbarer Broadcast-Server darf das Abhaken NIE blockieren.
            rescue(fn () => event(new ScenarioRunStepCompleted(
                $step,
                Auth::user()->name,
                $step->checked_at?->toIso8601String(),
            )), report: false);
        } else {
            $step->update([
                'checked_at' => null,
                'checked_by_user_id' => null,
            ]);
            $step->refresh();
            $this->writeLogEntry($run, 'step', __('Schritt zurückgesetzt: :title', ['title' => $step->title]));

            rescue(fn () => event(new ScenarioRunStepReopened($step, Auth::user()->name)), report: false);
        }

        // Übrige Geräte der Firma zum Neu-Sync anstoßen (geteilter Fortschritt in den Apps).
        rescue(fn () => app(PushNotifier::class)->syncCompany($run->company), report: false);

        unset($this->cockpit);
    }

    // Ein Schritt wurde extern (App oder anderer Browser) an- bzw. abgehakt →
    // Cockpit neu berechnen. WICHTIG: pro Echo-Event eine eigene Methode mit
    // genau einem #[On] — mehrere gestapelte #[On] mit dynamischem Kanal
    // registrieren sich nicht zuverlässig alle (nur „completed" käme an,
    // „reopened"/Abhaken-Entfernen nicht).

    #[On('echo-private:scenario-run.{activeRunId},.step.completed')]
    public function onStepCompletedRemotely(): void
    {
        $this->refreshCockpitLive();
    }

    #[On('echo-private:scenario-run.{activeRunId},.step.reopened')]
    public function onStepReopenedRemotely(): void
    {
        $this->refreshCockpitLive();
    }

    #[On('echo-private:company.{companyId},.incident.started')]
    public function onIncidentStartedRemotely(): void
    {
        $this->refreshCockpitLive();
        $this->activeRunId = $this->cockpit?->activeRun?->id ?? '';
    }

    #[On('echo-private:company.{companyId},.incident.ended')]
    public function onIncidentEndedRemotely(): void
    {
        $this->refreshCockpitLive();
        $this->activeRunId = $this->cockpit?->activeRun?->id ?? '';
    }

    /**
     * Verwirft die berechneten Cockpit-Daten, sodass sie beim nächsten Zugriff
     * frisch aus der DB geladen werden (Live-Aktualisierung).
     */
    private function refreshCockpitLive(): void
    {
        unset($this->cockpit);
        unset($this->logEntries);
    }

    /**
     * Alarmiert den Krisenstab per SMS an alle hinterlegten Mobilnummern
     * (Hauptpersonen + Vertretungen, eindeutig). Schreibt einen
     * zusammenfassenden Logbuch-Eintrag.
     */
    public function alertCrisisStaff(): void
    {
        $gateway = app(SmsGatewayContract::class);
        if (! $gateway->isConfigured()) {
            return;
        }

        $cockpit = $this->cockpit;
        $run = $cockpit?->activeRun;
        if ($run === null) {
            return;
        }

        $numbers = collect($cockpit->crisisStaff)
            ->flatMap(function (array $member): array {
                $people = collect([$member['main'] ?? null])
                    ->merge($member['deputies'] ?? collect())
                    ->filter();

                return $people->pluck('mobile_phone')->all();
            })
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            Flux::toast(variant: 'warning', text: __('Keine Mobilnummern im Krisenstab hinterlegt.'));

            return;
        }

        $scenarioName = $run->scenario?->name ?? $run->title ?? '–';
        $message = __('[PlanB] Krisenstab-Aktivierung: :scenario. Bitte umgehend ins Krisen-Cockpit.', [
            'scenario' => $scenarioName,
        ]);

        $success = 0;
        $failure = 0;
        foreach ($numbers as $number) {
            $result = $gateway->send($number, $message);
            if ($result->success) {
                $success++;
            } else {
                $failure++;
            }
        }

        $this->writeLogEntry(
            $run,
            'alert',
            __('Krisenstab alarmiert: :success/:total SMS erfolgreich', [
                'success' => $success,
                'total' => $numbers->count(),
            ]),
        );

        unset($this->cockpit);

        Flux::toast(
            variant: $failure === 0 ? 'success' : 'warning',
            text: __('Krisenstab alarmiert: :success/:total SMS erfolgreich.', [
                'success' => $success,
                'total' => $numbers->count(),
            ]),
        );
    }

    /**
     * Legt einen manuellen Logbuch-Eintrag (Notiz/Entscheidung/Maßnahme) an.
     */
    public function addLogEntry(): void
    {
        $cockpit = $this->cockpit;
        $run = $cockpit?->activeRun;
        if ($run === null) {
            return;
        }

        $message = trim($this->newLogMessage);
        if ($message === '') {
            Flux::toast(variant: 'warning', text: __('Bitte einen Text für den Eintrag eingeben.'));

            return;
        }

        $type = in_array($this->newLogType, ['note', 'decision', 'action'], true)
            ? $this->newLogType
            : 'note';

        $this->writeLogEntry($run, $type, $message);

        $this->newLogMessage = '';
        $this->newLogType = 'note';
        unset($this->logEntries);

        Flux::toast(variant: 'success', text: __('Eintrag im Krisen-Logbuch gespeichert.'));
    }

    /**
     * Logbuch-Einträge des aktiven Laufs, absteigend nach Zeitpunkt.
     *
     * @return Collection<int, CrisisLogEntry>
     */
    #[Computed]
    public function logEntries(): Collection
    {
        $run = $this->cockpit?->activeRun;
        if ($run === null) {
            return collect();
        }

        return CrisisLogEntry::where('scenario_run_id', $run->id)
            ->with('user')
            ->orderByDesc('occurred_at')
            ->get();
    }

    /**
     * Schreibt einen Logbuch-Eintrag für den gegebenen Lauf.
     */
    private function writeLogEntry(ScenarioRun $run, string $type, string $message): void
    {
        CrisisLogEntry::create([
            'company_id' => $run->company_id,
            'scenario_run_id' => $run->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'source' => 'web',
            'message' => $message,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Koordinations-Chat (freie Lagemeldungen) des aktiven Laufs — derselbe
     * Strom wie in der App, chronologisch (älteste zuerst).
     *
     * @return Collection<int, ScenarioRunMessage>
     */
    #[Computed]
    public function coordinationMessages(): Collection
    {
        $run = $this->cockpit?->activeRun;
        if ($run === null) {
            return collect();
        }

        return $run->messages()->with('user')->get();
    }

    /**
     * Postet eine Koordinations-/Lagemeldung. Broadcastet via Reverb, sodass App
     * und andere Browser sie sofort sehen; App-Meldungen laufen umgekehrt hier ein.
     */
    public function postCoordinationMessage(): void
    {
        $run = $this->cockpit?->activeRun;
        if ($run === null) {
            return;
        }

        $body = trim($this->newCoordinationMessage);
        if ($body === '') {
            return;
        }

        $message = ScenarioRunMessage::create([
            'company_id' => $run->company_id,
            'scenario_run_id' => $run->id,
            'user_id' => Auth::id(),
            'author_name' => Auth::user()->name,
            'body' => mb_substr($body, 0, 2000),
        ]);

        $this->newCoordinationMessage = '';
        unset($this->coordinationMessages);

        rescue(fn () => event(new ScenarioRunMessagePosted($message)), report: false);
    }

    /**
     * Live-Aktualisierung: eine neue Meldung (aus der App oder einem anderen
     * Browser) verwirft den Cache, sodass sie sofort erscheint.
     */
    #[On('echo-private:scenario-run.{activeRunId},.message.posted')]
    public function onCoordinationMessagePosted(): void
    {
        unset($this->coordinationMessages);
    }

    /**
     * Dokumentierte FORDEC-Entscheidungen des aktiven Laufs (neueste zuerst).
     *
     * @return Collection<int, FordecDecision>
     */
    #[Computed]
    public function fordecDecisions(): Collection
    {
        $run = $this->cockpit?->activeRun;
        if ($run === null) {
            return collect();
        }

        return FordecDecision::where('scenario_run_id', $run->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Speichert eine FORDEC-Entscheidung und spiegelt sie ins Krisen-Logbuch.
     */
    public function saveFordec(): void
    {
        $run = $this->cockpit?->activeRun;
        if ($run === null) {
            Flux::toast(variant: 'warning', text: __('FORDEC-Entscheidungen können nur bei einem aktiven Vorfall dokumentiert werden.'));

            return;
        }

        $validated = $this->validate([
            'fordecTitle' => ['nullable', 'string', 'max:255'],
            'fordecFacts' => ['nullable', 'string', 'max:5000'],
            'fordecOptions' => ['nullable', 'string', 'max:5000'],
            'fordecRisksBenefits' => ['nullable', 'string', 'max:5000'],
            'fordecDecision' => ['required', 'string', 'max:5000'],
            'fordecExecution' => ['nullable', 'string', 'max:5000'],
            'fordecCheckAt' => ['nullable', 'date'],
        ]);

        FordecDecision::create([
            'company_id' => $run->company_id,
            'scenario_run_id' => $run->id,
            'user_id' => Auth::id(),
            'title' => $validated['fordecTitle'] ?: null,
            'facts' => $validated['fordecFacts'] ?: null,
            'options' => $validated['fordecOptions'] ?: null,
            'risks_benefits' => $validated['fordecRisksBenefits'] ?: null,
            'decision' => $validated['fordecDecision'],
            'execution' => $validated['fordecExecution'] ?: null,
            'check_at' => $validated['fordecCheckAt'] ?: null,
            'created_by_name' => Auth::user()?->name,
        ]);

        // Als Entscheidung ins Krisen-Logbuch spiegeln, damit sie im Protokoll/Export erscheint.
        $summary = trim(($validated['fordecTitle'] !== '' ? $validated['fordecTitle'].': ' : '').$validated['fordecDecision']);
        $this->writeLogEntry($run, 'decision', __('FORDEC-Entscheidung: :summary', ['summary' => $summary]));

        $this->reset([
            'fordecTitle', 'fordecFacts', 'fordecOptions',
            'fordecRisksBenefits', 'fordecDecision', 'fordecExecution', 'fordecCheckAt',
        ]);
        unset($this->fordecDecisions, $this->logEntries);

        Flux::toast(variant: 'success', text: __('FORDEC-Entscheidung dokumentiert.'));
    }

    /**
     * Farbe der Typ-Badge im Krisen-Logbuch.
     */
    public function logTypeBadgeColor(string $type): string
    {
        return match ($type) {
            'decision' => 'indigo',
            'action' => 'amber',
            'alert' => 'red',
            'step' => 'emerald',
            'system' => 'zinc',
            default => 'sky',
        };
    }

    /**
     * Anzeige-Label der Typ-Badge im Krisen-Logbuch.
     */
    public function logTypeLabel(string $type): string
    {
        return match ($type) {
            'note' => __('Notiz'),
            'decision' => __('Entscheidung'),
            'action' => __('Maßnahme'),
            'step' => __('Schritt'),
            'alert' => __('Alarmierung'),
            'system' => __('System'),
            default => $type,
        };
    }

    /**
     * Anzeige-Label der Quelle eines Logbuch-Eintrags (App/Web/System).
     */
    public function logSourceLabel(?string $source): string
    {
        return match ($source) {
            'app' => __('App'),
            'web' => __('Web'),
            'system' => __('System'),
            default => (string) $source,
        };
    }

    public function openTemplate(string $id): void
    {
        $this->previewTemplateId = $id;
        Flux::modal('cockpit-template-preview')->show();
    }

    /**
     * @return \App\Models\CommunicationTemplate|null
     */
    #[Computed]
    public function previewTemplate()
    {
        if (! $this->previewTemplateId) {
            return null;
        }

        return $this->cockpit?->communicationTemplates->firstWhere('id', $this->previewTemplateId);
    }

    /**
     * Dienstleister der Firma (für die Auflösung von „Verantwortlich"-Angaben).
     *
     * @return Collection<int, ServiceProviderModel>
     */
    #[Computed]
    public function serviceProviders(): Collection
    {
        $company = $this->company;

        return $company
            ? ServiceProviderModel::where('company_id', $company->id)->get()
            : collect();
    }

    /**
     * Löst eine freie „Verantwortlich"-Angabe eines Schritts (z. B.
     * „Notfallbeauftragter") auf einen konkreten Namen auf: zuerst über die
     * Krisenstab-Rollen (→ Hauptperson), sonst über die Dienstleister
     * (Name/Typ/Ansprechpartner). Bewusst konservativ – nur bei einem sicheren
     * Treffer wird ein Name zurückgegeben, sonst null (kein falscher Name im Ernstfall).
     */
    public function resolveResponsible(?string $responsible): ?string
    {
        $needle = self::normalizeRole($responsible);
        if ($needle === '') {
            return null;
        }

        foreach ($this->cockpit?->crisisStaff ?? [] as $member) {
            $main = $member['main'] ?? null;
            if ($main !== null && self::normalizeRole($member['role_label']) === $needle) {
                return $main->fullName();
            }
        }

        foreach ($this->serviceProviders as $provider) {
            $candidates = array_filter([
                $provider->name,
                $provider->type?->label(),
                $provider->contact_name,
            ]);
            foreach ($candidates as $candidate) {
                if (self::normalizeRole($candidate) === $needle) {
                    return $provider->contact_name
                        ? $provider->name.' · '.$provider->contact_name
                        : $provider->name;
                }
            }
        }

        return null;
    }

    /**
     * Normalisiert eine Rollen-/Verantwortlich-Bezeichnung auf einen Stamm:
     * Kleinbuchstaben, Gender-Suffix nach „/" entfernt, nur Buchstaben/Ziffern,
     * gängige Endungen (er/in/e/r) abgeschnitten – damit „Notfallbeauftragter"
     * und „Notfallbeauftragte/r" zusammenfinden.
     */
    private static function normalizeRole(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $s = mb_strtolower(trim($value));
        $s = (string) preg_replace('#/.*$#u', '', $s);
        $s = (string) preg_replace('/[^a-z0-9äöüß]/u', '', $s);

        foreach (['erin', 'er', 'in', 'e', 'r'] as $suffix) {
            if (str_ends_with($s, $suffix) && mb_strlen($s) - mb_strlen($suffix) >= 5) {
                return mb_substr($s, 0, mb_strlen($s) - mb_strlen($suffix));
            }
        }

        return $s;
    }

    /**
     * Formatiert eine Minutenanzahl als „4h 0m" / „45m".
     */
    public function formatRto(?int $minutes): string
    {
        if ($minutes === null) {
            return '–';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours === 0) {
            return $mins.'m';
        }

        return $hours.'h '.$mins.'m';
    }

    /**
     * Farbe für die Notfall-Level-Badge nach sort-Reihenfolge (1 = höchster Schweregrad).
     */
    public function levelBadgeColor(?int $sort): string
    {
        return match ($sort) {
            1 => 'red',
            2 => 'amber',
            3 => 'sky',
            4 => 'emerald',
            default => 'zinc',
        };
    }
}; ?>

<section class="w-full">
    @if (! $this->company)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @elseif (! $this->isEnabled)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Live-Inzident-Modus deaktiviert') }}</flux:heading>
            <flux:subheading>
                {{ __('Aktivieren Sie den Modus in den Systemeinstellungen, um im Ernstfall ein reduziertes Krisen-Cockpit zu sehen.') }}
            </flux:subheading>
            <flux:button class="mt-4" :href="route('system-settings.index')" wire:navigate icon="cog-8-tooth">
                {{ __('Zu den Einstellungen') }}
            </flux:button>
        </div>
    @else
        @if (! $this->cockpit->hasActiveRun())
            <div class="space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <flux:heading size="xl">{{ __('Krisen-Cockpit') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Reduzierte Sicht für den Ernstfall – Krisenstab, Wiederanlauf-Reihenfolge, Schritte und Meldepflichten.') }}
                        </flux:subheading>
                    </div>
                    <x-manual-help slug="krisen-cockpit" />
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <div class="flex items-start gap-3">
                        <flux:icon.shield-check class="mt-0.5 h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        <div>
                            <flux:heading size="lg" class="text-emerald-900 dark:text-emerald-100">{{ __('Kein aktiver Notfall') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-emerald-800 dark:text-emerald-200">
                                {{ __('Wenn ein Szenario gestartet wird (z. B. von der Szenarien-Seite), öffnet sich hier automatisch das Krisen-Cockpit.') }}
                            </flux:text>
                            <flux:button class="mt-4" variant="ghost" :href="route('scenarios.index')" wire:navigate>
                                {{ __('Szenarien öffnen') }}
                            </flux:button>
                        </div>
                    </div>
                </div>

                @if ($peaceVerse)
                    <figure class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:icon name="book-open" class="h-5 w-5 text-zinc-400" />
                        <blockquote class="mt-3 text-base italic leading-relaxed text-zinc-700 dark:text-zinc-200">
                            „{{ $peaceVerse['text'] }}"
                        </blockquote>
                        <figcaption class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            — {{ $peaceVerse['reference'] }}
                        </figcaption>
                    </figure>
                @endif
            </div>
        @else
            @php
                $cockpit = $this->cockpit;
                $run = $cockpit->activeRun;
                $scenarioName = $run->scenario?->name ?? $run->title ?? '–';
                $trigger = $run->scenario?->trigger ?? $run->title ?? null;
                $startedAtIso = $run->started_at?->toIso8601String();
            @endphp

            <div class="space-y-6" x-data="{ cockpitTab: 'recovery' }">
                {{-- Umschalter bei mehreren parallel aktiven Notfällen --}}
                @if ($cockpit->activeRuns->count() > 1)
                    <div class="rounded-xl border border-amber-300 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/40">
                        <flux:text class="px-1 text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-200">
                            {{ __(':count Notfälle gleichzeitig aktiv — Lagebild wählen', ['count' => $cockpit->activeRuns->count()]) }}
                        </flux:text>
                        <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($cockpit->activeRuns as $candidate)
                                @php
                                    $isSelected = $candidate->id === $run->id;
                                @endphp
                                <button
                                    type="button"
                                    wire:key="run-switch-{{ $candidate->id }}"
                                    wire:click="selectRun('{{ $candidate->id }}')"
                                    class="{{ $isSelected
                                        ? 'border-rose-600 bg-rose-600 text-white'
                                        : 'border-amber-300 bg-white text-zinc-800 hover:border-rose-400 dark:border-amber-800 dark:bg-zinc-900 dark:text-zinc-100' }} flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-left text-sm transition"
                                >
                                    <span class="font-medium">{{ $candidate->scenario?->name ?? $candidate->title ?? '–' }}</span>
                                    @if ($candidate->isDrill())
                                        <span class="{{ $isSelected ? 'bg-white/20 text-white' : 'bg-amber-500 text-white' }} rounded-full px-1.5 py-0.5 text-[10px] font-bold">
                                            {{ __('ÜBUNG') }}
                                        </span>
                                    @endif
                                    <span class="{{ $isSelected ? 'text-rose-100' : 'text-zinc-500 dark:text-zinc-400' }} text-xs">
                                        {{ $candidate->started_at?->format('H:i') }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Sektion 1: Lage-Header (sticky) --}}
                <div
                    class="sticky top-0 z-30 -mx-4 rounded-xl border-l-4 border border-l-rose-500 border-rose-200 bg-rose-50 px-6 py-5 text-rose-950 shadow-sm dark:border-rose-900 dark:border-l-rose-500 dark:bg-rose-950/40 dark:text-rose-50 sm:mx-0"
                    x-data="{
                        startedAt: @js($startedAtIso),
                        elapsed: '00:00',
                        tick() {
                            if (! this.startedAt) { this.elapsed = '–'; return; }
                            const start = new Date(this.startedAt).getTime();
                            const diff = Math.max(0, Math.floor((Date.now() - start) / 1000));
                            const h = Math.floor(diff / 3600);
                            const m = Math.floor((diff % 3600) / 60);
                            const s = diff % 60;
                            const pad = (n) => n.toString().padStart(2, '0');
                            this.elapsed = h > 0
                                ? `${pad(h)}:${pad(m)}:${pad(s)}`
                                : `${pad(m)}:${pad(s)}`;
                        }
                    }"
                    x-init="tick(); setInterval(() => tick(), 1000)"
                    data-test="cockpit-header"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 text-2xl font-bold leading-tight">
                                <flux:icon.exclamation-triangle class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                                <span>{{ __('Aktiver Notfall:') }}</span>
                                <span class="truncate" data-test="cockpit-scenario-name">{{ $scenarioName }}</span>
                            </div>
                            @if ($trigger && $trigger !== $scenarioName)
                                <p class="mt-1 text-sm text-rose-900/80 dark:text-rose-100/80">
                                    <span class="font-semibold">{{ __('Auslöser:') }}</span>
                                    {{ $trigger }}
                                </p>
                            @endif
                            <p class="mt-1 text-sm text-rose-900/80 dark:text-rose-100/80">
                                <span class="font-semibold">{{ __('Ausgelöst von:') }}</span>
                                @if ($run->source === 'monitoring')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-200/70 px-2 py-0.5 text-xs font-semibold text-rose-900 dark:bg-rose-900/60 dark:text-rose-100">
                                        <flux:icon.cpu-chip class="h-3.5 w-3.5" />
                                        {{ __('Automatisch · IT-Monitoring') }}
                                    </span>
                                    @if ($run->trigger_detail)
                                        <span class="font-mono text-xs">{{ $run->trigger_detail }}</span>
                                    @endif
                                @else
                                    {{ $run->startedBy?->name ?? __('Unbekannt') }}
                                @endif
                                @if ($run->started_at)
                                    <span class="text-rose-900/80 dark:text-rose-100/80">·</span>
                                    {{ $run->started_at->isoFormat('DD.MM.YYYY HH:mm') }}
                                @endif
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm">
                                <flux:icon.clock class="h-4 w-4" />
                                <span>{{ __('Laufzeit:') }}</span>
                                <span class="font-mono text-base font-bold tabular-nums" x-text="elapsed">00:00</span>
                            </p>
                        </div>

                        <div class="shrink-0">
                            <flux:button
                                type="button"
                                variant="danger"
                                wire:click="endRun"
                                wire:confirm="{{ __('Szenario wirklich beenden?') }}"
                                icon="x-circle"
                                data-test="cockpit-end-run"
                            >
                                {{ __('Szenario beenden') }}
                            </flux:button>
                        </div>
                    </div>
                </div>

                {{-- Umschalter: Wiederanlauf ↔ FORDEC-Entscheidung (weitere Bereiche später) --}}
                <div class="flex flex-wrap gap-2 border-b border-zinc-200 pb-1 dark:border-zinc-700">
                    <button
                        type="button"
                        x-on:click="cockpitTab = 'recovery'"
                        :class="cockpitTab === 'recovery' ? 'border-indigo-600 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'"
                        class="inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition"
                    >
                        <flux:icon.arrow-path class="h-4 w-4" />
                        {{ __('Wiederanlauf') }}
                    </button>
                    <button
                        type="button"
                        x-on:click="cockpitTab = 'fordec'"
                        :class="cockpitTab === 'fordec' ? 'border-indigo-600 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'"
                        class="inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition"
                        data-test="cockpit-tab-fordec"
                    >
                        <flux:icon.scale class="h-4 w-4" />
                        {{ __('FORDEC-Entscheidung') }}
                    </button>
                </div>

                {{-- Tab „Wiederanlauf": Krisenstab, Schritte, Kommunikation, Meldepflichten, Logbuch --}}
                <div x-show="cockpitTab === 'recovery'" class="space-y-6">
                {{-- Sektion 2: Krisenstab --}}
                @php
                    $smsConfigured = app(\App\Services\Sms\SmsGatewayContract::class)->isConfigured();
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <flux:icon.users class="h-5 w-5 text-zinc-500" />
                            <flux:heading size="lg">{{ __('Krisenstab') }}</flux:heading>
                        </div>
                        @if ($smsConfigured)
                            <flux:button
                                type="button"
                                variant="primary"
                                size="sm"
                                icon="bell-alert"
                                wire:click="alertCrisisStaff"
                                wire:confirm="{{ __('Krisenstab jetzt per SMS alarmieren?') }}"
                                data-test="cockpit-alert-crisis-staff"
                            >
                                {{ __('Krisenstab alarmieren') }}
                            </flux:button>
                        @endif
                    </div>

                    @if (count($cockpit->crisisStaff) === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Kein Krisenstab hinterlegt.') }}
                        </flux:text>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5" data-test="cockpit-crisis-staff">
                            @foreach ($cockpit->crisisStaff as $member)
                                @php
                                    $main = $member['main'] ?? null;
                                    $deputies = $member['deputies'] ?? collect();
                                @endphp
                                <div class="flex flex-col rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                                    <flux:badge color="zinc" size="sm" class="self-start">
                                        {{ $member['role_label'] ?? '' }}
                                    </flux:badge>

                                    @if ($main)
                                        <div class="mt-3">
                                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ $main->fullName() }}
                                            </div>
                                            @if ($main->position)
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $main->position }}</div>
                                            @endif
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @if ($main->mobile_phone)
                                                    <flux:button size="xs" variant="filled" icon="device-phone-mobile"
                                                        href="tel:{{ \App\Support\PhoneFormat::tel($main->mobile_phone) }}">
                                                        {{ __('Mobil') }}
                                                    </flux:button>
                                                @endif
                                                @if ($main->work_phone)
                                                    <flux:button size="xs" variant="filled" icon="phone"
                                                        href="tel:{{ \App\Support\PhoneFormat::tel($main->work_phone) }}">
                                                        {{ __('Festnetz') }}
                                                    </flux:button>
                                                @endif
                                                @if ($main->email)
                                                    <flux:button size="xs" variant="filled" icon="envelope"
                                                        href="mailto:{{ $main->email }}">
                                                        {{ __('E-Mail') }}
                                                    </flux:button>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-3 rounded border border-rose-200 bg-rose-50 px-2 py-1.5 text-xs text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                                            {{ __('Keine Hauptperson hinterlegt') }}
                                        </div>
                                    @endif

                                    @if ($deputies->isNotEmpty())
                                        <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                                {{ __('Vertretung') }}
                                            </div>
                                            <div class="space-y-2">
                                                @foreach ($deputies as $deputy)
                                                    <div class="text-xs">
                                                        <div class="font-medium text-zinc-700 dark:text-zinc-200">
                                                            {{ $deputy->fullName() }}
                                                        </div>
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            @if ($deputy->mobile_phone)
                                                                <flux:button size="xs" variant="ghost" icon="device-phone-mobile"
                                                                    href="tel:{{ \App\Support\PhoneFormat::tel($deputy->mobile_phone) }}">
                                                                    {{ __('Mobil') }}
                                                                </flux:button>
                                                            @endif
                                                            @if ($deputy->work_phone)
                                                                <flux:button size="xs" variant="ghost" icon="phone"
                                                                    href="tel:{{ \App\Support\PhoneFormat::tel($deputy->work_phone) }}">
                                                                    {{ __('Tel.') }}
                                                                </flux:button>
                                                            @endif
                                                            @if ($deputy->email)
                                                                <flux:button size="xs" variant="ghost" icon="envelope"
                                                                    href="mailto:{{ $deputy->email }}">
                                                                    {{ __('Mail') }}
                                                                </flux:button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Sektion 3: Wiederanlauf-Reihenfolge --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.arrow-path class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Wiederanlauf-Reihenfolge') }}</flux:heading>
                    </div>

                    @if (count($cockpit->recoveryOrder) === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Systeme hinterlegt.') }}
                        </flux:text>
                    @else
                        <div class="overflow-x-auto" data-test="cockpit-recovery-list">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        <th class="py-2 pl-3 pr-3 font-semibold">{{ __('Notfall-Level') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('System') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('RTO') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('Frist') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('Aufgaben') }}</th>
                                        <th class="py-2 pr-3 font-semibold sr-only">{{ __('Aktion') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($cockpit->recoveryOrder as $item)
                                        @php
                                            $sys = $item['system'];
                                            $deadline = $item['deadline_at'] ?? null;
                                            $deadlineIso = $deadline?->toIso8601String();
                                            $deadlineMissed = $deadline !== null && $deadline->isPast();
                                            $badgeColor = $this->levelBadgeColor($item['level_sort'] ?? null);
                                            $depthIndent = max(0, ($item['depth'] ?? 0)) * 12;
                                        @endphp
                                        <tr class="align-top {{ $deadlineMissed ? 'bg-rose-50 dark:bg-rose-950/30' : '' }}">
                                            <td class="py-[17px] pl-3 pr-3">
                                                <flux:badge color="{{ $badgeColor }}" size="sm">
                                                    {{ $item['level_name'] ?? __('—') }}
                                                </flux:badge>
                                            </td>
                                            <td class="py-[17px] pr-3">
                                                <div style="padding-left: {{ $depthIndent }}px" class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $sys->name }}
                                                </div>
                                            </td>
                                            <td class="py-[17px] pr-3 font-mono tabular-nums text-zinc-700 dark:text-zinc-200">
                                                {{ $this->formatRto($item['rto_minutes'] ?? null) }}
                                            </td>
                                            <td class="py-[17px] pr-3">
                                                @if ($deadline)
                                                    <span
                                                        class="inline-flex items-center gap-1.5 font-mono text-xs tabular-nums {{ $deadlineMissed ? 'animate-pulse rounded bg-rose-100 px-1.5 py-0.5 text-rose-800 dark:bg-rose-900/60 dark:text-rose-100' : 'text-zinc-700 dark:text-zinc-200' }}"
                                                        x-data="{
                                                            deadline: @js($deadlineIso),
                                                            display: '–',
                                                            tick() {
                                                                if (! this.deadline) { this.display = '–'; return; }
                                                                const target = new Date(this.deadline).getTime();
                                                                let diff = Math.floor((target - Date.now()) / 1000);
                                                                const sign = diff < 0 ? '-' : '';
                                                                diff = Math.abs(diff);
                                                                const h = Math.floor(diff / 3600);
                                                                const m = Math.floor((diff % 3600) / 60);
                                                                const s = diff % 60;
                                                                const pad = (n) => n.toString().padStart(2, '0');
                                                                if (diff < 3600) {
                                                                    this.display = `${sign}${pad(m)}:${pad(s)}`;
                                                                } else {
                                                                    this.display = `${sign}${pad(h)}:${pad(m)}`;
                                                                }
                                                            }
                                                        }"
                                                        x-init="tick(); setInterval(() => tick(), 30000)"
                                                        x-text="display"
                                                    >–</span>
                                                @else
                                                    <span class="text-zinc-400">—</span>
                                                @endif
                                            </td>
                                            <td class="py-[17px] pr-3">
                                                @php
                                                    $open = (int) ($item['open_tasks'] ?? 0);
                                                    $total = (int) ($item['total_tasks'] ?? 0);
                                                    $taskBadge = $open === 0 ? 'emerald' : ($open >= ($total === 0 ? 1 : $total) ? 'red' : 'amber');
                                                @endphp
                                                <flux:badge color="{{ $taskBadge }}" size="sm">
                                                    {{ $open }}/{{ $total }}
                                                </flux:badge>
                                            </td>
                                            <td class="py-[17px] pr-3 text-right">
                                                <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                                                    :href="route('systems.show', ['system' => $sys->id])" wire:navigate>
                                                    {{ __('Öffnen') }}
                                                </flux:button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Sektion 3b: Aktuell laufender Schaden --}}
                @php
                    $damageRate = (int) $cockpit->damageRatePerHourEur;
                    $damageRateFormatted = number_format($damageRate, 0, ',', '.');
                    $topDamageSystems = array_slice($cockpit->damageRatePerSystem, 0, 5);
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" data-test="cockpit-damage">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.banknotes class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Aktuell laufender Schaden') }}</flux:heading>
                    </div>

                    @if ($damageRate === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Ausfallkosten je Stunde an Systemen hinterlegt.') }}
                        </flux:text>
                        <flux:button class="mt-3" size="xs" variant="ghost" :href="route('systems.index')" wire:navigate icon="arrow-top-right-on-square">
                            {{ __('Zu den Systemen') }}
                        </flux:button>
                    @else
                        <div
                            x-data="{
                                startedAt: @js($startedAtIso),
                                ratePerHour: {{ $damageRate }},
                                accumulated: '0',
                                tick() {
                                    if (! this.startedAt) { this.accumulated = '0'; return; }
                                    const start = new Date(this.startedAt).getTime();
                                    const elapsedSeconds = Math.max(0, (Date.now() - start) / 1000);
                                    const ratePerSecond = this.ratePerHour / 3600;
                                    const value = Math.floor(elapsedSeconds * ratePerSecond);
                                    this.accumulated = value.toLocaleString('de-DE');
                                }
                            }"
                            x-init="tick(); setInterval(() => tick(), 1000)"
                        >
                            <div class="font-mono text-4xl font-bold tabular-nums text-rose-600 dark:text-rose-400">
                                <span x-text="accumulated" data-test="cockpit-damage-counter">0</span>
                                <span> €</span>
                            </div>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('aktuell') }} <span class="font-semibold">{{ $damageRateFormatted }} €/h</span> &times; {{ __('Laufzeit') }}
                            </flux:text>
                        </div>

                        @if (count($topDamageSystems) > 0)
                            <div class="mt-5">
                                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    {{ __('Top 5 Systeme nach Stundenrate') }}
                                </div>
                                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" data-test="cockpit-damage-top">
                                    @foreach ($topDamageSystems as $entry)
                                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                            <span class="truncate font-medium text-zinc-800 dark:text-zinc-100">
                                                {{ $entry['system_name'] }}
                                            </span>
                                            <span class="font-mono tabular-nums text-zinc-700 dark:text-zinc-200">
                                                {{ number_format($entry['hourly'], 0, ',', '.') }} €/h
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Sektion 4: Schritte abhaken --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.list-bullet class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Schritte') }}</flux:heading>
                    </div>

                    @if ($cockpit->steps->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Schritte für diesen Lauf hinterlegt.') }}
                        </flux:text>
                    @else
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" data-test="cockpit-steps">
                            @foreach ($cockpit->steps as $step)
                                @php
                                    $checked = $step->checked_at !== null;
                                @endphp
                                <li class="flex items-start gap-3 py-3 {{ $checked ? 'opacity-60' : '' }}">
                                    <button
                                        type="button"
                                        wire:click="toggleStep('{{ $step->id }}')"
                                        class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded border {{ $checked ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-zinc-300 bg-white hover:border-rose-400 dark:border-zinc-600 dark:bg-zinc-800' }}"
                                        aria-label="{{ $checked ? __('Schritt rückgängig machen') : __('Schritt abhaken') }}"
                                        data-test="cockpit-step-toggle"
                                    >
                                        @if ($checked)
                                            <flux:icon.check class="h-4 w-4" />
                                        @endif
                                    </button>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <div class="font-medium {{ $checked ? 'text-zinc-500 line-through dark:text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                                {{ $step->title }}
                                            </div>
                                            @if ($step->responsible)
                                                @php $responsibleName = $this->resolveResponsible($step->responsible); @endphp
                                                <div class="shrink-0 text-right">
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ $step->responsible }}
                                                    </div>
                                                    @if ($responsibleName)
                                                        <div class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                                                            {{ $responsibleName }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        @if ($step->description)
                                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                                {{ $step->description }}
                                            </div>
                                        @endif
                                        @if ($checked && $step->checked_at)
                                            <div class="mt-1 text-xs text-zinc-400">
                                                {{ __('Erledigt von') }}
                                                <span class="font-medium text-zinc-500 dark:text-zinc-300">{{ $step->checkedBy?->name ?? __('Unbekannt') }}</span>
                                                · {{ $step->checked_at->isoFormat('DD.MM.YYYY HH:mm') }}
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Sektion 5: Kommunikation --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <flux:icon.megaphone class="h-5 w-5 text-zinc-500" />
                            <flux:heading size="lg">{{ __('Kommunikation') }}</flux:heading>
                        </div>
                        <x-manual-help slug="kommunikations-vorlagen" />
                    </div>

                    @if ($cockpit->communicationTemplates->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Vorlagen hinterlegt.') }}
                        </flux:text>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-test="cockpit-communication">
                            @foreach ($cockpit->communicationTemplates as $tpl)
                                <div class="flex flex-col rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                                    <div class="flex flex-wrap gap-1.5">
                                        <flux:badge color="indigo" size="sm">
                                            {{ $tpl->audience?->label() ?? '—' }}
                                        </flux:badge>
                                        <flux:badge color="zinc" size="sm" :icon="$tpl->channel?->icon()">
                                            {{ $tpl->channel?->label() ?? '—' }}
                                        </flux:badge>
                                    </div>
                                    @if ($tpl->subject)
                                        <div class="mt-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $tpl->subject }}
                                        </div>
                                    @else
                                        <div class="mt-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $tpl->name }}
                                        </div>
                                    @endif
                                    <p class="mt-2 line-clamp-3 text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $tpl->body }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        <flux:button type="button" size="xs" variant="primary" icon="document-text"
                                            wire:click="openTemplate('{{ $tpl->id }}')">
                                            {{ __('Vorlage öffnen') }}
                                        </flux:button>
                                        @if ($tpl->channel === CommunicationChannel::Sms)
                                            <flux:button type="button" size="xs" variant="ghost" icon="device-phone-mobile"
                                                wire:click="openSmsSend('{{ $tpl->id }}')">
                                                {{ __('Per SMS senden') }}
                                            </flux:button>
                                        @elseif ($tpl->channel === CommunicationChannel::Email)
                                            <flux:button type="button" size="xs" variant="ghost" icon="envelope"
                                                wire:click="openEmailSend('{{ $tpl->id }}')">
                                                {{ __('Per E-Mail senden') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Sektion 6: Meldepflichten --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.shield-exclamation class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Meldepflichten') }}</flux:heading>
                    </div>

                    @if (count($cockpit->obligations) === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine offenen Meldepflichten') }}
                        </flux:text>
                    @else
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" data-test="cockpit-obligations">
                            @foreach ($cockpit->obligations as $entry)
                                @php
                                    $deadline = $entry['deadline_at'] ?? null;
                                    $deadlineIso = $deadline?->toIso8601String();
                                    $reported = (bool) ($entry['reported'] ?? false);
                                    $missed = $deadline !== null && $deadline->isPast() && ! $reported;
                                @endphp
                                <li class="flex items-start justify-between gap-3 py-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $entry['label'] ?? '—' }}
                                        </div>
                                        @if ($deadline)
                                            <div
                                                class="mt-1 text-xs"
                                                x-data="{
                                                    deadline: @js($deadlineIso),
                                                    display: '–',
                                                    tick() {
                                                        if (! this.deadline) { this.display = '–'; return; }
                                                        const target = new Date(this.deadline).getTime();
                                                        let diff = Math.floor((target - Date.now()) / 1000);
                                                        const sign = diff < 0 ? '-' : '';
                                                        diff = Math.abs(diff);
                                                        const h = Math.floor(diff / 3600);
                                                        const m = Math.floor((diff % 3600) / 60);
                                                        const pad = (n) => n.toString().padStart(2, '0');
                                                        this.display = `${sign}${pad(h)}:${pad(m)}`;
                                                    }
                                                }"
                                                x-init="tick(); setInterval(() => tick(), 30000)"
                                            >
                                                <span class="font-semibold">{{ __('Frist:') }}</span>
                                                <span class="font-mono tabular-nums" x-text="display">–</span>
                                                <span class="text-zinc-400">·</span>
                                                <span>{{ $deadline->isoFormat('DD.MM.YYYY HH:mm') }}</span>
                                            </div>
                                        @endif
                                        @if ($missed)
                                            <div class="mt-1 text-xs font-semibold text-rose-700 dark:text-rose-300">
                                                {{ __('Frist abgelaufen — bitte sofort melden!') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="shrink-0">
                                        @if ($reported)
                                            <flux:badge color="emerald" size="sm" icon="check-circle">
                                                {{ __('Gemeldet') }}
                                            </flux:badge>
                                        @else
                                            <flux:badge color="{{ $missed ? 'red' : 'amber' }}" size="sm">
                                                {{ __('Offen') }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Sektion 7: Krisen-Logbuch --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" data-test="cockpit-log">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <flux:icon.book-open class="h-5 w-5 text-zinc-500" />
                            <flux:heading size="lg">{{ __('Krisen-Logbuch') }}</flux:heading>
                        </div>
                        <flux:button
                            type="button"
                            size="sm"
                            variant="ghost"
                            icon="document-arrow-down"
                            :href="route('scenario-runs.protocol.pdf', ['run' => $run])"
                            target="_blank"
                            data-test="cockpit-log-pdf"
                        >
                            {{ __('Protokoll als PDF') }}
                        </flux:button>
                    </div>

                    {{-- Eingabeformular für manuelle Einträge --}}
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <flux:select wire:model="newLogType" :label="__('Typ')" class="sm:w-48">
                            <flux:select.option value="note">{{ __('Notiz') }}</flux:select.option>
                            <flux:select.option value="decision">{{ __('Entscheidung') }}</flux:select.option>
                            <flux:select.option value="action">{{ __('Maßnahme') }}</flux:select.option>
                        </flux:select>
                        <flux:input
                            wire:model="newLogMessage"
                            :label="__('Eintrag')"
                            :placeholder="__('Was wurde entschieden oder getan?')"
                            class="flex-1"
                            wire:keydown.enter="addLogEntry"
                            data-test="cockpit-log-input"
                        />
                        <flux:button type="button" variant="primary" icon="plus" wire:click="addLogEntry" data-test="cockpit-log-add">
                            {{ __('Hinzufügen') }}
                        </flux:button>
                    </div>

                    @if ($this->logEntries->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Einträge im Krisen-Logbuch.') }}
                        </flux:text>
                    @else
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" data-test="cockpit-log-entries">
                            @foreach ($this->logEntries as $entry)
                                <li class="flex items-start gap-3 py-3">
                                    <flux:badge color="{{ $this->logTypeBadgeColor((string) $entry->type) }}" size="sm" class="mt-0.5 shrink-0">
                                        {{ $this->logTypeLabel((string) $entry->type) }}
                                    </flux:badge>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $entry->message }}
                                        </div>
                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $entry->occurred_at?->isoFormat('DD.MM.YYYY HH:mm') }}
                                            @if ($entry->user)
                                                · {{ $entry->user->name }}
                                            @endif
                                            @if ($entry->source)
                                                · <span class="font-medium">{{ $this->logSourceLabel($entry->source) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Sektion 8: Koordination — freier Lagemeldungs-Chat, live via Reverb (derselbe Strom wie in der App). --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" data-test="cockpit-coordination">
                    <div class="mb-1 flex items-center gap-2">
                        <flux:icon.chat-bubble-left-ellipsis class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Koordination') }}</flux:heading>
                    </div>
                    <flux:subheading class="mb-4 text-xs">{{ __('Kurze Lagemeldungen für alle, die an diesem Notfall arbeiten — App und Cockpit sehen sich gegenseitig in Echtzeit (zusätzlich zum Krisen-Logbuch).') }}</flux:subheading>

                    <div class="mb-4 max-h-72 space-y-2 overflow-y-auto" data-test="cockpit-coordination-messages">
                        @forelse ($this->coordinationMessages as $message)
                            <div wire:key="coord-{{ $message->id }}" class="rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-950/40">
                                <div class="whitespace-pre-line text-zinc-800 dark:text-zinc-200">{{ $message->body }}</div>
                                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $message->author_name ?? $message->user?->name ?? __('System') }} · {{ $message->created_at?->isoFormat('DD.MM.YYYY HH:mm') }} {{ __('Uhr') }}
                                </div>
                            </div>
                        @empty
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Lagemeldungen.') }}</flux:text>
                        @endforelse
                    </div>

                    <form wire:submit="postCoordinationMessage" class="flex items-end gap-2">
                        <flux:textarea wire:model="newCoordinationMessage" rows="1" class="flex-1" :placeholder="__('Lagemeldung schreiben… (z. B. „Feuerwehr eingetroffen“)')" data-test="cockpit-coordination-input" />
                        <flux:button type="submit" variant="primary" icon="paper-airplane" data-test="cockpit-coordination-send">{{ __('Senden') }}</flux:button>
                    </form>
                </div>
            </div>

                </div>{{-- Ende Tab „Wiederanlauf" --}}

                {{-- Tab „FORDEC-Entscheidung": strukturierte, nachvollziehbare Krisenentscheidung --}}
                <div x-show="cockpitTab === 'fordec'" x-cloak class="space-y-6">
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-1 flex items-center gap-2">
                            <flux:icon.scale class="h-5 w-5 text-zinc-500" />
                            <flux:heading size="lg">{{ __('FORDEC-Entscheidung') }}</flux:heading>
                        </div>
                        <flux:subheading class="mb-4">
                            {{ __('Strukturierte Krisenentscheidung in sechs Schritten. Wird revisionssicher gespeichert und ins Krisen-Logbuch übernommen.') }}
                        </flux:subheading>

                        <form wire:submit="saveFordec" class="space-y-4">
                            <flux:input wire:model="fordecTitle" :label="__('Kurztitel (optional)')" type="text" placeholder="z. B. Verlagerung in den Ausweichstandort" />
                            <flux:textarea wire:model="fordecFacts" :label="__('Facts — Was wissen wir sicher?')" rows="2" placeholder="Gesicherte Fakten, Status, betroffene Systeme/Standorte." />
                            <flux:textarea wire:model="fordecOptions" :label="__('Options — Welche Handlungsoptionen gibt es?')" rows="2" placeholder="Mögliche Handlungswege, auch die Option „nichts tun / abwarten“." />
                            <flux:textarea wire:model="fordecRisksBenefits" :label="__('Risks &amp; Benefits — Risiken und Vorteile der Optionen')" rows="2" placeholder="Pro Option: was spricht dafür, was dagegen, welche Nebenwirkungen?" />
                            <flux:textarea wire:model="fordecDecision" :label="__('Decision — Was wurde entschieden?')" rows="2" placeholder="Die getroffene Entscheidung, klar und eindeutig." required />
                            <flux:textarea wire:model="fordecExecution" :label="__('Execution — Wer macht was bis wann?')" rows="2" placeholder="Verantwortliche, Aufgaben, Fristen." />
                            <flux:input wire:model="fordecCheckAt" :label="__('Check — Wann prüfen wir die Entscheidung erneut?')" type="datetime-local" />

                            <div class="flex justify-end">
                                <flux:button variant="primary" type="submit" icon="check" data-test="cockpit-fordec-save">
                                    {{ __('Entscheidung dokumentieren') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading size="lg" class="mb-4">{{ __('Dokumentierte Entscheidungen') }}</flux:heading>
                        @forelse ($this->fordecDecisions as $decision)
                            <div wire:key="fordec-{{ $decision->id }}" class="border-t border-zinc-100 py-4 first:border-t-0 first:pt-0 dark:border-zinc-800">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <flux:heading size="base">{{ $decision->title ?: __('FORDEC-Entscheidung') }}</flux:heading>
                                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span>{{ $decision->created_at->format('d.m.Y H:i') }}</span>
                                        @if ($decision->created_by_name)<span>· {{ $decision->created_by_name }}</span>@endif
                                    </div>
                                </div>
                                <dl class="mt-2 space-y-1.5 text-sm">
                                    @foreach ([
                                        __('Facts') => $decision->facts,
                                        __('Options') => $decision->options,
                                        __('Risks & Benefits') => $decision->risks_benefits,
                                        __('Decision') => $decision->decision,
                                        __('Execution') => $decision->execution,
                                    ] as $label => $value)
                                        @if ($value)
                                            <div class="grid grid-cols-[9rem_1fr] gap-2">
                                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                                                <dd class="text-zinc-800 dark:text-zinc-200 whitespace-pre-line">{{ $value }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                    @if ($decision->check_at)
                                        <div class="grid grid-cols-[9rem_1fr] gap-2">
                                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Check') }}</dt>
                                            <dd class="text-zinc-800 dark:text-zinc-200">{{ $decision->check_at->format('d.m.Y H:i') }} {{ __('Uhr') }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @empty
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Noch keine FORDEC-Entscheidung dokumentiert.') }}
                            </flux:text>
                        @endforelse
                    </div>
                </div>{{-- Ende Tab „FORDEC-Entscheidung" --}}

            @include('partials.communication-send-modals')

            <flux:modal name="cockpit-template-preview" class="max-w-2xl">
                @php
                    $preview = $this->previewTemplate;
                @endphp
                @if ($preview)
                    <div class="space-y-5" x-data="{ copied: false, copy(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
                        <div>
                            <flux:heading size="lg">{{ $preview->subject ?: $preview->name }}</flux:heading>
                            <flux:subheading>
                                {{ $preview->audience?->label() }} · {{ $preview->channel?->label() }}
                            </flux:subheading>
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800/50">
                            <pre class="whitespace-pre-wrap font-sans text-zinc-800 dark:text-zinc-100">{{ $preview->body }}</pre>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <flux:button type="button" variant="ghost" icon="clipboard"
                                x-on:click="copy(@js($preview->body))">
                                <span x-show="! copied">{{ __('Kopieren') }}</span>
                                <span x-show="copied" x-cloak>{{ __('Kopiert!') }}</span>
                            </flux:button>
                            <flux:modal.close>
                                <flux:button variant="filled" type="button">{{ __('Schließen') }}</flux:button>
                            </flux:modal.close>
                        </div>
                    </div>
                @endif
            </flux:modal>

            @if ($crisisVerse)
                <figure class="mt-6 rounded-xl border border-rose-200 bg-rose-50/60 p-6 dark:border-rose-900 dark:bg-rose-950/20">
                    <flux:icon name="book-open" class="h-5 w-5 text-rose-500 dark:text-rose-400" />
                    <blockquote class="mt-3 text-base italic leading-relaxed text-rose-900 dark:text-rose-100">
                        „{{ $crisisVerse['text'] }}"
                    </blockquote>
                    <figcaption class="mt-2 text-sm text-rose-700 dark:text-rose-300">
                        — {{ $crisisVerse['reference'] }}
                    </figcaption>
                </figure>
            @endif
        @endif
    @endif
</section>
