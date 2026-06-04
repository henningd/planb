<?php

namespace App\Http\Controllers;

use App\Models\CrisisLogEntry;
use App\Models\ScenarioRun;
use App\Support\CurrentCompany;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export des Krisen-Logbuchs (Entscheidungsprotokoll) eines ScenarioRuns
 * als revisionssicheres PDF. Mandanten-Isolation erfolgt über das
 * gescopte Route-Binding sowie zusätzlich über einen expliziten
 * company_id-Check (Defense-in-depth).
 */
class CrisisLogExportController extends Controller
{
    public function pdf(Request $request, string $currentTeam, ScenarioRun $run): Response
    {
        abort_unless($run->company_id === CurrentCompany::id() && CurrentCompany::id() !== null, 404);

        $company = CurrentCompany::resolve();
        abort_if($company === null, 404);

        $entries = CrisisLogEntry::where('scenario_run_id', $run->id)
            ->with('user')
            ->orderBy('occurred_at')
            ->get();

        $scenarioName = $run->scenario?->name ?? $run->title ?? '–';

        $pdf = Pdf::loadView('crisis-log-export', [
            'company' => $company,
            'run' => $run,
            'scenarioName' => $scenarioName,
            'entries' => $entries,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);

        $teamSlug = Str::slug($currentTeam) ?: 'team';
        $filename = 'krisenprotokoll-'.$teamSlug.'-'.now()->format('Y-m-d').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
