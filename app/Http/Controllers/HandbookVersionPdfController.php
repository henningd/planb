<?php

namespace App\Http\Controllers;

use App\Models\HandbookVersion;
use App\Support\CurrentCompany;
use App\Support\HandbookPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HandbookVersionPdfController extends Controller
{
    /**
     * Liefert das revisionssichere PDF einer HandbookVersion zum Download
     * aus, sofern es zur Firma des aktuellen Teams gehört. Mandanten-Isolation
     * erfolgt über CurrentCompanyScope (Modell-Lookup) plus expliziten
     * company_id-Vergleich.
     */
    public function __invoke(Request $request, string $currentTeam, HandbookVersion $version): StreamedResponse
    {
        $companyId = CurrentCompany::id();
        abort_unless($companyId !== null && $version->company_id === $companyId, 404);

        $isAudit = $request->query('variant') === 'audit';
        $path = $isAudit ? $version->audit_pdf_path : $version->pdf_path;
        abort_unless($path !== null, 404);

        $disk = Storage::disk(HandbookPdfGenerator::DISK);
        abort_unless($disk->exists($path), 404);

        $filename = sprintf(
            '%s-%s-v%s.pdf',
            $isAudit ? 'audit-bericht' : 'notfallhandbuch',
            $version->company->team?->slug ?? 'firma',
            preg_replace('/[^A-Za-z0-9._-]/', '_', $version->version),
        );

        return $disk->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
