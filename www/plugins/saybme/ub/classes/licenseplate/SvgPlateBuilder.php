<?php namespace Saybme\Ub\Classes\LicensePlate;

/**
 * Генератор: рамка из SVG-шаблона, символы в <text>/<tspan>, шрифт RoadNumbers.
 */
class SvgPlateBuilder
{
    private const MAIN_X = [10.0, 15.0, 20.0, 25.0, 30.0, 36.0];
    private const MAIN_Y = 10.5435;
    private const MAIN_SIZE = 9.0;

    private const REG2_X = [51.6, 56.5];
    private const REG2_Y = 8.2;
    private const REG2_SIZE = 6.5;

    private const REG3_X = [50.8, 54.2, 57.6];
    private const REG3_Y = 8.0;
    private const REG3_SIZE = 5.5;

    /** Латиница (валидатор) → строчная кириллица как в шаблоне. */
    private const TO_CYR_LOWER = [
        'A' => 'а', 'B' => 'в', 'E' => 'е', 'K' => 'к',
        'M' => 'м', 'H' => 'н', 'O' => 'о', 'P' => 'р',
        'C' => 'с', 'T' => 'т', 'Y' => 'у', 'X' => 'х',
    ];

    private string $template;
    private static ?string $fontCss = null;

    public function __construct()
    {
        $path = __DIR__ . '/templates/russia_type1.svg';
        if (!is_file($path)) {
            throw new PlateException('Нет шаблона russia_type1.svg');
        }
        $this->template = (string) file_get_contents($path);
    }

    /**
     * @param array{type:string,number:string,region:string,chars:list<string>} $parsed
     */
    public function build(array $parsed): string
    {
        return match ($parsed['type']) {
            PlateTypes::CAR, PlateTypes::TRAILER => $this->buildFromTemplate($parsed['chars']),
            PlateTypes::MOTO => $this->buildMoto($parsed['chars'], $parsed['region']),
            default => throw new PlateException('Неизвестный тип.'),
        };
    }

    /** @param list<string> $chars */
    private function buildFromTemplate(array $chars): string
    {
        $main = array_slice($chars, 0, 6);
        $regs = array_slice($chars, 6);

        $texts = '';
        foreach ($main as $i => $ch) {
            if (!isset(self::MAIN_X[$i])) {
                break;
            }
            $texts .= $this->textNode(self::MAIN_X[$i], self::MAIN_Y, self::MAIN_SIZE, $ch);
        }

        $regX = count($regs) >= 3 ? self::REG3_X : self::REG2_X;
        $regY = count($regs) >= 3 ? self::REG3_Y : self::REG2_Y;
        $regSize = count($regs) >= 3 ? self::REG3_SIZE : self::REG2_SIZE;
        foreach ($regs as $i => $ch) {
            if (!isset($regX[$i])) {
                break;
            }
            $texts .= $this->textNode($regX[$i], $regY, $regSize, $ch);
        }

        $svg = str_replace(
            ["<!-- MAIN:6 -->\n", "<!-- REGION -->\n"],
            [$texts, ''],
            $this->template
        );

        // встраиваем RoadNumbers
        $svg = preg_replace(
            '/<defs>/',
            '<defs><style type="text/css"><![CDATA[' . $this->fontFaceCss() . ']]></style>',
            $svg,
            1
        );

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $svg;
    }

    private function textNode(float $x, float $y, float $size, string $ch): string
    {
        $ch = $this->displayChar($ch);
        $safe = htmlspecialchars($ch, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return sprintf(
            '<text fill="black" style="white-space: pre" xml:space="preserve" font-family="RoadNumbers" font-size="%s" letter-spacing="0em">'
            . '<tspan x="%s" y="%s">%s</tspan></text>' . "\n",
            $this->f($size),
            $this->f($x),
            $this->f($y),
            $safe
        );
    }

    /** Цифры как есть, буквы — строчная кириллица. */
    private function displayChar(string $ch): string
    {
        $ch = strtoupper($ch);
        if (isset(self::TO_CYR_LOWER[$ch])) {
            return self::TO_CYR_LOWER[$ch];
        }
        return $ch;
    }

    /** @param list<string> $chars */
    private function buildMoto(array $chars, string $region): string
    {
        $w = 190;
        $h = 145;
        $parts = [];
        $parts[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $parts[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" fill="none">',
            $w,
            $h,
            $w,
            $h
        );
        $parts[] = '<defs><style type="text/css"><![CDATA[' . $this->fontFaceCss() . ']]></style></defs>';
        $parts[] = sprintf('<rect width="%d" height="%d" rx="10" fill="#fff"/>', $w, $h);
        $parts[] = '<rect x="1.5" y="1.5" width="187" height="142" rx="8" fill="none" stroke="#000" stroke-width="3"/>';
        $parts[] = '<line x1="8" y1="70" x2="182" y2="70" stroke="#000" stroke-width="2"/>';

        $top = array_slice($chars, 0, 4);
        $x = 20.0;
        foreach ($top as $ch) {
            $parts[] = $this->textNode($x, 55, 42, $ch);
            $x += 38;
        }
        $bot = array_slice($chars, 4, 2);
        $x = 16.0;
        foreach ($bot as $ch) {
            $parts[] = $this->textNode($x, 120, 36, $ch);
            $x += 34;
        }
        $parts[] = '<line x1="90" y1="78" x2="90" y2="142" stroke="#000" stroke-width="2.5"/>';
        $regs = array_slice($chars, 6);
        $x = 100.0;
        $sz = strlen($region) >= 3 ? 28.0 : 34.0;
        foreach ($regs as $ch) {
            $parts[] = $this->textNode($x, 118, $sz, $ch);
            $x += $sz * 0.7;
        }
        $parts[] = '<text x="118" y="95" font-family="RoadNumbers" font-size="10" fill="#000">rus</text>';
        $parts[] = '<rect x="150" y="82" width="22" height="14" fill="#fff" stroke="#000" stroke-width="0.5"/>';
        $parts[] = '<rect x="150" y="86.7" width="22" height="4.7" fill="#0066B3"/>';
        $parts[] = '<rect x="150" y="91.3" width="22" height="4.7" fill="#ED1C24"/>';
        $parts[] = '</svg>';
        return implode("\n", $parts);
    }

    private function fontFaceCss(): string
    {
        if (self::$fontCss !== null) {
            return self::$fontCss;
        }
        $path = __DIR__ . '/fonts/RoadNumbers2.0.ttf';
        if (!is_file($path)) {
            throw new PlateException('Нет fonts/RoadNumbers2.0.ttf');
        }
        $b64 = base64_encode((string) file_get_contents($path));
        self::$fontCss = "@font-face{font-family:'RoadNumbers';"
            . "src:url('data:font/truetype;charset=utf-8;base64,{$b64}') format('truetype');"
            . 'font-weight:normal;font-style:normal;}';
        return self::$fontCss;
    }

    private function f(float $n): string
    {
        return rtrim(rtrim(sprintf('%.4f', $n), '0'), '.') ?: '0';
    }
}
