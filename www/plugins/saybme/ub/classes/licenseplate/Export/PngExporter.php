<?php namespace Saybme\Ub\Classes\LicensePlate\Export;

use Saybme\Ub\Classes\LicensePlate\Glyphs\GlyphLibrary;
use Saybme\Ub\Classes\LicensePlate\PlateException;
use Saybme\Ub\Classes\LicensePlate\PlateRenderer;
use Saybme\Ub\Classes\LicensePlate\Templates\AbstractPlate;

/**
 * Экспорт в PNG: Imagick (если есть) или GD-растеризация тех же layout-команд.
 */
class PngExporter
{
    private PlateRenderer $renderer;
    private GlyphLibrary $glyphs;

    public function __construct(?PlateRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new PlateRenderer();
        $this->glyphs = $this->renderer->glyphs();
    }

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>,execution:int,flag:bool} $parsed
     */
    public function export(array $parsed, ?int $widthPx = null): string
    {
        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            try {
                return $this->viaImagick($parsed, $widthPx);
            } catch (\Throwable $e) {
                // Падаем на GD
            }
        }

        if (!extension_loaded('gd')) {
            throw new PlateException(
                'Для PNG нужна библиотека GD или Imagick. Установите расширение php-gd.'
            );
        }

        return $this->viaGd($parsed, $widthPx);
    }

    public function imagickAvailable(): bool
    {
        return extension_loaded('imagick') && class_exists(\Imagick::class);
    }

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>,execution:int,flag:bool} $parsed
     */
    private function viaImagick(array $parsed, ?int $widthPx): string
    {
        $svg = $this->renderer->renderSvg($parsed);
        $built = $this->renderer->buildLayout($parsed);
        /** @var AbstractPlate $template */
        $template = $built['template'];
        $wMm = $template->widthMm();
        $hMm = $template->heightMm();

        $targetW = $widthPx ?? (int) round($wMm * 2.5);
        $targetH = (int) round($targetW * ($hMm / $wMm));

        $im = new \Imagick();
        $im->setBackgroundColor(new \ImagickPixel('transparent'));
        $im->setResolution(150, 150);
        $im->readImageBlob($svg);
        $im->setImageFormat('png32');
        $im->resizeImage($targetW, $targetH, \Imagick::FILTER_LANCZOS, 1, true);
        $blob = $im->getImageBlob();
        $im->clear();
        $im->destroy();
        return $blob;
    }

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>,execution:int,flag:bool} $parsed
     */
    private function viaGd(array $parsed, ?int $widthPx): string
    {
        $built = $this->renderer->buildLayout($parsed);
        /** @var AbstractPlate $template */
        $template = $built['template'];
        $layout = $built['layout'];

        $wMm = $template->widthMm();
        $hMm = $template->heightMm();
        $targetW = $widthPx ?? (int) round($wMm * 2.5);
        if ($targetW < 40) {
            $targetW = 40;
        }
        $scale = $targetW / $wMm;
        $targetH = (int) max(1, round($hMm * $scale));

        $im = imagecreatetruecolor($targetW, $targetH);
        if ($im === false) {
            throw new PlateException('Не удалось создать GD-изображение.');
        }
        imagesavealpha($im, true);
        imagealphablending($im, false);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $targetW, $targetH, $transparent);
        imagealphablending($im, true);

        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $blue = imagecolorallocate($im, 0, 57, 166);
        $red = imagecolorallocate($im, 213, 43, 30);

        foreach ($layout as $cmd) {
            $this->paintCmd($im, $cmd, $scale, $white, $black, $blue, $red);
        }

        ob_start();
        imagepng($im);
        $blob = ob_get_clean();
        imagedestroy($im);

        if ($blob === false || $blob === '') {
            throw new PlateException('Ошибка кодирования PNG.');
        }
        return $blob;
    }

    /**
     * @param resource|\GdImage $im
     * @param array<string, mixed> $cmd
     */
    private function paintCmd($im, array $cmd, float $scale, int $white, int $black, int $blue, int $red): void
    {
        $op = $cmd['op'] ?? '';
        switch ($op) {
            case 'rounded_rect':
                $this->gdRoundedRect(
                    $im,
                    (float) $cmd['x'] * $scale,
                    (float) $cmd['y'] * $scale,
                    (float) $cmd['w'] * $scale,
                    (float) $cmd['h'] * $scale,
                    (float) ($cmd['rx'] ?? 0) * $scale,
                    $cmd['fill'] ?? null,
                    $cmd['stroke'] ?? null,
                    (float) ($cmd['strokeWidth'] ?? 0) * $scale,
                    $white,
                    $black
                );
                break;

            case 'line':
                imagesetthickness($im, max(1, (int) round((float) ($cmd['strokeWidth'] ?? 1) * $scale)));
                imageline(
                    $im,
                    (int) round((float) $cmd['x1'] * $scale),
                    (int) round((float) $cmd['y1'] * $scale),
                    (int) round((float) $cmd['x2'] * $scale),
                    (int) round((float) $cmd['y2'] * $scale),
                    $black
                );
                imagesetthickness($im, 1);
                break;

            case 'glyph':
                $this->glyphs->drawOnGd(
                    $im,
                    (string) $cmd['char'],
                    (float) $cmd['x'] * $scale,
                    (float) $cmd['y'] * $scale,
                    (float) $cmd['height'] * $scale,
                    $black,
                    $white
                );
                break;

            case 'flag':
                $x = (float) $cmd['x'] * $scale;
                $y = (float) $cmd['y'] * $scale;
                $w = (float) $cmd['w'] * $scale;
                $h = (float) $cmd['h'] * $scale;
                $sh = $h / 3;
                imagefilledrectangle($im, (int) $x, (int) $y, (int) ($x + $w), (int) ($y + $sh), $white);
                imagefilledrectangle($im, (int) $x, (int) ($y + $sh), (int) ($x + $w), (int) ($y + 2 * $sh), $blue);
                imagefilledrectangle($im, (int) $x, (int) ($y + 2 * $sh), (int) ($x + $w), (int) ($y + $h), $red);
                imagerectangle($im, (int) $x, (int) $y, (int) ($x + $w), (int) ($y + $h), $black);
                break;

            case 'rus':
                $x = (float) $cmd['x'] * $scale;
                $y = (float) $cmd['y'] * $scale;
                $height = (float) $cmd['height'] * $scale;
                // RUS одной строкой шрифтом RoadNumbers
                $this->glyphs->drawOnGd($im, 'R', $x, $y, $height, $black, $white);
                $x2 = $x + $this->glyphs->advanceWidth('R', $height);
                $this->glyphs->drawOnGd($im, 'U', $x2, $y, $height, $black, $white);
                $x3 = $x2 + $this->glyphs->advanceWidth('U', $height);
                $this->glyphs->drawOnGd($im, 'S', $x3, $y, $height, $black, $white);
                break;
        }
    }

    /**
     * @param resource|\GdImage $im
     */
    private function gdRoundedRect(
        $im,
        float $x,
        float $y,
        float $w,
        float $h,
        float $rx,
        ?string $fill,
        ?string $stroke,
        float $strokeW,
        int $white,
        int $black
    ): void {
        $rx = min($rx, $w / 2, $h / 2);
        if ($fill !== null) {
            $color = $this->parseColor($im, $fill, $white, $black);
            $this->filledRoundedRect($im, $x, $y, $w, $h, $rx, $color);
        }
        if ($stroke !== null && $strokeW > 0) {
            $color = $this->parseColor($im, $stroke, $white, $black);
            // Рисуем обводку как разность двух скруглённых прямоугольников (внешний/внутренний)
            $this->strokeRoundedRect($im, $x, $y, $w, $h, $rx, $strokeW, $color);
        }
    }

    /**
     * @param resource|\GdImage $im
     */
    private function parseColor($im, string $hex, int $white, int $black): int
    {
        if (!preg_match('/^#([0-9A-Fa-f]{6})$/', $hex, $m)) {
            return str_contains(strtolower($hex), 'fff') ? $white : $black;
        }
        $n = hexdec($m[1]);
        $r = ($n >> 16) & 0xFF;
        $g = ($n >> 8) & 0xFF;
        $b = $n & 0xFF;
        return imagecolorallocate($im, $r, $g, $b);
    }

    /**
     * @param resource|\GdImage $im
     */
    private function filledRoundedRect($im, float $x, float $y, float $w, float $h, float $r, int $color): void
    {
        $x1 = (int) round($x);
        $y1 = (int) round($y);
        $x2 = (int) round($x + $w);
        $y2 = (int) round($y + $h);
        $r = (int) round($r);
        if ($r <= 0) {
            imagefilledrectangle($im, $x1, $y1, $x2, $y2, $color);
            return;
        }
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    /**
     * @param resource|\GdImage $im
     */
    private function strokeRoundedRect(
        $im,
        float $x,
        float $y,
        float $w,
        float $h,
        float $r,
        float $sw,
        int $color
    ): void {
        // Упрощённо: несколько концентрических скруглённых рамок
        $steps = max(1, (int) ceil($sw));
        for ($i = 0; $i < $steps; $i++) {
            $inset = $i;
            $this->outlineRoundedRect(
                $im,
                $x + $inset,
                $y + $inset,
                $w - 2 * $inset,
                $h - 2 * $inset,
                max(0, $r - $inset),
                $color
            );
        }
    }

    /**
     * @param resource|\GdImage $im
     */
    private function outlineRoundedRect($im, float $x, float $y, float $w, float $h, float $r, int $color): void
    {
        $x1 = (int) round($x);
        $y1 = (int) round($y);
        $x2 = (int) round($x + $w);
        $y2 = (int) round($y + $h);
        $r = (int) max(0, round($r));
        imageline($im, $x1 + $r, $y1, $x2 - $r, $y1, $color);
        imageline($im, $x1 + $r, $y2, $x2 - $r, $y2, $color);
        imageline($im, $x1, $y1 + $r, $x1, $y2 - $r, $color);
        imageline($im, $x2, $y1 + $r, $x2, $y2 - $r, $color);
        if ($r > 0) {
            imagearc($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $color);
            imagearc($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $color);
            imagearc($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $color);
            imagearc($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $color);
        }
    }
}
