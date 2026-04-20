<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Thin wrapper around bacon/bacon-qr-code (already available through Fortify)
 * that returns an inline SVG string without the leading XML declaration – safe
 * to embed directly into a Blade template.
 */
class QrCode
{
    public static function svg(string $content, int $size = 260): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(
                    $size,
                    1,
                    null,
                    null,
                    Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(15, 23, 42)),
                ),
                new SvgImageBackEnd,
            ),
        ))->writeString($content);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
