<?php

namespace App\Support\Mobile;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Erzeugt den Onboarding-QR-Code der Notfall-App als eingebetteten Data-URI.
 *
 * Bewusst als SVG (kein GD/Imagick nötig) — das Ergebnis lässt sich direkt in
 * ein <img src="…"> einbetten. Der Inhalt ist der Onboarding-Payload
 * ({url, key, email, code}), den die App beim Scannen auswertet.
 */
class OnboardingQrCode
{
    public static function dataUri(string $payload): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 240,
            margin: 8,
        );

        return $builder->build()->getDataUri();
    }
}
