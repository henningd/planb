<?php

namespace App\Http\Controllers;

use App\Models\AuthActivity;
use App\Support\AuthActivityFilter;
use App\Support\CurrentCompany;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exporte für die Login-Activity-Page.
 *
 * Liefert die gefilterten An-/Abmeldungen der aktuellen Firma als CSV
 * (Excel-DE-kompatibel, Semikolon-getrennt, UTF-8 BOM) oder als PDF
 * (A4 Querformat, mit Firmen-Header und Zeitraum). Mandanten-Isolation
 * erfolgt sowohl über den Global-Scope von BelongsToCurrentCompany als
 * auch zusätzlich über einen expliziten company_id-Filter.
 */
class AuthActivityExportController extends Controller
{
    /**
     * Spalten-Header für CSV und PDF — bewusst eine Quelle der Wahrheit.
     *
     * @return array<int, string>
     */
    protected function columns(): array
    {
        return ['Zeitpunkt', 'Benutzer', 'Ereignis', 'E-Mail', 'IP', 'User-Agent'];
    }

    public function csv(Request $request): StreamedResponse
    {
        $companyId = CurrentCompany::id();
        abort_if($companyId === null, 404);

        $filters = AuthActivityFilter::fromRequest($request);
        $columns = $this->columns();
        $controller = $this;

        $filename = 'anmeldungen-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($filters, $companyId, $columns, $controller): void {
            $handle = fopen('php://output', 'wb');

            // UTF-8 BOM, damit Excel-DE den Inhalt korrekt als UTF-8 erkennt.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $columns, ';', '"', '\\');

            AuthActivityFilter::build($filters)
                ->where('company_id', $companyId)
                ->chunk(500, function ($entries) use ($handle, $controller): void {
                    foreach ($entries as $entry) {
                        fputcsv($handle, $controller->rowFor($entry), ';', '"', '\\');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Request $request): Response
    {
        $companyId = CurrentCompany::id();
        abort_if($companyId === null, 404);

        $company = CurrentCompany::resolve();
        abort_if($company === null, 404);

        $filters = AuthActivityFilter::fromRequest($request);

        $entries = AuthActivityFilter::build($filters)
            ->where('company_id', $companyId)
            ->limit(2000)
            ->get();

        $pdf = Pdf::loadView('auth-activity-export', [
            'company' => $company,
            'entries' => $entries,
            'filters' => $filters,
            'columns' => $this->columns(),
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);

        $filename = 'anmeldungen-'.now()->format('Y-m-d_His').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Wandelt einen Auth-Eintrag in eine CSV-Zeile in der Reihenfolge der
     * Spalten-Header. Public, damit der CSV-Streaming-Closure und die
     * PDF-View direkt darauf zugreifen können.
     *
     * @return array<int, string>
     */
    public function rowFor(AuthActivity $entry): array
    {
        return [
            $entry->created_at?->format('d.m.Y H:i:s') ?? '',
            $entry->user?->name ?? (string) ($entry->email ?? ''),
            $this->eventLabel((string) $entry->event),
            (string) ($entry->email ?? ''),
            (string) ($entry->ip_address ?? ''),
            (string) ($entry->user_agent ?? ''),
        ];
    }

    protected function eventLabel(string $event): string
    {
        return match ($event) {
            'login' => 'Angemeldet',
            'logout' => 'Abgemeldet',
            'failed' => 'Fehlgeschlagen',
            default => $event,
        };
    }
}
