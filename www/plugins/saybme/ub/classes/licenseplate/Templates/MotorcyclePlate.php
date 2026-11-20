<?php namespace Saybme\Ub\Classes\LicensePlate\Templates;

/**
 * Тип 4 — мотоцикл.
 *
 * Исполнение 1 (по умолчанию): 190×145 мм — номер сверху, регион снизу.
 * Исполнение 2: 190×175 мм — увеличенная высота, иное размещение RUS/флага
 * (приближённо по А.6/А.8; сверить с официальными чертежами).
 *
 * Формат: DDDDLL + регион.
 */
class MotorcyclePlate extends AbstractPlate
{
    public const WIDTH_MM = 190.0;
    public const HEIGHT_EXEC1_MM = 145.0;
    public const HEIGHT_EXEC2_MM = 175.0;

    /** Высота основных знаков на мото-знаке меньше, чем 76 мм легкового */
    public const MOTO_MAIN_HEIGHT_MM = 48.0;
    public const MOTO_MAIN_HEIGHT_EXEC2_MM = 52.0;

    private int $execution = 1;

    public function setExecution(int $execution): void
    {
        $this->execution = $execution === 2 ? 2 : 1;
    }

    public function widthMm(): float
    {
        return self::WIDTH_MM;
    }

    public function heightMm(): float
    {
        return $this->execution === 2 ? self::HEIGHT_EXEC2_MM : self::HEIGHT_EXEC1_MM;
    }

    public function layout(array $parsed): array
    {
        $this->setExecution((int) ($parsed['execution'] ?? 1));
        $cmds = $this->baseFrame();

        $w = $this->widthMm();
        $h = $this->heightMm();
        $b = self::BORDER_MM;
        $region = $parsed['region'];
        $chars = $parsed['chars']; // D D D D L L
        $flag = (bool) ($parsed['flag'] ?? true);

        $mainH = $this->execution === 2 ? self::MOTO_MAIN_HEIGHT_EXEC2_MM : self::MOTO_MAIN_HEIGHT_MM;
        $gap = 3.5;
        $rowW = 0.0;
        for ($i = 0; $i < 6; $i++) {
            $rowW += $this->glyphs->advanceWidth($chars[$i], $mainH);
            if ($i < 5) {
                $rowW += $gap;
            }
        }

        // Горизонтальный разделитель: верх — номер, низ — регион
        if ($this->execution === 1) {
            $sepY = 88.0;
            $mainY = $b + 8.0;
            $regionPanelY = $sepY + 2;
            $regionPanelH = $h - $regionPanelY - $b - 2;
        } else {
            // Исполнение 2: выше, больше места под RUS/флаг
            $sepY = 100.0;
            $mainY = $b + 10.0;
            $regionPanelY = $sepY + 2;
            $regionPanelH = $h - $regionPanelY - $b - 2;
        }

        $cmds[] = $this->hSeparator($sepY, $b + 1, $w - $b - 1);

        $startX = ($w - $rowW) / 2;
        $x = $startX;
        for ($i = 0; $i < 6; $i++) {
            $cmds[] = [
                'op' => 'glyph',
                'char' => $chars[$i],
                'x' => $x,
                'y' => $mainY,
                'height' => $mainH,
                'color' => '#000000',
            ];
            $x += $this->glyphs->advanceWidth($chars[$i], $mainH) + $gap;
        }

        // Нижняя панель региона
        if ($this->execution === 1) {
            $cmds = array_merge(
                $cmds,
                $this->regionPanelMoto(
                    $region,
                    $b + 4,
                    $regionPanelY,
                    $w - 2 * $b - 8,
                    $regionPanelH,
                    $flag,
                    false
                )
            );
        } else {
            // Exec 2: цифры региона крупнее по центру, RUS+флаг под ними / сбоку
            $cmds = array_merge(
                $cmds,
                $this->regionPanelMoto(
                    $region,
                    $b + 4,
                    $regionPanelY,
                    $w - 2 * $b - 8,
                    $regionPanelH,
                    $flag,
                    true
                )
            );
        }

        return $cmds;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function regionPanelMoto(
        string $region,
        float $panelX,
        float $panelY,
        float $panelW,
        float $panelH,
        bool $drawFlag,
        bool $exec2
    ): array {
        $cmds = [];
        $digits = preg_split('//u', $region, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $n = count($digits);
        $digitH = $exec2
            ? ($n >= 3 ? 34.0 : 40.0)
            : ($n >= 3 ? 28.0 : 34.0);
        $gw = $this->glyphs->widthForHeight($digitH);
        $gap = $n >= 3 ? 2.0 : 3.0;
        $rowW = $n * $gw + max(0, $n - 1) * $gap;

        $rusH = $exec2 ? 10.0 : 8.0;
        $flagW = $exec2 ? 30.0 : 24.0;
        $flagH = $flagW * (2 / 3);
        $rusW = 3 * $this->glyphs->widthForHeight($rusH) + 2 * 1.2;

        if ($exec2) {
            // Цифры сверху нижней зоны, RUS+флаг снизу по центру
            $digitY = $panelY + 4;
            $digitX = $panelX + ($panelW - $rowW) / 2;
            $cmds = array_merge($cmds, $this->glyphRow($digits, $digitX, $digitY, $digitH, $gap));

            $blockH = max($flagH, $rusH);
            $bottomY = $panelY + $panelH - 6 - $blockH;
            $totalW = $rusW + 4 + ($drawFlag ? $flagW : 0);
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
                    'x' => $bx + $rusW + 4,
                    'y' => $bottomY + ($blockH - $flagH) / 2,
                    'w' => $flagW,
                    'h' => $flagH,
                ];
            }
        } else {
            // Exec 1: цифры слева, RUS+флаг справа
            $digitY = $panelY + ($panelH - $digitH) / 2;
            $digitX = $panelX + 4;
            $cmds = array_merge($cmds, $this->glyphRow($digits, $digitX, $digitY, $digitH, $gap));

            $right = $panelX + $panelW - 2;
            if ($drawFlag) {
                $cmds[] = [
                    'op' => 'flag',
                    'x' => $right - $flagW,
                    'y' => $panelY + ($panelH - $flagH) / 2,
                    'w' => $flagW,
                    'h' => $flagH,
                ];
                $right -= $flagW + 3;
            }
            $cmds[] = [
                'op' => 'rus',
                'x' => $right - $rusW,
                'y' => $panelY + ($panelH - $rusH) / 2,
                'height' => $rusH,
            ];
        }

        return $cmds;
    }
}
