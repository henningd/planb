<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Support\CurrentCompany;
use App\Support\Incident\Cockpit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Offline-Notfallkarte als Download-PDF.
 *
 * Bündelt Krisenstab, Wiederanlauf-Reihenfolge und Dienstleister-Hotlines
 * des aktuellen Mandanten auf einer kompakten, druckoptimierten A4-Seite —
 * gedacht zum Ausdrucken und offline griffbereit halten. Funktioniert auch
 * ohne aktiven ScenarioRun; Deadlines bleiben dann unberechnet.
 */
class EmergencyCardController extends Controller
{
    public function pdf(Request $request, string $currentTeam): Response
    {
        $company = CurrentCompany::resolve();
        abort_if($company === null, 404);

        $cockpit = Cockpit::for($company);

        $serviceProviders = ServiceProvider::query()
            ->where('company_id', $company->id)
            ->whereNotNull('hotline')
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('emergency-card', [
            'company' => $company,
            'crisisStaff' => $cockpit->crisisStaff,
            'recoveryOrder' => $cockpit->recoveryOrder,
            'serviceProviders' => $serviceProviders,
            'generatedAt' => now(),
        ])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);

        $slug = $company->team?->slug ?? 'firma';
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', $slug) ?? 'firma';
        $filename = 'notfallkarte-'.$slug.'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
