<?php

namespace App\Http\Controllers;

use App\Mail\Nis2QuickCheckReport;
use App\Models\Lead;
use App\Support\Settings\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View as ViewInstance;

class LeadConfirmationController extends Controller
{
    /**
     * Bestätigt die E-Mail-Adresse eines NIS2-Quick-Check-Leads (Double-Opt-In)
     * und löst einmalig den Versand der PDF-Auswertung aus. Der Zugriff ist
     * über die `signed`-Middleware abgesichert; wiederholte Aufrufe sind
     * idempotent und versenden die Auswertung nicht erneut.
     */
    public function __invoke(Lead $lead): View|ViewInstance
    {
        if (! $lead->isConfirmed()) {
            $lead->forceFill([
                'confirmed_at' => now(),
                'report_sent_at' => now(),
            ])->save();

            Mail::to($lead->email)->send(new Nis2QuickCheckReport($lead));
        }

        return view('nis2-quick-check-confirmed', [
            'productName' => SystemSetting::get('platform_name') ?: config('app.name', 'PlanB'),
        ]);
    }
}
