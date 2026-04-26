<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Scopes\CurrentCompanyScope;
use App\Support\HandbookPdfGenerator;
use App\Support\Settings\CompanySetting;
use Database\Factories\HandbookVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable([
    'company_id',
    'version',
    'changed_at',
    'changed_by_employee_id',
    'change_reason',
    'approved_at',
    'approved_by_employee_id',
    'approved_by_name',
    'pdf_path',
    'pdf_hash',
    'pdf_size',
    'pdf_generated_at',
])]
class HandbookVersion extends Model
{
    /** @use HasFactory<HandbookVersionFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return 'Version '.$this->version;
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'changed_by_employee_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_employee_id');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function hasPdf(): bool
    {
        return $this->pdf_path !== null;
    }

    /**
     * Eloquent-Lifecycle-Hooks:
     *  - deleted: räumt das hinterlegte PDF auf der privaten Disk auf
     *    (wirkt nur bei expliziten Eloquent-Deletes; DB-Cascades vom Parent
     *    lösen keine Eloquent-Events aus).
     *  - created: erzeugt ein revisionssicheres PDF, wenn der Mandant
     *    `auto_pdf_enabled` gesetzt hat. Fehler werden geschluckt – die
     *    manuelle Freigabe bleibt verfügbar.
     */
    protected static function booted(): void
    {
        static::deleted(function (self $version) {
            if ($version->pdf_path) {
                Storage::disk('handbook')->delete($version->pdf_path);
            }
        });

        static::created(function (self $version) {
            $company = $version->company()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->first();
            if ($company === null) {
                return;
            }

            if (! CompanySetting::for($company)->get('auto_pdf_enabled', false)) {
                return;
            }

            if ($version->hasPdf()) {
                return;
            }

            try {
                HandbookPdfGenerator::generate($version);
            } catch (Throwable) {
                // best-effort; manuelle Freigabe bleibt möglich
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'date',
            'approved_at' => 'date',
            'pdf_generated_at' => 'datetime',
            'pdf_size' => 'integer',
        ];
    }
}
