<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingInvoiceController extends Controller
{
    public function __invoke(Request $request, string $invoice): Response
    {
        abort_unless(config('features.billing'), 404);

        $team = $request->user()?->currentTeam;

        abort_unless($team !== null, 403);

        return $team->downloadInvoice($invoice, [
            'vendor' => config('app.name'),
            'product' => config('app.name'),
        ]);
    }
}
