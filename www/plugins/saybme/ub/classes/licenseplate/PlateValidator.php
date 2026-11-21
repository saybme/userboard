<?php namespace Saybme\Ub\Classes\LicensePlate;

/**
 * Нормализация и проверка номера. Возвращает только безопасные символы A-Z/0-9.
 */
class PlateValidator
{
    public const CYR_TO_LATIN = [
        'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K',
        'М' => 'M', 'Н' => 'H', 'О' => 'O', 'Р' => 'P',
        'С' => 'C', 'Т' => 'T', 'У' => 'Y', 'Х' => 'X',
        'а' => 'A', 'в' => 'B', 'е' => 'E', 'к' => 'K',
        'м' => 'M', 'н' => 'H', 'о' => 'O', 'р' => 'P',
        'с' => 'C', 'т' => 'T', 'у' => 'Y', 'х' => 'X',
    ];

    public const ALLOWED = 'ABEKMHOPCTYX';

    /**
     * @return array{type:string,number:string,region:string,chars:list<string>}
     */
    public function parse(string $type, string $number, string $region): array
    {
        $type = strtolower(trim($type));
        if (!in_array($type, PlateTypes::all(), true)) {
            throw new PlateException('Неизвестный тип: «' . $this->safe($type) . '». Допустимо: car, trailer, moto.');
        }

        $number = $this->normalizeBody($number);
        $region = $this->normalizeRegion($region);

        $ok = match ($type) {
            PlateTypes::CAR => (bool) preg_match('/^[A-Z]\d{3}[A-Z]{2}$/', $number),
            PlateTypes::TRAILER => (bool) preg_match('/^[A-Z]{2}\d{4}$/', $number),
            PlateTypes::MOTO => (bool) preg_match('/^\d{4}[A-Z]{2}$/', $number),
            default => false,
        };

        if (!$ok) {
            $hint = match ($type) {
                PlateTypes::CAR => 'буква + 3 цифры + 2 буквы (А123ВС)',
                PlateTypes::TRAILER => '2 буквы + 4 цифры (АВ1234)',
                PlateTypes::MOTO => '4 цифры + 2 буквы (1234АВ)',
            };
            throw new PlateException('Неверный формат номера. Ожидается: ' . $hint . '.');
        }

        $chars = preg_split('//u', $number . $region, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            'type' => $type,
            'number' => $number,
            'region' => $region,
            'chars' => $chars,
        ];
    }

    public function normalizeBody(string $raw): string
    {
        $raw = preg_replace('/\s+/u', '', $raw) ?? '';
        $out = '';
        $len = mb_strlen($raw, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($raw, $i, 1, 'UTF-8');
            if (isset(self::CYR_TO_LATIN[$ch])) {
                $out .= self::CYR_TO_LATIN[$ch];
                continue;
            }
            if (preg_match('/^\d$/', $ch)) {
                $out .= $ch;
                continue;
            }
            if (preg_match('/^[A-Za-z]$/', $ch)) {
                $up = strtoupper($ch);
                if (!str_contains(self::ALLOWED, $up)) {
                    throw new PlateException(
                        'Недопустимый символ. Разрешены: А В Е К М Н О Р С Т У Х и цифры.'
                    );
                }
                $out .= $up;
                continue;
            }
            throw new PlateException(
                'Недопустимый символ. Разрешены: А В Е К М Н О Р С Т У Х и цифры.'
            );
        }
        return $out;
    }

    public function normalizeRegion(string $region): string
    {
        $digits = preg_replace('/\D+/', '', $region) ?? '';
        if ($digits === '' || strlen($digits) > 3) {
            throw new PlateException('Код региона: 2 или 3 цифры.');
        }
        if (strlen($digits) === 1) {
            $digits = '0' . $digits;
        }
        return $digits;
    }

    private function safe(string $s): string
    {
        return htmlspecialchars(mb_substr($s, 0, 32, 'UTF-8'), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
