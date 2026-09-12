<?php

declare(strict_types=1);

namespace FinMath\Simple;

/** Descuentos del capítulo 2: comercial, real y racional. */
final class Discount
{
    /** D = Vn d n — sobre el valor nominal */
    public static function commercial(float $nominal, float $discountRate, float $periods): float
    {
        return $nominal * $discountRate * $periods;
    }

    /** Vt = Vn (1 - d n) */
    public static function commercialProceeds(float $nominal, float $discountRate, float $periods): float
    {
        return $nominal * (1 - $discountRate * $periods);
    }

    /** Dr = Vn - P, con P el valor presente a interés simple */
    public static function real(float $nominal, float $rate, float $periods): float
    {
        return $nominal - SimpleInterest::presentValue($nominal, $rate, $periods);
    }

    /** (2.12) sobre el valor efectivo, no el nominal */
    public static function rational(float $nominal, float $discountRate, float $periods): float
    {
        return $nominal * $discountRate * $periods / (1 + $discountRate * $periods);
    }

    public static function rationalProceeds(float $nominal, float $discountRate, float $periods): float
    {
        return $nominal / (1 + $discountRate * $periods);
    }

    /**
     * Tasa vencida que cobra de verdad un banco que descuenta a tasa
     * anticipada d. Es la "verdadera tasa bancaria" de los ejercicios.
     */
    public static function trueBankRate(float $discountRate, float $periods): float
    {
        $proceeds = 1 - $discountRate * $periods;

        if ($proceeds <= 0) {
            throw new \DomainException(
                'El descuento consume todo el valor del documento: la operación no tiene sentido.'
            );
        }

        return ($discountRate * $periods / $proceeds) / $periods;
    }
}
