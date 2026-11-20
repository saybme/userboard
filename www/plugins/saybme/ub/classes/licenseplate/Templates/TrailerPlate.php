<?php namespace Saybme\Ub\Classes\LicensePlate\Templates;

/**
 * Тип 2 — прицеп, 520×112 мм, формат LLDDDD + регион.
 * Буквы 58 мм, цифры 76 мм.
 */
class TrailerPlate extends AbstractPlate
{
    public const WIDTH_MM = 520.0;
    public const HEIGHT_MM = 112.0;

    public function widthMm(): float
    {
        return self::WIDTH_MM;
    }

    public function heightMm(): float
    {
        return self::HEIGHT_MM;
    }

    public function layout(array $parsed): array
    {
        $cmds = $this->baseFrame();
        $h = self::HEIGHT_MM;
        $b = self::BORDER_MM;
        $region = $parsed['region'];
        $chars = $parsed['chars']; // L L D D D D
        $regionDigits = strlen($region);
        $flag = (bool) ($parsed['flag'] ?? true);

        $regionW = $regionDigits >= 3 ? 105.0 : 90.0;
        $sepX = self::WIDTH_MM - $regionW;

        $cmds[] = $this->separator($sepX, $b + 1, $h - $b - 1);

        $digitH = self::MAIN_DIGIT_HEIGHT_MM;
        $letterH = self::MAIN_LETTER_HEIGHT_MM;
        $digitY = ($h - $digitH) / 2;

        $heights = [
            $letterH, $letterH, $digitH, $digitH, $digitH, $digitH,
        ];
        $gaps = [8.0, 12.0, 6.0, 6.0, 6.0];

        $rowW = 0.0;
        for ($i = 0; $i < 6; $i++) {
            $rowW += $this->glyphs->advanceWidth($chars[$i], $heights[$i]);
            if ($i < 5) {
                $rowW += $gaps[$i];
            }
        }

        $avail = $sepX - $b - 8.0;
        $startX = $b + 10.0 + max(0, ($avail - $rowW) * 0.4);

        $x = $startX;
        for ($i = 0; $i < 6; $i++) {
            $chH = $heights[$i];
            $y = $digitY + ($digitH - $chH) / 2;
            $cmds[] = [
                'op' => 'glyph',
                'char' => $chars[$i],
                'x' => $x,
                'y' => $y,
                'height' => $chH,
                'color' => '#000000',
            ];
            $x += $this->glyphs->advanceWidth($chars[$i], $chH) + ($gaps[$i] ?? 0);
        }

        $cmds = array_merge(
            $cmds,
            $this->regionPanel(
                $region,
                $sepX + 2,
                $b + 2,
                $regionW - 4,
                $h - 2 * $b - 4,
                $flag,
                'vertical'
            )
        );

        return $cmds;
    }
}
