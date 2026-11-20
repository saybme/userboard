<?php namespace Saybme\Ub\Classes\LicensePlate\Glyphs;

/**
 * Векторные глифы российских номеров (реконструкция под ГОСТ Р 50577, прил. Б/В).
 *
 * Без внешних TTF: SVG path + растеризация в GD.
 * Кириллица АВЕКМНОРСТУХ отображается теми же контурами, что латиница A B E K M H O P C T Y X.
 *
 * Координаты глифа: 0..100 × 0..160.
 */
class GlyphLibrary
{
    public const BOX_W = 100.0;
    public const BOX_H = 160.0;

    public const CYR_TO_LATIN = [
        'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K',
        'М' => 'M', 'Н' => 'H', 'О' => 'O', 'Р' => 'P',
        'С' => 'C', 'Т' => 'T', 'У' => 'Y', 'Х' => 'X',
        'а' => 'A', 'в' => 'B', 'е' => 'E', 'к' => 'K',
        'м' => 'M', 'н' => 'H', 'о' => 'O', 'р' => 'P',
        'с' => 'C', 'т' => 'T', 'у' => 'Y', 'х' => 'X',
    ];

    /** @var array<string,float> */
    private array $advanceCache = [];

    public function svgFontFaceCss(): string
    {
        return '';
    }

    public function toFontChar(string $char): string
    {
        $char = trim($char);
        if ($char === '') {
            return '';
        }
        if (isset(self::CYR_TO_LATIN[$char])) {
            return self::CYR_TO_LATIN[$char];
        }
        $up = mb_strtoupper($char, 'UTF-8');
        if (isset(self::CYR_TO_LATIN[$up])) {
            return self::CYR_TO_LATIN[$up];
        }
        if (preg_match('/^[0-9A-Za-z]$/', $char)) {
            return strtoupper($char);
        }
        throw new \InvalidArgumentException('Нет глифа для символа: ' . $char);
    }

    public function isDigit(string $char): bool
    {
        return (bool) preg_match('/^\d$/', $this->toFontChar($char));
    }

    public function advanceWidth(string $char, float $height): float
    {
        $key = $this->toFontChar($char) . ':' . round($height, 3);
        if (isset($this->advanceCache[$key])) {
            return $this->advanceCache[$key];
        }
        $unit = $this->advances()[$this->toFontChar($char)] ?? 78.0;
        return $this->advanceCache[$key] = $unit * ($height / self::BOX_H);
    }

    public function widthForHeight(float $height): float
    {
        return $this->advanceWidth('0', $height);
    }

    public function pathD(string $char): string
    {
        $ch = $this->toFontChar($char);
        $paths = $this->paths();
        if (!isset($paths[$ch])) {
            throw new \InvalidArgumentException('Нет path для символа: ' . $char);
        }
        return $paths[$ch];
    }

    public function svgUse(string $char, float $x, float $y, float $height, string $color = '#000'): string
    {
        $d = htmlspecialchars($this->pathD($char), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $color = $this->safeColor($color);
        $s = $height / self::BOX_H;
        return '<path fill="' . $color . '" fill-rule="evenodd" d="' . $d . '" transform="translate('
            . $this->f($x) . ' ' . $this->f($y) . ') scale(' . $this->f($s) . ')"/>';
    }

    /**
     * @param resource|\GdImage $im
     */
    public function drawOnGd($im, string $char, float $x, float $y, float $height, int $color, ?int $holeColor = null): void
    {
        $s = $height / self::BOX_H;
        $subs = $this->pathToSubpolygons($this->pathD($char), $x, $y, $s, $s);
        if ($subs === []) {
            return;
        }
        $this->fillPolygon($im, $subs[0], $color);
        if ($holeColor !== null) {
            for ($i = 1, $n = count($subs); $i < $n; $i++) {
                $this->fillPolygon($im, $subs[$i], $holeColor);
            }
        }
    }

    /** @return array<string, float> */
    private function advances(): array
    {
        return [
            '0' => 82, '1' => 62, '2' => 82, '3' => 82, '4' => 86,
            '5' => 82, '6' => 82, '7' => 78, '8' => 82, '9' => 82,
            'A' => 86, 'B' => 82, 'C' => 84, 'E' => 78, 'H' => 86,
            'K' => 84, 'M' => 96, 'O' => 88, 'P' => 80, 'T' => 82,
            'X' => 84, 'Y' => 84, 'R' => 82, 'U' => 86, 'S' => 78, 'D' => 86,
        ];
    }

    /**
     * Заполненные контуры (evenodd). Формы — рубленый номерной стиль.
     * @return array<string, string>
     */
    private function paths(): array
    {
        // Толстый штрих ~22–26, высота почти 160, поля сверху/снизу ~6–8
        return [
            // Цифры
            '0' => 'M18 8 H82 A38 72 0 0 1 82 152 H18 A38 72 0 0 1 18 8 Z M42 40 H58 A10 40 0 0 1 58 120 H42 A10 40 0 0 1 42 40 Z',
            '1' => 'M38 8 H62 V152 H38 V52 L22 64 V40 Z',
            '2' => 'M12 38 C12 16 32 6 50 6 C72 6 88 20 88 42 C88 64 74 76 52 94 L28 114 V126 H88 V152 H8 V116 L46 84 C62 72 64 62 64 46 C64 34 56 28 46 28 C34 28 28 36 28 46 Z',
            '3' => 'M16 18 C20 6 38 4 52 4 C74 4 90 18 90 40 C90 56 80 68 64 74 C82 80 94 94 94 114 C94 140 72 154 48 154 C26 154 10 142 6 124 H34 C38 134 44 138 52 138 C66 138 72 128 72 114 C72 100 62 92 46 92 H34 V68 H48 C62 68 68 58 68 46 C68 34 60 26 48 26 C38 26 32 32 30 42 Z',
            '4' => 'M58 8 H82 V152 H58 V108 H8 V84 L52 8 Z M32 84 H58 V48 Z',
            '5' => 'M22 8 H86 V34 H46 V62 H58 C82 62 96 78 96 104 C96 134 76 154 48 154 C26 154 10 140 8 120 H36 C38 130 44 136 52 136 C66 136 72 124 72 110 C72 94 64 86 50 86 H22 Z',
            '6' => 'M78 20 C70 8 54 6 42 6 C18 6 6 30 6 70 V100 C6 136 26 154 52 154 C78 154 96 134 96 108 C96 82 78 64 54 64 C40 64 30 70 24 80 V66 C24 40 32 28 46 28 C56 28 64 34 68 44 Z M34 96 C38 86 46 82 56 82 C68 82 74 92 74 106 C74 122 66 132 52 132 C40 132 34 122 34 108 Z',
            '7' => 'M12 8 H90 V36 L42 152 H14 L60 36 H12 Z',
            '8' => 'M50 6 C74 6 90 20 90 42 C90 58 80 70 66 76 C82 82 96 96 96 118 C96 142 74 154 50 154 C26 154 4 142 4 118 C4 96 18 82 34 76 C20 70 10 58 10 42 C10 20 26 6 50 6 Z M50 30 C40 30 34 36 34 46 C34 56 40 62 50 62 C60 62 66 56 66 46 C66 36 60 30 50 30 Z M50 94 C38 94 30 104 30 116 C30 130 38 138 50 138 C62 138 70 130 70 116 C70 104 62 94 50 94 Z',
            '9' => 'M22 140 C30 152 46 154 58 154 C82 154 94 130 94 90 V60 C94 24 74 6 48 6 C22 6 4 26 4 52 C4 78 22 96 46 96 C60 96 70 90 76 80 V94 C76 120 68 132 54 132 C44 132 36 126 32 116 Z M66 64 C62 74 54 78 44 78 C32 78 26 68 26 54 C26 38 34 28 48 28 C60 28 66 38 66 52 Z',

            // Буквы (латиница = кириллические аналоги)
            'A' => 'M8 152 L42 8 H62 L96 152 H70 L64 122 H36 L30 152 Z M42 96 H58 L50 56 Z',
            'B' => 'M12 8 H56 C78 8 90 22 90 42 C90 56 82 66 68 70 C86 74 98 88 98 110 C98 136 78 152 50 152 H12 Z M36 30 V66 H52 C62 66 66 58 66 48 C66 38 60 30 52 30 Z M36 90 V130 H54 C68 130 74 120 74 110 C74 98 66 90 54 90 Z',
            'C' => 'M90 40 C82 16 66 8 48 8 C20 8 6 32 6 80 C6 128 20 152 48 152 C66 152 82 144 90 120 H62 C58 132 52 136 46 136 C34 136 30 118 30 80 C30 42 34 24 46 24 C54 24 60 30 64 40 Z',
            'E' => 'M16 8 H88 V34 H42 V66 H80 V92 H42 V126 H88 V152 H16 Z',
            'H' => 'M12 8 H38 V66 H62 V8 H88 V152 H62 V92 H38 V152 H12 Z',
            'K' => 'M12 8 H38 V70 L66 8 H96 L58 78 L96 152 H66 L38 96 V152 H12 Z',
            'M' => 'M6 152 V8 H30 L50 78 L70 8 H94 V152 H72 V56 L54 120 H46 L28 56 V152 Z',
            'O' => 'M18 8 H82 A38 72 0 0 1 82 152 H18 A38 72 0 0 1 18 8 Z M42 40 H58 A10 40 0 0 1 58 120 H42 A10 40 0 0 1 42 40 Z',
            'P' => 'M14 8 H58 C82 8 96 26 96 52 C96 80 80 98 54 98 H40 V152 H14 Z M40 32 V74 H54 C66 74 70 64 70 52 C70 40 64 32 54 32 Z',
            'T' => 'M8 8 H92 V34 H64 V152 H36 V34 H8 Z',
            'X' => 'M8 8 H36 L50 48 L64 8 H92 L66 80 L92 152 H64 L50 112 L36 152 H8 L34 80 Z',
            'Y' => 'M8 8 H36 L50 52 L64 8 H92 L62 88 V152 H38 V88 Z',
            'R' => 'M14 8 H56 C80 8 94 24 94 50 C94 72 80 88 60 92 L94 152 H66 L40 98 H40 V152 H14 Z M40 32 V74 H54 C66 74 68 64 68 52 C68 40 64 32 54 32 Z',
            'U' => 'M12 8 H38 V100 C38 124 44 136 52 136 C60 136 66 124 66 100 V8 H92 V104 C92 140 74 154 52 154 C30 154 12 140 12 104 Z',
            'S' => 'M86 36 C78 14 62 8 46 8 C22 8 8 24 8 46 C8 68 22 80 48 88 C64 92 70 98 70 108 C70 120 62 130 48 130 C36 130 28 122 26 110 H8 C12 136 28 154 50 154 C76 154 94 136 94 110 C94 86 78 74 52 66 C36 62 32 56 32 46 C32 34 40 28 50 28 C62 28 68 36 70 46 Z',
            'D' => 'M14 8 H48 C78 8 96 32 96 80 C96 128 78 152 48 152 H14 Z M40 34 V126 H50 C66 126 70 110 70 80 C70 50 66 34 50 34 Z',
        ];
    }

    /**
     * @return list<list<array{0:float,1:float}>>
     */
    private function pathToSubpolygons(string $d, float $ox, float $oy, float $sx, float $sy): array
    {
        $tokens = $this->tokenize($d);
        $subpaths = [];
        $current = [];
        $cx = 0.0;
        $cy = 0.0;
        $startX = 0.0;
        $startY = 0.0;
        $prevCmd = '';
        $i = 0;
        $n = count($tokens);

        $map = static function (float $x, float $y) use ($ox, $oy, $sx, $sy): array {
            return [$ox + $x * $sx, $oy + $y * $sy];
        };

        $num = static function () use (&$i, $tokens, $n): float {
            if ($i >= $n || !is_numeric($tokens[$i])) {
                throw new \RuntimeException('Ожидалось число в SVG path.');
            }
            return (float) $tokens[$i++];
        };

        while ($i < $n) {
            $t = $tokens[$i];
            if (preg_match('/^[A-Za-z]$/', $t)) {
                $cmd = $t;
                $i++;
            } else {
                $cmd = ($prevCmd === 'M') ? 'L' : (($prevCmd === 'm') ? 'l' : $prevCmd);
                if ($cmd === '') {
                    break;
                }
            }
            $prevCmd = $cmd;

            switch ($cmd) {
                case 'M':
                    if ($current) {
                        $subpaths[] = $current;
                        $current = [];
                    }
                    $cx = $num();
                    $cy = $num();
                    $startX = $cx;
                    $startY = $cy;
                    $current[] = $map($cx, $cy);
                    break;
                case 'm':
                    if ($current) {
                        $subpaths[] = $current;
                        $current = [];
                    }
                    $cx += $num();
                    $cy += $num();
                    $startX = $cx;
                    $startY = $cy;
                    $current[] = $map($cx, $cy);
                    break;
                case 'L':
                    $cx = $num();
                    $cy = $num();
                    $current[] = $map($cx, $cy);
                    break;
                case 'l':
                    $cx += $num();
                    $cy += $num();
                    $current[] = $map($cx, $cy);
                    break;
                case 'H':
                    $cx = $num();
                    $current[] = $map($cx, $cy);
                    break;
                case 'h':
                    $cx += $num();
                    $current[] = $map($cx, $cy);
                    break;
                case 'V':
                    $cy = $num();
                    $current[] = $map($cx, $cy);
                    break;
                case 'v':
                    $cy += $num();
                    $current[] = $map($cx, $cy);
                    break;
                case 'C':
                    $x1 = $num();
                    $y1 = $num();
                    $x2 = $num();
                    $y2 = $num();
                    $x = $num();
                    $y = $num();
                    foreach ($this->cubic($cx, $cy, $x1, $y1, $x2, $y2, $x, $y, 10) as $p) {
                        $current[] = $map($p[0], $p[1]);
                    }
                    $cx = $x;
                    $cy = $y;
                    break;
                case 'c':
                    $x1 = $cx + $num();
                    $y1 = $cy + $num();
                    $x2 = $cx + $num();
                    $y2 = $cy + $num();
                    $x = $cx + $num();
                    $y = $cy + $num();
                    foreach ($this->cubic($cx, $cy, $x1, $y1, $x2, $y2, $x, $y, 10) as $p) {
                        $current[] = $map($p[0], $p[1]);
                    }
                    $cx = $x;
                    $cy = $y;
                    break;
                case 'A':
                case 'a':
                    // rx ry x-axis-rotation large-arc sweep x y
                    $rx = abs($num());
                    $ry = abs($num());
                    $num(); // rotation ignored
                    $large = (int) $num();
                    $sweep = (int) $num();
                    $x = $cmd === 'A' ? $num() : $cx + $num();
                    $y = $cmd === 'A' ? $num() : $cy + $num();
                    foreach ($this->arcApprox($cx, $cy, $x, $y, $rx, $ry, $large, $sweep, 16) as $p) {
                        $current[] = $map($p[0], $p[1]);
                    }
                    $cx = $x;
                    $cy = $y;
                    break;
                case 'Z':
                case 'z':
                    $current[] = $map($startX, $startY);
                    $cx = $startX;
                    $cy = $startY;
                    if ($current) {
                        $subpaths[] = $current;
                        $current = [];
                    }
                    break;
                default:
                    // неизвестная команда — пропускаем один токен
                    $i++;
                    break;
            }
        }
        if ($current) {
            $subpaths[] = $current;
        }
        return $subpaths;
    }

    /** @return list<string> */
    private function tokenize(string $d): array
    {
        $d = preg_replace('/([MmLlHhVvCcSsQqTtAaZz])/', ' $1 ', $d) ?? $d;
        $d = str_replace(',', ' ', $d);
        $d = preg_replace('/\s+/', ' ', trim($d)) ?? '';
        if ($d === '') {
            return [];
        }
        return explode(' ', $d);
    }

    /**
     * @return list<array{0:float,1:float}>
     */
    private function cubic(
        float $x0,
        float $y0,
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $x3,
        float $y3,
        int $steps
    ): array {
        $out = [];
        for ($s = 1; $s <= $steps; $s++) {
            $t = $s / $steps;
            $u = 1 - $t;
            $x = $u * $u * $u * $x0 + 3 * $u * $u * $t * $x1 + 3 * $u * $t * $t * $x2 + $t * $t * $t * $x3;
            $y = $u * $u * $u * $y0 + 3 * $u * $u * $t * $y1 + 3 * $u * $t * $t * $y2 + $t * $t * $t * $y3;
            $out[] = [$x, $y];
        }
        return $out;
    }

    /**
     * Упрощённая аппроксимация дуги эллипса (для 0/O и отверстий).
     * @return list<array{0:float,1:float}>
     */
    private function arcApprox(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $rx,
        float $ry,
        int $large,
        int $sweep,
        int $steps
    ): array {
        if ($rx < 0.001 || $ry < 0.001) {
            return [[$x2, $y2]];
        }
        // Центр как середина хорды + смещение по нормали (достаточно для наших глифов)
        $mx = ($x1 + $x2) / 2;
        $my = ($y1 + $y2) / 2;
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $len = hypot($dx, $dy);
        if ($len < 0.001) {
            return [[$x2, $y2]];
        }
        $nx = -$dy / $len;
        $ny = $dx / $len;
        $dist = max($rx, $ry) * ($large ? 0.85 : 0.55);
        if ($sweep === 0) {
            $dist = -$dist;
        }
        $cx = $mx + $nx * $dist;
        $cy = $my + $ny * $dist;

        $a1 = atan2($y1 - $cy, $x1 - $cx);
        $a2 = atan2($y2 - $cy, $x2 - $cx);
        if ($sweep === 1 && $a2 < $a1) {
            $a2 += M_PI * 2;
        }
        if ($sweep === 0 && $a2 > $a1) {
            $a2 -= M_PI * 2;
        }

        $out = [];
        for ($s = 1; $s <= $steps; $s++) {
            $t = $s / $steps;
            $a = $a1 + ($a2 - $a1) * $t;
            $out[] = [$cx + cos($a) * $rx, $cy + sin($a) * $ry];
        }
        return $out;
    }

    /**
     * @param resource|\GdImage $im
     * @param list<array{0:float,1:float}> $pts
     */
    private function fillPolygon($im, array $pts, int $color): void
    {
        if (count($pts) < 3) {
            return;
        }
        $flat = [];
        foreach ($pts as $p) {
            $flat[] = (int) round($p[0]);
            $flat[] = (int) round($p[1]);
        }
        imagefilledpolygon($im, $flat, $color);
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
