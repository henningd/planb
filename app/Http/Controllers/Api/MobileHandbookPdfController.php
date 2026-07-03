<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\HandbookVersion;
use App\Scopes\CurrentCompanyScope;
use App\Support\HandbookPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Liefert das Handbuch-PDF an die Notfall-App aus — nur für den Mandanten des
 * Tokens.
 *
 * `{version}` = eine Versions-UUID → das revisionssichere, gespeicherte PDF.
 * `{version}` = `current` → das aktuelle Handbuch live gerendert (Fallback,
 * wenn keine freigegebene PDF-Version existiert).
 */
class MobileHandbookPdfController extends Controller
{
    public function __invoke(Request $request, string $version): Response
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);

        $company = Company::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($token->company_id);
        abort_if($company === null, 404);

        if ($version === 'current') {
            @ini_set('memory_limit', '1024M');

            return response(HandbookPdfGenerator::renderLive($company), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="notfallhandbuch.pdf"',
            ]);
        }

        $handbookVersion = HandbookVersion::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($version);

        abort_unless(
            $handbookVersion !== null && $handbookVersion->company_id === $company->id,
            404,
        );
        abort_unless($handbookVersion->hasPdf(), 404);

        $disk = Storage::disk(HandbookPdfGenerator::DISK);
        abort_unless($disk->exists($handbookVersion->pdf_path), 404);

        return $disk->download($handbookVersion->pdf_path, 'notfallhandbuch.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
