<?php

namespace App\Support;

use App\Models\Company;
use App\Support\Settings\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Erzeugt die drei fachlichen PDF-Exporttypen zu einem Handbuch (live, ohne
 * revisionssichere Speicherung – dafür ist {@see HandbookPdfGenerator} zuständig):
 *
 * - Typ 1 „Ernstfall-Handbuch": schlanke, operative Fassung für Papierordner und
 *   Krisenstab (ohne Governance-/Audit-Kapitel).
 * - Typ 2 „Audit-Bericht": Reifegrad, Risiken, Maßnahmen, Aufgaben, Tests,
 *   Schulungen, Lessons Learned, Management Review, BIA, Versicherungsprüfung.
 * - Typ 3 „Vollständiger Export": komplettes Handbuch + Audit-Bericht in einem
 *   Dokument (Archiv / Übergabe).
 */
class HandbookExport
{
    /**
     * @return array{binary: string, filename: string}
     */
    public static function ernstfall(Company $company): array
    {
        $data = self::handbookData($company, 'ernstfall');

        return [
            'binary' => self::toPdf(Pdf::loadView('handbook-print', $data), $company),
            'filename' => self::filename($company, 'ernstfall-handbuch'),
        ];
    }

    /**
     * @return array{binary: string, filename: string}
     */
    public static function audit(Company $company): array
    {
        $pdf = Pdf::loadView('audit-report', AuditReportData::forCompany($company));

        return [
            'binary' => self::toPdf($pdf, $company),
            'filename' => self::filename($company, 'audit-bericht'),
        ];
    }

    /**
     * @return array{binary: string, filename: string}
     */
    public static function full(Company $company): array
    {
        $handbookHtml = view('handbook-print', self::handbookData($company, 'full'))->render();
        $auditHtml = config('features.bia')
            ? view('audit-report', AuditReportData::forCompany($company))->render()
            : null;

        $pdf = Pdf::loadHTML(self::combine($handbookHtml, $auditHtml));

        return [
            'binary' => self::toPdf($pdf, $company),
            'filename' => self::filename($company, 'vollstaendiger-export'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function handbookData(Company $company, string $exportMode): array
    {
        $data = HandbookData::forCompany($company);
        $data['version'] = $company->currentHandbookVersion();
        $data['showPdfHashFooter'] = false;
        $data['isPdf'] = true;
        $data['exportMode'] = $exportMode;

        return $data;
    }

    /**
     * Fügt Handbuch- und Audit-HTML zu einem Dokument zusammen: die <style>-Blöcke
     * beider Views in den <head> (Handbuch zuletzt, damit es bei generischen
     * Selektoren gewinnt), danach beide <body>-Inhalte mit Seitenumbruch dazwischen.
     */
    private static function combine(string $handbookHtml, ?string $auditHtml): string
    {
        if ($auditHtml === null) {
            return $handbookHtml;
        }

        $styles = self::extractStyles($auditHtml).self::extractStyles($handbookHtml);

        return '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'.$styles.'</head><body>'
            .self::extractBody($handbookHtml)
            .'<div style="page-break-before: always;"></div>'
            .self::extractBody($auditHtml)
            .'</body></html>';
    }

    private static function extractStyles(string $html): string
    {
        preg_match_all('/<style\b[^>]*>.*?<\/style>/is', $html, $matches);

        return implode('', $matches[0]);
    }

    private static function extractBody(string $html): string
    {
        if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $html, $matches) !== 1) {
            return '';
        }

        // Auto-Print-Skripte entfernen; im PDF ohne Funktion.
        return (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $matches[1]);
    }

    private static function toPdf(\Barryvdh\DomPDF\PDF $pdf, Company $company): string
    {
        $paper = (string) CompanySetting::for($company)->get('pdf_paper_size', 'a4');

        return $pdf
            ->setPaper($paper)
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'defaultMediaType' => 'print'])
            ->output();
    }

    private static function filename(Company $company, string $kind): string
    {
        return sprintf(
            '%s-%s-%s.pdf',
            $kind,
            preg_replace('/[^A-Za-z0-9_-]/', '', $company->team?->slug ?? 'firma'),
            now()->format('Y-m-d'),
        );
    }
}
