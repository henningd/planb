<?php

namespace App\Support;

use App\Models\Company;
use App\Models\HandbookVersion;
use App\Scopes\CurrentCompanyScope;
use App\Support\Settings\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Erzeugt revisionssichere PDF-Snapshots eines Notfallhandbuchs für eine
 * HandbookVersion. Das PDF wird einmalig pro Version auf der privaten
 * 'handbook'-Disk unter {company_id}/{version_id}.pdf abgelegt und nicht
 * mehr überschrieben, damit alte Stände jederzeit nachweisbar bleiben.
 */
class HandbookPdfGenerator
{
    public const DISK = 'handbook';

    /**
     * Generiert + speichert das PDF und schreibt Metadaten (Pfad, SHA-256,
     * Größe, Zeitstempel) zurück an die Version. Wirft wenn die Version
     * bereits ein PDF hat – Revisionssicherheit.
     */
    public static function generate(HandbookVersion $version): HandbookVersion
    {
        if ($version->hasPdf()) {
            throw new RuntimeException(
                'Diese Version wurde bereits freigegeben und besitzt ein unveränderliches PDF.'
            );
        }

        $company = $version->company()->withoutGlobalScope(CurrentCompanyScope::class)->firstOrFail();
        $settings = CompanySetting::for($company);

        $data = HandbookData::forCompany($company);
        $data['version'] = $version;
        $data['showPdfHashFooter'] = (bool) $settings->get('pdf_footer_show_hash', true);
        $data['isPdf'] = true;

        $paper = (string) $settings->get('pdf_paper_size', 'a4');

        $pdf = Pdf::loadView('handbook-print', $data)
            ->setPaper($paper)
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'defaultMediaType' => 'print']);

        $binary = $pdf->output();
        $relativePath = "{$company->id}/{$version->id}.pdf";

        Storage::disk(self::DISK)->put($relativePath, $binary);

        $version->forceFill([
            'pdf_path' => $relativePath,
            'pdf_hash' => hash('sha256', $binary),
            'pdf_size' => strlen($binary),
            'pdf_generated_at' => now(),
        ])->save();

        // Audit-/Governance-Bericht revisionssicher zum selben Versionsstand
        // mitspeichern, sofern das BIA-/Governance-Modul aktiv ist.
        if (config('features.bia')) {
            $auditData = AuditReportData::forCompany($company, null, $version);
            $auditData['isPdf'] = true;

            $auditPdf = Pdf::loadView('audit-report', $auditData)
                ->setPaper($paper)
                ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'defaultMediaType' => 'print']);

            $auditBinary = $auditPdf->output();
            $auditPath = "{$company->id}/{$version->id}-audit.pdf";

            Storage::disk(self::DISK)->put($auditPath, $auditBinary);

            $version->forceFill([
                'audit_pdf_path' => $auditPath,
                'audit_pdf_hash' => hash('sha256', $auditBinary),
                'audit_pdf_size' => strlen($auditBinary),
                'audit_pdf_generated_at' => now(),
            ])->save();
        }

        return $version;
    }

    /**
     * Rendert das AKTUELLE Handbuch der Firma live als PDF (ohne Speicherung).
     * Fallback für die Notfall-App, wenn (noch) keine freigegebene, PDF-behaftete
     * HandbookVersion existiert – so ist das vorhandene Handbuch trotzdem mobil
     * verfügbar. Nicht revisionssicher; es spiegelt den momentanen Datenstand.
     */
    public static function renderLive(Company $company): string
    {
        $settings = CompanySetting::for($company);

        $data = HandbookData::forCompany($company);
        $data['version'] = $company->currentHandbookVersion();
        $data['showPdfHashFooter'] = false;
        $data['isPdf'] = true;

        $paper = (string) $settings->get('pdf_paper_size', 'a4');

        return Pdf::loadView('handbook-print', $data)
            ->setPaper($paper)
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'defaultMediaType' => 'print'])
            ->output();
    }
}
