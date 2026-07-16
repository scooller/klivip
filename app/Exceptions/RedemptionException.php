<?php

namespace App\Exceptions;

use Exception;

class RedemptionException extends Exception
{
    public static function linkNotFound(): self
    {
        return new self('El link de canje no existe');
    }

    public static function linkNotAvailable(): self
    {
        return new self('El link de canje no está disponible');
    }

    public static function sweepstakeNotAvailable(): self
    {
        return new self('El sorteo no está disponible');
    }

    public static function sweepstakeLimitReached(): self
    {
        return new self('El sorteo no tiene cupos disponibles');
    }

    public static function userLimitReached(): self
    {
        return new self('Has alcanzado el límite de participaciones en este sorteo');
    }

    public static function linkLimitReached(): self
    {
        return new self('Este link ha alcanzado su límite de redenciones');
    }

    public static function alreadyVoided(): self
    {
        return new self('Esta redención ya fue anulada');
    }

    public static function missingContactInfo(): self
    {
        return new self('Se requiere al menos un método de contacto (email o teléfono)');
    }
}
