<?php namespace Saybme\Ub\Classes\LicensePlate;

/**
 * Раскладка типа 1 в мм (ГОСТ Р 50577-2018 / типичная конструкторская схема):
 * 520×112, цифры 76, буквы 58, регион 58, intra 5, inter 15.
 *
 * @phpstan-type Slot array{x:float,y:float,w:float,h:float}
 */
final class TemplateSlots
{
    public const W = 520.0;
    public const H = 112.0;
    public const BORDER = 3.0;
    public const RADIUS = 10.0;
    public const SEP_X = 385.0;

    public const DIGIT_H = 78.0;
    public const LETTER_H = 60.0;
    public const REGION_H = 64.0;
    public const INTRA = 5.0;
    public const INTER = 14.0;

    /** Нижняя базовая линия основного ряда (мм от верха). */
    public static function mainBaseline(): float
    {
        // цифры 76 мм, вертикальный центр во внутреннем поле
        $innerTop = self::BORDER;
        $innerH = self::H - 2 * self::BORDER;
        $digitTop = $innerTop + ($innerH - self::DIGIT_H) / 2;
        return $digitTop + self::DIGIT_H;
    }

    public static function letterTop(): float
    {
        return self::mainBaseline() - self::LETTER_H;
    }

    public static function digitTop(): float
    {
        return self::mainBaseline() - self::DIGIT_H;
    }

    /**
     * Центры слотов основного ряда — считаются при вёрстке в builder
     * (ширина зависит от advance глифов).
     */
}
