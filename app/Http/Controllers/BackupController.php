<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\Backup\BackupCatalog;
use App\Support\Backup\Exporter;
use App\Support\CurrentCompany;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Streamt die ausgewählten Bereiche der aktuellen Firma als JSON-Download.
     */
    public function download(Request $request): StreamedResponse
    {
        $companyId = CurrentCompany::id();
        abort_unless($companyId !== null, 404);

        $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->findOrFail($companyId);

        $catalog = BackupCatalog::all();
        $requestedKeys = collect((array) $request->query('areas', array_keys($catalog)))
            ->map(fn ($v) => (string) $v)
            ->filter(fn (string $k) => isset($catalog[$k]))
            ->values()
            ->all();

        $payload = Exporter::export($company, $requestedKeys);

        $filename = sprintf(
            'planb-backup-%s-%s.json',
            $company->team?->slug ?? 'firma',
            now()->format('Y-m-d_His'),
        );

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}
