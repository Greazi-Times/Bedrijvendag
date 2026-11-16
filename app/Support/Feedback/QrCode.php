<?php

namespace App\Support\Feedback;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** QR codes are drawn locally (bacon/bacon-qr-code, already installed for two-factor login); no external service sees the link. */
class QrCode
{
    public static function svg(string $url): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle(512, 2), new SvgImageBackEnd)))->writeString($url);
    }

    /** PNG needs the imagick extension; SVG works everywhere. */
    public static function png(string $url): ?string
    {
        return extension_loaded('imagick')
            ? (new Writer(new ImageRenderer(new RendererStyle(1024, 4), new ImagickImageBackEnd('png'))))->writeString($url)
            : null;
    }
}
