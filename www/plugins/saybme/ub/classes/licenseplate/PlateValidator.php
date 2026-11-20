<?php namespace Saybme\Ub\Classes\LicensePlate;

use Saybme\Ub\Classes\LicensePlate\Regions\RegionCodes;

/**
 * Валидация и нормализация серии/номера и кода региона.
 */
class PlateValidator
{
    /** Допустимые кириллические буквы ГОСТ */
    public const LETTERS = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];

    /** Латиница → кириллица (визуальные аналоги) */
    public const LATIN_MAP = [
        'A' => 'А', 'a' => 'А',
        'B' => 'В', 'b' => 'В',
        'E' => 'Е', 'e' => 'Е',
        'K' => 'К', 'k' => 'К',
        'M' => 'М', 'm' => 'М',
        'H' => 'Н', 'h' => 'Н',
        'O' => 'О', 'o' => 'О',
        'P' => 'Р', 'p' => 'Р',
        'C' => 'С', 'c' => 'С',
        'T' => 'Т', 't' => 'Т',
        'Y' => 'У', 'y' => 'У',
        'X' => 'Х', 'x' => 'Х',
    ];

    /**
     * @return array{type: string, number: string, region: string, chars: list<string>, execution: int, flag: bool}
     */
    public function validate(
        string $type,
        string $number,
        string $region,
        int $execution = 1,
        bool $flag = true,
        bool $strictRegion = false
    ): array {
        $type = PlateTypes::normalize($type);
        $number = $this->normalizeNumber($number);
        $region = $this->normalizeRegion($region);

        $this->validateFormat($type, $number);
        $this->validateRegion($region, $strictRegion);

        if ($type === PlateTypes::MOTO && !in_array($execution, [1, 2], true)) {
            throw new PlateException('Для мотоцикла execution должен быть 1 или 2.');
        }

        return [
            'type' => $type,
            'number' => $number,
            'region' => $region,
            'chars' => $this->splitChars($number),
            'execution' => $execution,
            'flag' => $flag,
        ];
    }

    public function normalizeNumber(string $number): string
    {
        $number = trim($number);
        $number = preg_replace('/[\s\-_.]+/u', '', $number) ?? '';
        $out = '';
        $len = mb_strlen($number, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($number, $i, 1, 'UTF-8');
            if (isset(self::LATIN_MAP[$ch])) {
                $ch = self::LATIN_MAP[$ch];
            }
            $ch = mb_strtoupper($ch, 'UTF-8');
            $out .= $ch;
        }
        return $out;
    }

    public function normalizeRegion(string $region): string
    {
        $region = trim($region);
        $region = preg_replace('/\D+/', '', $region) ?? '';
        if (preg_match('/^\d{1}$/', $region)) {
            $region = '0' . $region;
        }
        return $region;
    }

    public function validateFormat(string $type, string $number): void
    {
        $letter = '[' . implode('', self::LETTERS) . ']';
        $patterns = [
            PlateTypes::CAR => '/^' . $letter . '\d{3}' . $letter . '{2}$/u',
            PlateTypes::TRAILER => '/^' . $letter . '{2}\d{4}$/u',
            PlateTypes::MOTO => '/^\d{4}' . $letter . '{2}$/u',
        ];

        $hints = [
            PlateTypes::CAR => 'Формат легкового: буква + 3 цифры + 2 буквы (например А123ВС).',
            PlateTypes::TRAILER => 'Формат прицепа: 2 буквы + 4 цифры (например АВ1234).',
            PlateTypes::MOTO => 'Формат мотоцикла: 4 цифры + 2 буквы (например 1234АВ).',
        ];

        if (!isset($patterns[$type])) {
            throw new PlateException('Неизвестный тип номера.');
        }

        if (!preg_match($patterns[$type], $number)) {
            // Более детальная диагностика
            $this->throwDetailedFormatError($type, $number, $hints[$type]);
        }
    }

    public function validateRegion(string $region, bool $strictRegion = false): void
    {
        if (!preg_match('/^\d{2,3}$/', $region)) {
            throw new PlateException(
                'Код региона должен содержать 2 или 3 цифры (получено: «' . $region . '»).'
            );
        }

        if ($strictRegion && !RegionCodes::isKnown($region)) {
            throw new PlateException(
                'Неизвестный код региона «' . $region . '». Включите strictRegion=false или укажите известный код.'
            );
        }
    }

    /** @return list<string> */
    public function splitChars(string $number): array
    {
        $chars = [];
        $len = mb_strlen($number, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $chars[] = mb_substr($number, $i, 1, 'UTF-8');
        }
        return $chars;
    }

    private function throwDetailedFormatError(string $type, string $number, string $hint): void
    {
        if ($number === '') {
            throw new PlateException('Номер не указан. ' . $hint);
        }

        $allowed = array_merge(self::LETTERS, array_map('strval', range(0, 9)));
        $len = mb_strlen($number, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($number, $i, 1, 'UTF-8');
            if (!in_array($ch, $allowed, true) && !preg_match('/^\d$/u', $ch)) {
                throw new PlateException(
                    'Недопустимый символ «' . $ch . '» в номере. Разрешены цифры и буквы: '
                    . implode(', ', self::LETTERS) . '. ' . $hint
                );
            }
        }

        throw new PlateException('Неверный формат номера «' . $number . '». ' . $hint);
    }
}
