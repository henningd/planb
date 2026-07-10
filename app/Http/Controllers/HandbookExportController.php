<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\CurrentCompany;
use App\Support\HandbookExport;
use Illuminate\Http\Response;

/**
 * Liefert die drei fachlichen Live-PDF-Exporttypen (Ernstfall-Handbuch,
 * Audit-Bericht, vollständiger Export) zum Handbuch der aktuellen Firma.
 */
class HandbookExportController extends Controller
{
    public function ernstfall(): Response
    {
        return $this->download(HandbookExport::ernstfall($this->company()));
    }

    public function audit(): Response
    {
        abort_unless(config('features.bia'), 404);

        return $this->download(HandbookExport::audit($this->company()));
    }

    public function full(): Response
    {
        return $this->download(HandbookExport::full($this->company()));
    }

    private function company(): Company
    {
        $company = CurrentCompany::resolve();
        abort_unless($company, 404);

        return $company;
    }

    /**
     * @param  array{binary: string, filename: string}  $export
     */
    private function download(array $export): Response
    {
        return response($export['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
        ]);
    }
}
