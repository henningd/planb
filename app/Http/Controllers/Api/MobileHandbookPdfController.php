<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\HandbookVersion;
use App\Scopes\CurrentCompanyScope;
use App\Support\HandbookPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liefert das revisionssichere Handbuch-PDF einer Version an die Notfall-App
 * aus — nur, wenn die Version zum Mandanten des Tokens gehört.
 */
class MobileHandbookPdfController extends Controller
{
    public function __invoke(Request $request, string $version): StreamedResponse
    {
        $token = $request->attributes->get('api_token');
        abort_unless($token instanceof ApiToken, 401);

        $handbookVersion = HandbookVersion::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($version);

        abort_unless(
            $handbookVersion !== null && $handbookVersion->company_id === $token->company_id,
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
