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
        abort_unless($version->hasPdf(), 404);

        $disk = Storage::disk(HandbookPdfGenerator::DISK);
        abort_unless($disk->exists($version->pdf_path), 404);

        $filename = sprintf(
            'notfallhandbuch-%s-v%s.pdf',
            $version->company->team?->slug ?? 'firma',
            preg_replace('/[^A-Za-z0-9._-]/', '_', $version->version),
        );

        return $disk->download($version->pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
