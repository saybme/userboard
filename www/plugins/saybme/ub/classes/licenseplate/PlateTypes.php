<?php namespace Saybme\Ub\Classes\LicensePlate;

/**
 * Типы регистрационных знаков (ГОСТ Р 50577).
 */
final class PlateTypes
{
    public const CAR = 'car';
    public const TRAILER = 'trailer';
    public const MOTO = 'moto';

    /** Алиасы входа → канонический тип */
    public const ALIASES = [
        'car' => self::CAR,
        'auto' => self::CAR,
        '1' => self::CAR,
        'type1' => self::CAR,
        'trailer' => self::TRAILER,
        'прицеп' => self::TRAILER,
        '2' => self::TRAILER,
        'type2' => self::TRAILER,
        'moto' => self::MOTO,
        'motorcycle' => self::MOTO,
        'motorbike' => self::MOTO,
        '4' => self::MOTO,
        'type4' => self::MOTO,
    ];

    public static function normalize(string $type): string
    {
        $key = mb_strtolower(trim($type), 'UTF-8');
        if (!isset(self::ALIASES[$key])) {
            throw new PlateException(
                'Неизвестный тип номера: «' . $type . '». Допустимо: car, trailer, moto.'
            );
        }
        return self::ALIASES[$key];
    }

    public static function all(): array
    {
        return [self::CAR, self::TRAILER, self::MOTO];
    }
}
