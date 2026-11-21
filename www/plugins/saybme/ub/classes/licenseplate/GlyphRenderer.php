<?php namespace Saybme\Ub\Classes\LicensePlate;

/**
 * SVG <path> из GlyphPaths.
 * Масштаб по высоте чернил; лёгкое горизонтальное сжатие к ширинам ГОСТ (~40 мм цифра).
 */
class GlyphRenderer
{
    /** Горизонтальное сжатие относительно «сырого» RoadNumbers advance. */
    private float $widthFactor;

    public function __construct(float $widthFactor = 1.0)
    {
        $this->widthFactor = $widthFactor;
    }

    public function inkHeight(string $char): float
    {
        $g = $this->glyph($char);
        return max(1.0, (float) $g['yMax'] - (float) $g['yMin']);
    }

    public function scaleY(string $char, float $height): float
    {
        return $height / $this->inkHeight($char);
    }

    public function scaleX(string $char, float $height): float
    {
        return $this->scaleY($char, $height) * $this->widthFactor;
    }

    public function advanceMm(string $char, float $height): float
    {
        $g = $this->glyph($char);
        return $g['advance'] * $this->scaleX($char, $height);
    }

    /**
     * x,y — левый верх бокса (SVG Y вниз).
     */
    public function pathElement(string $char, float $x, float $y, float $height, string $fill = '#000000'): string
    {
        $g = $this->glyph($char);
        $sx = $this->scaleX($char, $height);
        $sy = $this->scaleY($char, $height);
        $ty = $y + $g['yMax'] * $sy;
        $tx = $x - $g['xMin'] * $sx;

        $d = htmlspecialchars($g['d'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $fill = $this->safeColor($fill);
        $transform = sprintf(
            'translate(%.4f %.4f) scale(%.6f %.6f)',
            $tx,
            $ty,
            $sx,
            -$sy
        );

        return '<path fill="' . $fill . '" fill-rule="evenodd" transform="' . $transform . '" d="' . $d . '"/>';
    }

    /** @return array{d:string,advance:int,xMin:int,yMin:int,xMax:int,yMax:int} */
    private function glyph(string $char): array
    {
        $char = strtoupper($char);
        if (!isset(GlyphPaths::GLYPHS[$char])) {
            throw new PlateException('Нет контура для символа.');
        }
        return GlyphPaths::GLYPHS[$char];
    }

    private function safeColor(string $color): string
    {
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color)) {
            return $color;
        }
        return '#000000';
    }
}
