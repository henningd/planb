<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Enums\ScenarioRunMode;
use Database\Factories\ScenarioRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'scenario_id',
    'started_by_user_id',
    'title',
    'mode',
    'source',
    'trigger_detail',
    'started_at',
    'ended_at',
    'aborted_at',
    'escalated_at',
    'summary',
    'share_token',
    'share_token_created_at',
])]
class ScenarioRun extends Model
{
    /** @use HasFactory<ScenarioRunFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids;

    /**
     * @return BelongsTo<Scenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    /**
     * @return HasMany<ScenarioRunStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ScenarioRunStep::class)->orderBy('sort');
    }

    /**
     * @return HasMany<CrisisLogEntry, $this>
     */
    public function crisisLogEntries(): HasMany
    {
        return $this->hasMany(CrisisLogEntry::class)->orderByDesc('occurred_at');
    }

    /**
     * Alarm-Quittierungen („gesehen"/„übernehme"), max. eine je Nutzer.
     *
     * @return HasMany<ScenarioRunAcknowledgement, $this>
     */
    public function acknowledgements(): HasMany
    {
        return $this->hasMany(ScenarioRunAcknowledgement::class)->orderBy('acknowledged_at');
    }

    /**
     * Freie Koordinations-/Lagemeldungen der Bearbeitenden (Chat).
     *
     * @return HasMany<ScenarioRunMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ScenarioRunMessage::class)->orderBy('created_at');
    }

    public function isDrill(): bool
    {
        return $this->mode === ScenarioRunMode::Drill;
    }

    public function isActive(): bool
    {
        return $this->ended_at === null && $this->aborted_at === null;
    }

    /**
     * Kurzstatus der Lage für Anzeigen (auch öffentlich).
     */
    public function phaseLabel(): string
    {
        return match (true) {
            $this->aborted_at !== null => 'abgebrochen',
            $this->ended_at !== null => 'beendet',
            $this->escalated_at !== null => 'eskaliert',
            default => 'aktiv',
        };
    }

    /**
     * Fortschritt der Checkliste (erledigte / gesamte Schritte).
     *
     * @return array{done: int, total: int, percent: int}
     */
    public function progress(): array
    {
        $total = $this->steps->count();
        $done = $this->steps->whereNotNull('checked_at')->count();

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    public function isShared(): bool
    {
        return $this->share_token !== null;
    }

    /**
     * Aktiviert den öffentlichen Live-Lage-Link (Token einmalig erzeugen).
     */
    public function enableSharing(): void
    {
        if ($this->share_token === null) {
            $this->forceFill([
                'share_token' => Str::random(48),
                'share_token_created_at' => now(),
            ])->save();
        }
    }

    public function disableSharing(): void
    {
        $this->forceFill(['share_token' => null, 'share_token_created_at' => null])->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => ScenarioRunMode::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'aborted_at' => 'datetime',
            'escalated_at' => 'datetime',
            'share_token_created_at' => 'datetime',
        ];
    }
}
