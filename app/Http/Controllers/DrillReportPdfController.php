<?php

namespace App\Http\Controllers;

use App\Models\ScenarioRun;
use App\Support\CurrentCompany;
use App\Support\Reports\DrillReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export eines Übungsberichts als PDF — der nüchterne, vollständige
 * Nachweis einer durchgeführten Notfall-Übung für Prüfer und Versicherer.
 * Mandanten-Isolation über das gescopte Route-Binding plus expliziten
 * company_id-Check (Defense-in-depth, wie beim Krisenprotokoll-Export).
 */
class DrillReportPdfController extends Controller
{
    public function __invoke(Request $request, string $currentTeam, ScenarioRun $run): Response
    {
        abort_unless($run->company_id === CurrentCompany::id() && CurrentCompany::id() !== null, 404);

        $company = CurrentCompany::resolve();
        abort_if($company === null, 404);

        $report = DrillReport::for($run);
        abort_unless($report->isReportable(), 404);

        $pdf = Pdf::loadView('pdf.drill-report', [
            'company' => $company,
            'report' => $report,
            'run' => $run,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);

        $teamSlug = Str::slug($currentTeam) ?: 'team';
        $filename = 'uebungsbericht-'.$teamSlug.'-'.($run->started_at?->format('Y-m-d') ?? now()->format('Y-m-d')).'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
