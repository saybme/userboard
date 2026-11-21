<?php namespace Saybme\Ub\Classes\LicensePlate;

/** Типы по ГОСТ Р 50577-2018 (группа 1). */
class PlateTypes
{
    public const CAR = 'car';       // тип 1
    public const TRAILER = 'trailer'; // тип 2
    public const MOTO = 'moto';     // тип 4

    /** @return list<string> */
    public static function all(): array
    {
        return [self::CAR, self::TRAILER, self::MOTO];
    }
}
