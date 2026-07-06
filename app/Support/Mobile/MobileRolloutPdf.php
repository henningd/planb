<?php

namespace App\Support\Mobile;

use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Massen-Rollout der Notfall-App: erzeugt für eine Auswahl von Benutzern je
 * einen Kopplungs-Code (Gültigkeit {@see MobileAccessCode::ROLLOUT_TTL_DAYS}
 * Tage statt der kurzen Self-Service-TTL, da die Blätter gedruckt verteilt
 * werden) und rendert ein druckbares PDF — eine Seite pro Person mit Name,
 * QR-Code, Code als Text und Schritt-Anleitung.
 *
 * Die Klartext-Codes existieren nur innerhalb dieses Aufrufs (in der DB liegt
 * ausschließlich der Hash); das PDF ist daher der einzige Weg, sie auszugeben.
 */
class MobileRolloutPdf
{
    /**
     * @param  Collection<int, User>  $users
     * @return array{binary: string, filename: string, expiresAt: Carbon}
     */
    public static function generate(Company $company, Collection $users, User $issuedBy): array
    {
        $expiresAt = Carbon::now()->addDays(MobileAccessCode::ROLLOUT_TTL_DAYS);

        $entries = $users->map(function (User $user) use ($company, $issuedBy, $expiresAt) {
            $issued = MobileAccessCode::issue($user, $company, $issuedBy->id, $expiresAt);

            return [
                'name' => $user->name,
                'email' => $user->email,
                'code' => $issued['code'],
                'qr' => OnboardingQrCode::dataUri(self::payload($user, $issued['code'])),
            ];
        });

        $pdf = Pdf::loadView('pdf.mobile-rollout', [
            'company' => $company,
            'entries' => $entries,
            'expiresAt' => $expiresAt,
        ])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'defaultMediaType' => 'print']);

        return [
            'binary' => $pdf->output(),
            'filename' => 'notfall-app-zugaenge-'.now()->format('Y-m-d').'.pdf',
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Onboarding-Payload — identisch zum Self-Service-QR der Einstellungen-Seite
     * ({url, key, email, code}), damit die App beide Wege gleich behandelt.
     */
    private static function payload(User $user, string $code): string
    {
        return (string) json_encode([
            'url' => rtrim((string) config('app.url'), '/'),
            'key' => (string) config('services.mobile.app_key', ''),
            'email' => $user->email,
            'code' => $code,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
