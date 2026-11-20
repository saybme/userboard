<?php namespace Saybme\Ub\Classes\LicensePlate;

use Saybme\Ub\Classes\LicensePlate\Glyphs\GlyphLibrary;
use Saybme\Ub\Classes\LicensePlate\Templates\AbstractPlate;
use Saybme\Ub\Classes\LicensePlate\Templates\CarPlate;
use Saybme\Ub\Classes\LicensePlate\Templates\MotorcyclePlate;
use Saybme\Ub\Classes\LicensePlate\Templates\TrailerPlate;

/**
 * Собирает SVG из layout-команд шаблона.
 */
class PlateRenderer
{
    private GlyphLibrary $glyphs;

    public function __construct(?GlyphLibrary $glyphs = null)
    {
        $this->glyphs = $glyphs ?? new GlyphLibrary();
    }

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>,execution:int,flag:bool} $parsed
     */
    public function renderSvg(array $parsed): string
    {
        $template = $this->templateFor($parsed);
        $layout = $template->layout($parsed);
        $w = $template->widthMm();
        $h = $template->heightMm();

        $parts = [];
        $parts[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $parts[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $this->f($w) . 'mm" height="'
            . $this->f($h) . 'mm" viewBox="0 0 ' . $this->f($w) . ' ' . $this->f($h) . '" '
            . 'fill="none">';
        $fontCss = $this->glyphs->svgFontFaceCss();
        if ($fontCss !== '') {
            $parts[] = '<defs><style type="text/css"><![CDATA[' . $fontCss . ']]></style></defs>';
        }

        foreach ($layout as $cmd) {
            $parts[] = $this->cmdToSvg($cmd);
        }

        $parts[] = '</svg>';
        return implode("\n", array_filter($parts, static fn($p) => $p !== ''));
    }

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>,execution:int,flag:bool} $parsed
     */
    public function templateFor(array $parsed): AbstractPlate
    {
        return match ($parsed['type']) {
            PlateTypes::CAR => new CarPlate($this->glyphs),
            PlateTypes::TRAILER => new TrailerPlate($this->glyphs),
            PlateTypes::MOTO => (function () use ($parsed) {
                $t = new MotorcyclePlate($this->glyphs);
                $t->setExecution((int) ($parsed['execution'] ?? 1));
                return $t;
            })(),
            default => throw new PlateException('Неизвестный тип для рендера.'),
        };
    }

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>,execution:int,flag:bool} $parsed
     * @return array{template: AbstractPlate, layout: list<array<string,mixed>>}
     */
    public function buildLayout(array $parsed): array
    {
        $template = $this->templateFor($parsed);
        return [
            'template' => $template,
            'layout' => $template->layout($parsed),
        ];
    }

    public function glyphs(): GlyphLibrary
    {
        return $this->glyphs;
    }

    /** @param array<string, mixed> $cmd */
    private function cmdToSvg(array $cmd): string
    {
        $op = $cmd['op'] ?? '';
        return match ($op) {
            'rounded_rect' => $this->svgRoundedRect($cmd),
            'line' => $this->svgLine($cmd),
            'glyph' => $this->glyphs->svgUse(
                (string) $cmd['char'],
                (float) $cmd['x'],
                (float) $cmd['y'],
                (float) $cmd['height'],
                (string) ($cmd['color'] ?? '#000')
            ),
            'flag' => $this->svgFlag($cmd),
            'rus' => $this->svgRusText($cmd),
            default => '',
        };
    }

    /** @param array<string, mixed> $cmd */
    private function svgRusText(array $cmd): string
    {
        $x = (float) $cmd['x'];
        $y = (float) $cmd['y'];
        $height = (float) $cmd['height'];
        $parts = ['<g>'];
        foreach (['R', 'U', 'S'] as $ch) {
            $parts[] = $this->glyphs->svgUse($ch, $x, $y, $height, '#000000');
            $x += $this->glyphs->advanceWidth($ch, $height) * 0.85;
        }
        $parts[] = '</g>';
        return implode('', $parts);
    }

    /** @param array<string, mixed> $cmd */
    private function svgRoundedRect(array $cmd): string
    {
        $attrs = [
            'x' => $this->f((float) $cmd['x']),
            'y' => $this->f((float) $cmd['y']),
            'width' => $this->f((float) $cmd['w']),
            'height' => $this->f((float) $cmd['h']),
            'rx' => $this->f((float) ($cmd['rx'] ?? 0)),
            'ry' => $this->f((float) ($cmd['rx'] ?? 0)),
        ];
        if (!empty($cmd['fill'])) {
            $attrs['fill'] = $this->safeColor((string) $cmd['fill']);
        } else {
            $attrs['fill'] = 'none';
        }
        if (!empty($cmd['stroke']) && (float) ($cmd['strokeWidth'] ?? 0) > 0) {
            $attrs['stroke'] = $this->safeColor((string) $cmd['stroke']);
            $attrs['stroke-width'] = $this->f((float) $cmd['strokeWidth']);
        }
        return '<rect ' . $this->attrs($attrs) . '/>';
    }

    /** @param array<string, mixed> $cmd */
    private function svgLine(array $cmd): string
    {
        $attrs = [
            'x1' => $this->f((float) $cmd['x1']),
            'y1' => $this->f((float) $cmd['y1']),
            'x2' => $this->f((float) $cmd['x2']),
            'y2' => $this->f((float) $cmd['y2']),
            'stroke' => $this->safeColor((string) ($cmd['stroke'] ?? '#000')),
            'stroke-width' => $this->f((float) ($cmd['strokeWidth'] ?? 1)),
            'stroke-linecap' => 'square',
        ];
        return '<line ' . $this->attrs($attrs) . '/>';
    }

    /** @param array<string, mixed> $cmd */
    private function svgFlag(array $cmd): string
    {
        $x = (float) $cmd['x'];
        $y = (float) $cmd['y'];
        $w = (float) $cmd['w'];
        $h = (float) $cmd['h'];
        $sh = $h / 3;
        $parts = [];
        $parts[] = '<g>';
        $parts[] = '<rect x="' . $this->f($x) . '" y="' . $this->f($y) . '" width="'
            . $this->f($w) . '" height="' . $this->f($sh) . '" fill="#ffffff" stroke="#000000" stroke-width="0.3"/>';
        $parts[] = '<rect x="' . $this->f($x) . '" y="' . $this->f($y + $sh) . '" width="'
            . $this->f($w) . '" height="' . $this->f($sh) . '" fill="#0039A6"/>';
        $parts[] = '<rect x="' . $this->f($x) . '" y="' . $this->f($y + 2 * $sh) . '" width="'
            . $this->f($w) . '" height="' . $this->f($sh) . '" fill="#D52B1E"/>';
        $parts[] = '<rect x="' . $this->f($x) . '" y="' . $this->f($y) . '" width="'
            . $this->f($w) . '" height="' . $this->f($h) . '" fill="none" stroke="#000000" stroke-width="0.4"/>';
        $parts[] = '</g>';
        return implode('', $parts);
    }

    /** @param array<string, string> $attrs */
    private function attrs(array $attrs): string
    {
        $out = [];
        foreach ($attrs as $k => $v) {
            $out[] = $k . '="' . htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
        }
        return implode(' ', $out);
    }

    private function safeColor(string $color): string
    {
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color)) {
            return $color;
        }
        return '#000000';
    }

    private function f(float $n): string
    {
        return rtrim(rtrim(sprintf('%.4f', $n), '0'), '.');
    }
}
