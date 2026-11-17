<?php

namespace App\Support\Feedback;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * ATIx-branded QR code, drawn locally from the real QR matrix (bacon/bacon-qr-code, installed for two-factor login), so
 * no external service ever sees a link. Error correction is level H.
 *
 * Look: orange on white; modules that touch are fused into one smooth shape and every free corner is rounded; the three
 * finder patterns are rounded squares with a round centre; the official ATIx icon (resources/images/bedrijvendag-icon.svg)
 * sits on a rounded white plate in the middle. The modules under the plate are left out, which level H recovers easily.
 * Preview, SVG download and PNG download all come from the same svg().
 */
class QrCode
{
    public const ORANGE = '#ff6600';

    private const MODULE = 10;

    private const QUIET = 4;

    public static function svg(string $url): string
    {
        $matrix = Encoder::encode($url, ErrorCorrectionLevel::H(), 'ISO-8859-1')->getMatrix();
        $n = $matrix->getWidth();
        $m = self::MODULE;
        $q = self::QUIET * $m;
        $size = ($n + 2 * self::QUIET) * $m;
        $plate = (int) (2 * floor((min($n * 0.33, 11) - 1) / 2) + 1); // odd number of modules: a third of the width, capped at 11 so dense codes (long URLs) keep enough data
        $from = intdiv($n - $plate, 2);
        $finder = fn (int $x, int $y): bool => ($x < 7 && $y < 7) || ($x >= $n - 7 && $y < 7) || ($x < 7 && $y >= $n - 7);
        $underPlate = fn (int $x, int $y): bool => $x >= $from && $x < $from + $plate && $y >= $from && $y < $from + $plate;
        $dark = fn (int $x, int $y): bool => $x >= 0 && $y >= 0 && $x < $n && $y < $n && $matrix->get($x, $y) === 1 && ! $finder($x, $y) && ! $underPlate($x, $y);

        // All modules in ONE path, so fused neighbours have no seam between them when rendered.
        $d = '';

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($dark($x, $y)) {
                    $up = $dark($x, $y - 1);
                    $down = $dark($x, $y + 1);
                    $left = $dark($x - 1, $y);
                    $right = $dark($x + 1, $y);
                    $d .= self::shape($q + $x * $m, $q + $y * $m, $m, [! $up && ! $left, ! $up && ! $right, ! $down && ! $right, ! $down && ! $left]);
                }
            }
        }

        $out = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$size.' '.$size.'" width="'.$size.'" height="'.$size.'" role="img" aria-label="QR code">';
        $out .= '<rect width="'.$size.'" height="'.$size.'" fill="#fff"/><g fill="'.self::ORANGE.'"><path d="'.$d.'"/>';

        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$fx, $fy]) {
            $x = $q + $fx * $m;
            $y = $q + $fy * $m;
            $out .= '<rect x="'.$x.'" y="'.$y.'" width="'.(7 * $m).'" height="'.(7 * $m).'" rx="'.(2.2 * $m).'"/>'
                .'<rect x="'.($x + $m).'" y="'.($y + $m).'" width="'.(5 * $m).'" height="'.(5 * $m).'" rx="'.(1.4 * $m).'" fill="#fff"/>'
                .'<circle cx="'.($x + 3.5 * $m).'" cy="'.($y + 3.5 * $m).'" r="'.(1.5 * $m).'"/>';
        }

        $box = $plate * $m;
        $px = $q + $from * $m;
        $pad = 0.25 * $m; // even padding around the logo
        $logo = $box - 2 * $pad;
        $out .= '</g><rect x="'.$px.'" y="'.$px.'" width="'.$box.'" height="'.$box.'" rx="'.(0.3 * $box).'" fill="#fff"/>';
        $out .= '<g transform="translate('.($px + $pad).' '.($px + $pad).') scale('.($logo / 160).')">'.self::icon().'</g></svg>';

        return $out;
    }

    /** PNG for screens and documents, rendered from the very same SVG; needs the imagick extension (SVG is the print format). */
    public static function png(string $url, int $pixels = 1024): ?string
    {
        if (! extension_loaded('imagick')) {
            return null;
        }

        $image = new \Imagick;
        $image->setBackgroundColor(new \ImagickPixel('white'));
        $image->setResolution(288, 288);
        $image->readImageBlob(self::svg($url));
        $image->setImageFormat('png');
        $image->resizeImage($pixels, $pixels, \Imagick::FILTER_LANCZOS, 1);

        return $image->getImageBlob();
    }

    /** One module as a rounded square; $round = [top-left, top-right, bottom-right, bottom-left] says which corners are free. */
    private static function shape(float $x, float $y, float $s, array $round): string
    {
        [$tl, $tr, $br, $bl] = array_map(fn (bool $r): float => $r ? $s / 2 : 0.0, $round);
        $arc = fn (float $r, float $dx, float $dy): string => $r > 0 ? 'a'.$r.' '.$r.' 0 0 1 '.$dx.' '.$dy : '';

        return 'M'.($x + $tl).' '.$y.'h'.($s - $tl - $tr).$arc($tr, $tr, $tr).'v'.($s - $tr - $br).$arc($br, -$br, $br)
            .'h'.-($s - $br - $bl).$arc($bl, -$bl, -$bl).'v'.-($s - $bl - $tl).$arc($tl, $tl, -$tl).'z';
    }

    /** The official ATIx icon, used as supplied. */
    private static function icon(): string
    {
        static $icon;

        return $icon ??= (preg_match('~<svg[^>]*>(.*)</svg>~s', (string) file_get_contents(resource_path('images/bedrijvendag-icon.svg')), $m) ? $m[1] : '');
    }
}
