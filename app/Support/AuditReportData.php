<?php

namespace App\Support;

use App\Models\AiSystem;
use App\Models\AuthorityContact;
use App\Models\BusinessProcess;
use App\Models\Company;
use App\Models\HandbookTest;
use App\Models\HandbookVersion;
use App\Models\InsurancePolicy;
use App\Models\LessonLearned;
use App\Models\ManagementReview;
use App\Models\OpenItem;
use App\Models\PreventiveMeasure;
use App\Models\Risk;
use App\Models\SystemTask;
use App\Models\TrainingRecord;
use App\Support\Compliance\Evaluator;
use Illuminate\Support\Carbon;

/**
 * Sammelt die Daten für den Audit-/Governance-Bericht: prozesszentrisch die
 * vollständige BIA je Geschäftsprozess samt verknüpfter Risiken, Maßnahmen und
 * Offener Punkte — plus Nachweise zu Schulungen, Aufgaben, Lessons Learned und
 * Management Review sowie einen Anhang mit (noch) nicht zugeordneten Einträgen,
 * damit im Bericht nichts verloren geht.
 */
class AuditReportData
{
    /**
     * @return array<string, mixed>
     */
    public static function forCompany(Company $company, ?Carbon $generatedAt = null, ?HandbookVersion $version = null): array
    {
        $now = $generatedAt ?? now();
        $periodStart = $now->copy()->subYear();

        // Aktuelle Handbuch-Version (bzw. die beim Freigeben gespeicherte) — soll
        // in jedem PDF stehen, damit der Bericht eindeutig einem Stand zuordenbar ist.
        $version ??= $company->currentHandbookVersion();
        $versionString = $version?->version
            ?? $company->handbookVersions()->latest('created_at')->value('version')
            ?? '1.0';

        $processes = BusinessProcess::with([
            'systems',
            'responsible',
            'responsibleRole',
            'risks.owner',
            'preventiveMeasures.responsible',
            'preventiveMeasures.responsibleRole',
            'openItems.responsible',
            'openItems.responsibleRole',
        ])
            ->where('company_id', $company->id)
            ->orderByRaw("CASE criticality WHEN 'existenzkritisch' THEN 0 WHEN 'hoch' THEN 1 WHEN 'mittel' THEN 2 ELSE 3 END")
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        $trainingRecords = config('features.training_records')
            ? TrainingRecord::with(['employee', 'responsible', 'openItems'])
                ->where('company_id', $company->id)
                ->orderByRaw('completed_at is null')
                ->orderBy('next_due_at')
                ->orderBy('topic')
                ->get()
            : collect();

        $systemTasks = SystemTask::with(['system', 'riskMitigation', 'assignees', 'roleAssignees'])
            ->where('company_id', $company->id)
            ->orderByRaw('completed_at is null desc')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->get();

        $lessonsLearned = config('features.lessons_learned')
            ? LessonLearned::with(['actionItems.responsibleEmployee', 'author'])
                ->where('company_id', $company->id)
                ->orderByRaw('finalized_at is null')
                ->orderByDesc('finalized_at')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $managementReviews = config('features.management_review')
            ? ManagementReview::where('company_id', $company->id)
                ->orderByDesc('review_date')
                ->get()
            : collect();

        $handbookTests = HandbookTest::with(['responsible', 'responsibleRole'])
            ->where('company_id', $company->id)
            ->orderBy('next_due_at')
            ->get();

        $openItems = OpenItem::where('company_id', $company->id)->get();
        $measures = PreventiveMeasure::where('company_id', $company->id)->get();

        $governanceSnapshot = [
            'risksTotal' => Risk::where('company_id', $company->id)->count(),
            'openItemsOpen' => $openItems->filter(fn (OpenItem $i) => $i->status->value !== 'resolved')->count(),
            'openItemsOverdue' => $openItems->filter(fn (OpenItem $i) => $i->isOverdue())->count(),
            'measuresOverdue' => $measures->filter(fn (PreventiveMeasure $m) => $m->isOverdue())->count(),
            'testsTotal' => $handbookTests->count(),
            'testsOverdue' => $handbookTests->filter(fn (HandbookTest $t) => $t->isOverdue())->count(),
            'lessonsTotal' => $lessonsLearned->count(),
            'lessonsOpenActions' => $lessonsLearned->sum(fn (LessonLearned $l) => $l->actionItems->filter(fn ($a) => ! in_array($a->status->value, ['done', 'cancelled'], true))->count()),
            'trainingDone' => $trainingRecords->filter(fn (TrainingRecord $t) => $t->completed_at !== null)->count(),
            'trainingPlanned' => $trainingRecords->filter(fn (TrainingRecord $t) => $t->completed_at === null)->count(),
            'trainingOverdue' => $trainingRecords->filter(fn (TrainingRecord $t) => $t->isOverdue())->count(),
            'tasksOpen' => $systemTasks->filter(fn (SystemTask $t) => ! $t->isDone())->count(),
            'tasksOverdue' => $systemTasks->filter(fn (SystemTask $t) => $t->isOverdue())->count(),
            'tasksDoneInPeriod' => $systemTasks->filter(fn (SystemTask $t) => $t->completed_at !== null && $t->completed_at->greaterThanOrEqualTo($periodStart))->count(),
            'nextReviewAt' => $managementReviews->max('next_review_at'),
        ];

        return [
            'company' => $company,
            'complianceReport' => config('features.compliance') ? Evaluator::for($company) : null,
            'authorityContacts' => config('features.authority_contacts')
                ? AuthorityContact::with(['responsibleRole', 'communicationTemplate'])
                    ->where('company_id', $company->id)
                    ->orderBy('type')->orderBy('sort')->orderBy('name')
                    ->get()
                : collect(),
            'processes' => $processes,
            'unlinkedRisks' => Risk::where('company_id', $company->id)->whereNull('business_process_id')->orderBy('title')->get(),
            'unlinkedMeasures' => PreventiveMeasure::with(['responsible', 'responsibleRole'])->where('company_id', $company->id)->whereNull('business_process_id')->orderBy('title')->get(),
            'unlinkedOpenItems' => OpenItem::with(['responsible', 'responsibleRole'])->where('company_id', $company->id)->whereNull('business_process_id')->orderBy('title')->get(),
            'insurancePolicies' => InsurancePolicy::with(['responsibleRole', 'scenarios'])->where('company_id', $company->id)->orderBy('type')->orderBy('insurer')->get(),
            'aiSystems' => config('features.ai_governance')
                ? AiSystem::with('responsibleRole')
                    ->withCount(['logEntries as open_art73_count' => fn ($q) => $q->where('reportable', true)->whereNull('reported_at')])
                    ->where('company_id', $company->id)
                    ->orderByRaw("CASE risk_class WHEN 'prohibited' THEN 0 WHEN 'high' THEN 1 WHEN 'limited' THEN 2 WHEN 'minimal' THEN 3 ELSE 4 END")
                    ->orderBy('name')
                    ->get()
                : collect(),
            'trainingRecords' => $trainingRecords,
            'systemTasks' => $systemTasks,
            'lessonsLearned' => $lessonsLearned,
            'managementReviews' => $managementReviews,
            'handbookTests' => $handbookTests,
            'governanceSnapshot' => $governanceSnapshot,
            'periodStart' => $periodStart,
            'versionString' => $versionString,
            'generatedAt' => $now,
        ];
    }
}
