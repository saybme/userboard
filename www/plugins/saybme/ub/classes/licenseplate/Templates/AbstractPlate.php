<?php namespace Saybme\Ub\Classes\LicensePlate\Templates;

use Saybme\Ub\Classes\LicensePlate\Glyphs\GlyphLibrary;

/**
 * Базовый шаблон знака. Единицы SVG user units = миллиметры.
 *
 * Константы по ГОСТ Р 50577 (табл. 1 / прил. А — ориентиры):
 * - окантовка 3±0,5 мм
 * - высота основных знаков типа 1/2: 76 мм
 * - толщина штриха при 76 мм: ≥11 мм
 * - высота цифр региона (2 знака): 45 мм; для 3 знаков — уменьшенная ~36–40 мм
 * - радиус скругления углов ~7–8 мм
 *
 * Точные миллиметровые отступы по чертежам А.1/А.2/А.6/А.8 следует сверить
 * с официальным стандартом — здесь визуально правдоподобная реконструкция.
 */
abstract class AbstractPlate
{
    public const BORDER_MM = 3.0;
    public const CORNER_RADIUS_MM = 7.5;
    /** Высота цифр типа 1/2 (табл. 1 / типовая схема А.1) */
    public const MAIN_DIGIT_HEIGHT_MM = 76.0;
    /** Высота букв типа 1/2 — меньше цифр */
    public const MAIN_LETTER_HEIGHT_MM = 58.0;
    /** @deprecated используйте MAIN_DIGIT_HEIGHT_MM */
    public const MAIN_CHAR_HEIGHT_MM = 76.0;
    /** Код региона: 2 цифры */
    public const REGION_CHAR_HEIGHT_2_MM = 58.0;
    /** Код региона: 3 цифры (ужимаем, чтобы влезли) */
    public const REGION_CHAR_HEIGHT_3_MM = 45.0;
    public const SEPARATOR_WIDTH_MM = 2.0;

    protected GlyphLibrary $glyphs;

    public function __construct(?GlyphLibrary $glyphs = null)
    {
        $this->glyphs = $glyphs ?? new GlyphLibrary();
    }

    abstract public function widthMm(): float;

    abstract public function heightMm(): float;

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>,execution:int,flag:bool} $parsed
     * @return list<array<string, mixed>> Команды отрисовки (общие для SVG и GD)
     */
    abstract public function layout(array $parsed): array;

    public function glyphs(): GlyphLibrary
    {
        return $this->glyphs;
    }

    /**
     * Базовая «рамка» знака: белое поле + чёрная окантовка.
     *
     * @return list<array<string, mixed>>
     */
    protected function baseFrame(): array
    {
        $w = $this->widthMm();
        $h = $this->heightMm();
        $r = self::CORNER_RADIUS_MM;
        $b = self::BORDER_MM;

        return [
            [
                'op' => 'rounded_rect',
                'x' => 0,
                'y' => 0,
                'w' => $w,
                'h' => $h,
                'rx' => $r,
                'fill' => '#ffffff',
                'stroke' => null,
                'strokeWidth' => 0,
            ],
            // Окантовка как inset rounded rect (центр обводки на b/2 от края)
            [
                'op' => 'rounded_rect',
                'x' => $b / 2,
                'y' => $b / 2,
                'w' => $w - $b,
                'h' => $h - $b,
                'rx' => max(0.5, $r - $b / 2),
                'fill' => null,
                'stroke' => '#000000',
                'strokeWidth' => $b,
            ],
        ];
    }

    /**
     * @param list<string> $chars
     * @return list<array<string, mixed>>
     */
    protected function glyphRow(
        array $chars,
        float $startX,
        float $baselineY,
        float $height,
        float $gap,
        string $color = '#000000'
    ): array {
        $cmds = [];
        $x = $startX;
        foreach ($chars as $ch) {
            $cmds[] = [
                'op' => 'glyph',
                'char' => $ch,
                'x' => $x,
                'y' => $baselineY,
                'height' => $height,
                'color' => $color,
            ];
            $x += $this->glyphs->advanceWidth($ch, $height) + $gap;
        }
        return $cmds;
    }

    /**
     * Панель региона: цифры + RUS + флаг.
     *
     * @return list<array<string, mixed>>
     */
    protected function regionPanel(
        string $region,
        float $panelX,
        float $panelY,
        float $panelW,
        float $panelH,
        bool $drawFlag,
        string $orientation = 'vertical'
    ): array {
        $cmds = [];
        $digits = preg_split('//u', $region, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $n = count($digits);
        $digitH = $n >= 3 ? self::REGION_CHAR_HEIGHT_3_MM : self::REGION_CHAR_HEIGHT_2_MM;
        $gap = $n >= 3 ? 2.0 : 3.0;
        $rowW = 0.0;
        foreach ($digits as $d) {
            $rowW += $this->glyphs->advanceWidth($d, $digitH);
        }
        $rowW += max(0, $n - 1) * $gap;

        if ($orientation === 'vertical') {
            // Цифры сверху панели
            $digitY = $panelY + 6.0;
            $digitX = $panelX + ($panelW - $rowW) / 2;
            $cmds = array_merge($cmds, $this->glyphRow($digits, $digitX, $digitY, $digitH, $gap));

            $flagW = min(28.0, $panelW * 0.42);
            $flagH = $flagW * (2 / 3);
            $rusH = 9.0;
            $blockH = max($flagH, $rusH);
            $bottomY = $panelY + $panelH - 8.0 - $blockH;

            $rusW = $this->glyphs->advanceWidth('R', $rusH)
                + $this->glyphs->advanceWidth('U', $rusH)
                + $this->glyphs->advanceWidth('S', $rusH);
            $totalW = $rusW + 3.0 + $flagW;
            $bx = $panelX + ($panelW - $totalW) / 2;

            $cmds[] = [
                'op' => 'rus',
                'x' => $bx,
                'y' => $bottomY + ($blockH - $rusH) / 2,
                'height' => $rusH,
            ];
            if ($drawFlag) {
                $cmds[] = [
                    'op' => 'flag',
                    'x' => $bx + $rusW + 3.0,
                    'y' => $bottomY + ($blockH - $flagH) / 2,
                    'w' => $flagW,
                    'h' => $flagH,
                ];
            }
        } else {
            // Горизонтально: цифры слева, RUS/флаг справа (moto exec 2 approx)
            $digitY = $panelY + ($panelH - $digitH) / 2;
            $digitX = $panelX + 4.0;
            $cmds = array_merge($cmds, $this->glyphRow($digits, $digitX, $digitY, $digitH, $gap));

            $flagW = 22.0;
            $flagH = $flagW * (2 / 3);
            $rusH = 8.0;
            $right = $panelX + $panelW - 4.0;
            if ($drawFlag) {
                $cmds[] = [
                    'op' => 'flag',
                    'x' => $right - $flagW,
                    'y' => $panelY + ($panelH - $flagH) / 2,
                    'w' => $flagW,
                    'h' => $flagH,
                ];
                $right -= $flagW + 3.0;
            }
            $rusW = 3 * $this->glyphs->widthForHeight($rusH) + 2 * 1.0;
            $cmds[] = [
                'op' => 'rus',
                'x' => $right - $rusW,
                'y' => $panelY + ($panelH - $rusH) / 2,
                'height' => $rusH,
            ];
        }

        return $cmds;
    }

    protected function separator(float $x, float $y1, float $y2): array
    {
        return [
            'op' => 'line',
            'x1' => $x,
            'y1' => $y1,
            'x2' => $x,
            'y2' => $y2,
            'stroke' => '#000000',
            'strokeWidth' => self::SEPARATOR_WIDTH_MM,
        ];
    }

    protected function hSeparator(float $y, float $x1, float $x2): array
    {
        return [
            'op' => 'line',
            'x1' => $x1,
            'y1' => $y,
            'x2' => $x2,
            'y2' => $y,
            'stroke' => '#000000',
            'strokeWidth' => self::SEPARATOR_WIDTH_MM,
        ];
    }
}
