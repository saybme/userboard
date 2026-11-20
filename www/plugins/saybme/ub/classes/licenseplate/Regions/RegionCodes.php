<?php namespace Saybme\Ub\Classes\LicensePlate\Regions;

/**
 * Мягкий каталог распространённых кодов регионов РФ (2–3 цифры).
 * Не претендует на полноту всех исторических/временных кодов.
 */
final class RegionCodes
{
    /** @var array<string, true> */
    private static ?array $set = null;

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $two = [];
        for ($i = 1; $i <= 99; $i++) {
            $two[] = sprintf('%02d', $i);
        }

        $three = [
            '102', '113', '116',
            '121', '122', '123', '124', '125', '126',
            '134', '136', '138', '142', '147',
            '150', '152', '154', '155', '156', '157', '158', '159',
            '161', '163', '164',
            '173', '174', '177', '178',
            '186', '190',
            '196', '197', '198', '199',
            '702', '716', '750', '761', '763',
            '777', '790', '797', '799',
        ];

        return array_values(array_unique(array_merge($two, $three)));
    }

    public static function isKnown(string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }
        // Нормализация: «5» → «05» для двузначных
        if (preg_match('/^\d{1}$/', $code)) {
            $code = '0' . $code;
        }
        return isset(self::set()[$code]);
    }

    /** @return array<string, true> */
    private static function set(): array
    {
        if (self::$set === null) {
            self::$set = array_fill_keys(self::all(), true);
        }
        return self::$set;
    }
}
