<?php namespace Saybme\Ub\Classes\LicensePlate;

use Exception;

/**
 * Исключение генератора номеров. Сообщения на русском, без трассировки для пользователя.
 */
class PlateException extends Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
