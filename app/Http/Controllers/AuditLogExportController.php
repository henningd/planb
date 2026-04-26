<?php

namespace App\Http\Controllers;

use App\Models\AuditLogEntry;
use App\Support\AuditLogFilter;
use App\Support\CurrentCompany;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exporte für die Audit-Log-Page.
 *
 * Liefert das gefilterte Aktivitätsprotokoll der aktuellen Firma als CSV
 * (Excel-DE-kompatibel, Semikolon-getrennt, UTF-8 BOM) oder als PDF
 * (A4 Querformat, mit Firmen-Header und Zeitraum). Mandanten-Isolation
 * erfolgt sowohl über den Global-Scope von BelongsToCurrentCompany als
 * auch zusätzlich über einen expliziten company_id-Filter.
 */
class AuditLogExportController extends Controller
{
    /**
     * Spalten-Header für CSV und PDF — bewusst eine Quelle der Wahrheit.
     *
     * @return array<int, string>
     */
    protected function columns(): array
    {
        return ['Datum', 'Benutzer', 'Aktion', 'Objekt-Typ', 'Objekt', 'Änderungen'];
    }

    public function csv(Request $request): StreamedResponse
    {
        $companyId = CurrentCompany::id();
        abort_if($companyId === null, 404);

        $filters = AuditLogFilter::fromRequest($request);
        $columns = $this->columns();
        $controller = $this;

        $filename = 'aktivitaetsprotokoll-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($filters, $companyId, $columns, $controller): void {
            $handle = fopen('php://output', 'wb');

            // UTF-8 BOM, damit Excel-DE den Inhalt korrekt als UTF-8 erkennt.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $columns, ';', '"', '\\');

            AuditLogFilter::build($filters)
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

        $filters = AuditLogFilter::fromRequest($request);

        $entries = AuditLogFilter::build($filters)
            ->where('company_id', $companyId)
            ->limit(2000)
            ->get();

        $pdf = Pdf::loadView('audit-log-export', [
            'company' => $company,
            'entries' => $entries,
            'filters' => $filters,
            'columns' => $this->columns(),
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);

        $filename = 'aktivitaetsprotokoll-'.now()->format('Y-m-d_His').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Wandelt einen Audit-Eintrag in eine CSV-Zeile in der Reihenfolge der
     * Spalten-Header. Public, damit der CSV-Streaming-Closure direkt darauf
     * zugreifen kann, ohne die Closure-Bindung zu verlieren.
     *
     * @return array<int, string>
     */
    public function rowFor(AuditLogEntry $entry): array
    {
        return [
            $entry->created_at?->format('d.m.Y H:i') ?? '',
            $entry->user?->name ?? '',
            $this->actionLabel((string) $entry->action),
            (string) $entry->entity_type,
            (string) ($entry->entity_label ?? $entry->entity_id),
            $this->changesAsText($entry),
        ];
    }

    protected function actionLabel(string $action): string
    {
        if (str_ends_with($action, '.assigned')) {
            return 'Zugewiesen';
        }

        if (str_ends_with($action, '.unassigned')) {
            return 'Entzogen';
        }

        return match ($action) {
            'created' => 'Angelegt',
            'updated' => 'Geändert',
            'deleted' => 'Gelöscht',
            default => $action,
        };
    }

    protected function changesAsText(AuditLogEntry $entry): string
    {
        $changes = $entry->changes;

        if (! is_array($changes) || $changes === []) {
            return '';
        }

        $action = (string) $entry->action;

        if (str_ends_with($action, '.assigned') || str_ends_with($action, '.unassigned')) {
            $related = (string) ($changes['related_label'] ?? '');
            $raci = (string) ($changes['pivot']['raci_role'] ?? '');

            return trim($related.($raci !== '' ? ' ('.$raci.')' : ''));
        }

        if ($action === 'updated') {
            $parts = [];
            foreach ($changes as $field => $diff) {
                $old = (string) ($diff['old'] ?? '');
                $new = (string) ($diff['new'] ?? '');
                $parts[] = sprintf('%s: %s → %s', $field, $old !== '' ? $old : '—', $new !== '' ? $new : '—');
            }

            return implode(' | ', $parts);
        }

        if ($action === 'created') {
            return 'Felder: '.implode(', ', array_keys($changes));
        }

        return '';
    }
}
